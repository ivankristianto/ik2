import { MODEL_NAME, prefixFor } from './levels.mjs';
import { rankAll } from './rank.mjs';
import { MAX_ATTEMPTS, findWork } from './staleness.mjs';

function seconds( startMs ) {
	return `${ ( ( performance.now() - startMs ) / 1000 ).toFixed( 1 ) }s`;
}

async function embedOne( db, embed, level, ownerId, row ) {
	const vector = await embed( level, row.text );
	await db.saveVector( { level, ownerId, model: MODEL_NAME, prefix: prefixFor( level ), vector, contentHash: row.hash } );
}

/**
 * One Run over an open CorpusDb. loadEmbedder is called only when there is something to embed.
 * Resolves to the counts, `{ noWork: true }`, or `{ skipped: true }` when another Run holds the lock.
 */
export async function runOnce( { db, loadEmbedder, log } ) {
	const startMs = performance.now();
	if ( ! ( await db.acquireLock() ) ) {
		log( 'ik2-embedder: another Run holds the lock, exiting' );
		return { skipped: true };
	}
	try {
		const startedAt = await db.now();
		const work = findWork( await db.loadMetadata(), MODEL_NAME );
		if ( ! work.hasWork ) {
			log( `ik2-embedder: no work, ${ seconds( startMs ) }` );
			return { noWork: true };
		}

		const counts = { documents: 0, chunks: 0, removed: 0, failed: 0 };
		for ( const id of work.removals ) {
			if ( await db.removeDocument( id ) ) {
				counts.removed++;
			}
		}
		await db.deleteVectors( work.orphanVectorIds );

		const needsModel = work.toIndex.some( ( p ) => p.embedDocument || p.chunkIds.length );
		const { embed } = needsModel ? await loadEmbedder() : { embed: null };
		const succeeded = [];
		for ( const item of work.toIndex ) {
			const done = { documents: 0, chunks: 0 };
			try {
				if ( item.embedDocument ) {
					const row = await db.documentText( item.id );
					if ( ! row ) {
						continue;
					}
					await embedOne( db, embed, 'document', item.id, row );
					done.documents++;
				}
				for ( const chunkId of item.chunkIds ) {
					// A Chunk deleted mid-Run leaves an orphan vector at worst, cleaned next Run.
					const row = await db.chunkText( chunkId );
					if ( row ) {
						await embedOne( db, embed, 'chunk', chunkId, row );
						done.chunks++;
					}
				}
				succeeded.push( item );
			} catch ( err ) {
				counts.failed++;
				await db.recordFailure( item.id, item.contentHash, String( err?.message ?? err ), MAX_ATTEMPTS );
			}
			counts.documents += done.documents;
			counts.chunks += done.chunks;
		}

		const related = rankAll( await db.rankingSet( MODEL_NAME ) );
		const runId = await db.writeRun( { startedAt, model: MODEL_NAME, counts, related } );
		for ( const item of succeeded ) {
			await db.markIndexed( item.id, item.contentHash );
		}

		log(
			`ik2-embedder: run ${ runId }, ${ counts.documents } documents and ${ counts.chunks } chunks embedded, ` +
				`${ counts.removed } removed, ${ counts.failed } failed, ${ work.orphanVectorIds.length } orphan vectors deleted, ` +
				`${ related.size } related lists, ${ seconds( startMs ) }, peak rss ${ Math.round( process.resourceUsage().maxRSS / 1024 ) } MB`
		);
		return { runId, ...counts, orphans: work.orphanVectorIds.length, lists: related.size };
	} finally {
		// Closing the session releases the lock anyway; don't mask the Run's own error.
		await db.releaseLock().catch( () => {} );
	}
}
