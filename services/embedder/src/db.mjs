import mysql from 'mysql2/promise';

import { pack, unpack } from './vectors.mjs';

const LOCK_NAME = 'ik2_corpus';
const INSERT_BATCH = 500;

export async function connect( config ) {
	const conn = await mysql.createConnection( {
		host: config.host,
		port: config.port,
		user: config.user,
		password: config.password,
		database: config.database,
		charset: 'utf8mb4',
		dateStrings: true,
	} );
	const tables = Object.fromEntries( Object.entries( config.tables ).map( ( [ k, v ] ) => [ k, mysql.escapeId( v ) ] ) );
	return new CorpusDb( conn, tables );
}

export class CorpusDb {
	constructor( conn, tables ) {
		this.conn = conn;
		this.tables = tables;
	}

	/** Runs `work` in one transaction, rolled back when it throws. */
	async transaction( work, { snapshot = false } = {} ) {
		await this.conn.query( snapshot ? 'START TRANSACTION WITH CONSISTENT SNAPSHOT' : 'START TRANSACTION' );
		try {
			const result = await work();
			await this.conn.commit();
			return result;
		} catch ( err ) {
			await this.conn.rollback();
			throw err;
		}
	}

	async close() {
		await this.conn.end();
	}

	async acquireLock() {
		const [ [ row ] ] = await this.conn.query( 'SELECT GET_LOCK(?, 0) AS got', [ LOCK_NAME ] );
		return row.got === 1;
	}

	async releaseLock() {
		await this.conn.query( 'SELECT RELEASE_LOCK(?)', [ LOCK_NAME ] );
	}

	async now() {
		const [ [ row ] ] = await this.conn.query( 'SELECT UTC_TIMESTAMP() AS now' );
		return row.now;
	}

	/** Metadata only, no text or blobs, so finding work stays cheap. */
	async loadMetadata() {
		const t = this.tables;
		// One snapshot, so a save that commits a Document and its Chunks between reads is seen whole or not at all.
		return this.transaction(
			async () => {
				const [ documents ] = await this.conn.query(
					`SELECT id, status, attempts, embed_hash AS embedHash, content_hash AS contentHash FROM ${ t.documents }`
				);
				const [ chunks ] = await this.conn.query(
					`SELECT id, document_id AS documentId, content_hash AS contentHash FROM ${ t.chunks }`
				);
				const [ vectors ] = await this.conn.query(
					`SELECT id, level, owner_id AS ownerId, model, content_hash AS contentHash FROM ${ t.vectors }`
				);
				return { documents, chunks, vectors };
			},
			{ snapshot: true }
		);
	}

	/**
	 * Deletes a Document marked `remove` with its Chunks and vectors.
	 * @return {Promise<boolean>} false when it was re-queued since the work was read
	 */
	async removeDocument( id ) {
		const t = this.tables;
		return this.transaction( async () => {
			const [ res ] = await this.conn.execute( `DELETE FROM ${ t.documents } WHERE id = ? AND status = 'remove'`, [ id ] );
			if ( res.affectedRows === 0 ) {
				return false;
			}
			const [ chunks ] = await this.conn.execute( `SELECT id FROM ${ t.chunks } WHERE document_id = ?`, [ id ] );
			await this.deleteVectorsByOwner( 'chunk', chunks.map( ( c ) => c.id ) );
			await this.deleteVectorsByOwner( 'document', [ id ] );
			await this.conn.execute( `DELETE FROM ${ t.chunks } WHERE document_id = ?`, [ id ] );
			return true;
		} );
	}

	async deleteVectorsByOwner( level, ownerIds ) {
		if ( ownerIds.length ) {
			await this.conn.query( `DELETE FROM ${ this.tables.vectors } WHERE level = ? AND owner_id IN (?)`, [ level, ownerIds ] );
		}
	}

	async deleteVectors( ids ) {
		if ( ids.length ) {
			await this.conn.query( `DELETE FROM ${ this.tables.vectors } WHERE id IN (?)`, [ ids ] );
		}
	}

	/** @return {Promise<{text:string, hash:string}|null>} null when the row is gone */
	async documentText( id ) {
		const [ [ row ] ] = await this.conn.execute(
			`SELECT embed_text AS text, embed_hash AS hash FROM ${ this.tables.documents } WHERE id = ?`,
			[ id ]
		);
		return row ?? null;
	}

	/** @return {Promise<{text:string, hash:string}|null>} null when the row is gone */
	async chunkText( id ) {
		const [ [ row ] ] = await this.conn.execute(
			`SELECT text, content_hash AS hash FROM ${ this.tables.chunks } WHERE id = ?`,
			[ id ]
		);
		return row ?? null;
	}

	async saveVector( { level, ownerId, model, prefix, vector, contentHash } ) {
		await this.conn.execute(
			`INSERT INTO ${ this.tables.vectors } (level, owner_id, model, prefix, dims, embedding, content_hash, embedded_at)
			VALUES (?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())
			ON DUPLICATE KEY UPDATE model = VALUES(model), prefix = VALUES(prefix), dims = VALUES(dims),
				embedding = VALUES(embedding), content_hash = VALUES(content_hash), embedded_at = VALUES(embedded_at)`,
			[ level, ownerId, model, prefix, vector.length, pack( vector ), contentHash ]
		);
	}

	/** Counts a failed attempt against the version read at the start of the Run. */
	async recordFailure( id, contentHash, message, maxAttempts ) {
		// status is assigned before attempts so it reads the old value in every sql_mode.
		await this.conn.execute(
			`UPDATE ${ this.tables.documents }
			SET status = IF(attempts + 1 >= ?, 'parked', status), attempts = attempts + 1, last_error = ?
			WHERE id = ? AND content_hash = ? AND status IN ('queued', 'indexed')`,
			[ maxAttempts, message.slice( 0, 1000 ), id, contentHash ]
		);
	}

	/**
	 * Articles to rank: any not marked `remove` with a current-model Document vector,
	 * so a re-queued Article whose embedding failed keeps its previous vector in the lists.
	 */
	async rankingSet( model ) {
		const t = this.tables;
		const [ rows ] = await this.conn.query(
			`SELECT d.object_id AS postId, d.title, d.embed_text AS embedText, v.embedding
			FROM ${ t.documents } d
			JOIN ${ t.vectors } v ON v.level = 'document' AND v.owner_id = d.id AND v.model = ?
			WHERE d.object_type = 'post' AND d.status <> 'remove'
			ORDER BY d.object_id`,
			[ model ]
		);
		return rows.map( ( r ) => ( { postId: r.postId, title: r.title, embedText: r.embedText, vector: unpack( r.embedding ) } ) );
	}

	/**
	 * Inserts the Run row and replaces every related list in one commit, so a reader
	 * never sees the new Run ID with the old lists. Resolves to the Run ID.
	 */
	async writeRun( { startedAt, model, counts, related } ) {
		const t = this.tables;
		return this.transaction( async () => {
			const [ res ] = await this.conn.execute(
				`INSERT INTO ${ t.runs } (started_at, finished_at, model, documents_embedded, chunks_embedded, removed, failed)
				VALUES (?, UTC_TIMESTAMP(), ?, ?, ?, ?, ?)`,
				[ startedAt, model, counts.documents, counts.chunks, counts.removed, counts.failed ]
			);
			const runId = res.insertId;
			// DELETE, not TRUNCATE: TRUNCATE commits on its own and needs DROP.
			await this.conn.query( `DELETE FROM ${ t.related }` );
			const rows = [];
			for ( const [ postId, list ] of related ) {
				for ( const r of list ) {
					rows.push( [ postId, r.position, r.relatedId, r.score, runId ] );
				}
			}
			for ( let i = 0; i < rows.length; i += INSERT_BATCH ) {
				await this.conn.query( `INSERT INTO ${ t.related } (post_id, position, related_id, score, run_id) VALUES ?`, [
					rows.slice( i, i + INSERT_BATCH ),
				] );
			}
			return runId;
		} );
	}

	/** An Article edited or removed during the Run no longer matches and keeps its status. */
	async markIndexed( id, contentHash ) {
		await this.conn.execute(
			`UPDATE ${ this.tables.documents }
			SET status = 'indexed', indexed_at = UTC_TIMESTAMP(), attempts = 0, last_error = NULL
			WHERE id = ? AND content_hash = ? AND status IN ('queued', 'indexed')`,
			[ id, contentHash ]
		);
	}
}
