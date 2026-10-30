import assert from 'node:assert/strict';
import { test } from 'node:test';

import { CorpusDb } from '../src/db.mjs';
import { MODEL_NAME } from '../src/levels.mjs';
import { pack } from '../src/vectors.mjs';

const TABLES = { documents: '`d`', chunks: '`c`', vectors: '`v`', related: '`r`', runs: '`u`' };

function fakeConn( { rows = [], failOn = null } = {} ) {
	const calls = [];
	return {
		calls,
		query: async ( sql, params ) => {
			calls.push( [ 'query', sql.replace( /\s+/g, ' ' ).trim(), params ] );
			if ( failOn && sql.includes( failOn ) ) {
				throw new Error( 'lost connection' );
			}
			return [ sql.startsWith( 'SELECT' ) ? rows : {} ];
		},
		commit: async () => calls.push( [ 'commit' ] ),
		rollback: async () => calls.push( [ 'rollback' ] ),
	};
}

function steps( conn ) {
	return conn.calls.map( ( [ kind, sql ] ) => ( kind === 'query' ? sql.split( ' FROM ' )[ 0 ] : kind ) );
}

test( 'loadMetadata reads all three tables inside one consistent snapshot', async () => {
	const conn = fakeConn();
	const metadata = await new CorpusDb( conn, TABLES ).loadMetadata();
	assert.deepEqual( metadata, { documents: [], chunks: [], vectors: [] } );
	assert.deepEqual( steps( conn ), [
		'START TRANSACTION WITH CONSISTENT SNAPSHOT',
		'SELECT id, status, attempts, embed_hash AS embedHash, content_hash AS contentHash',
		'SELECT id, document_id AS documentId, content_hash AS contentHash',
		'SELECT id, level, owner_id AS ownerId, model, content_hash AS contentHash',
		'commit',
	] );
} );

test( 'loadMetadata rolls the snapshot back when a read fails', async () => {
	const conn = fakeConn( { failOn: '`c`' } );
	await assert.rejects( new CorpusDb( conn, TABLES ).loadMetadata(), /lost connection/ );
	assert.equal( steps( conn ).at( -1 ), 'rollback' );
	assert.ok( ! steps( conn ).includes( 'commit' ) );
} );

test( 'rankingSet takes every Article not marked remove, so a re-queued one keeps its old vector', async () => {
	const conn = fakeConn( {
		rows: [ { postId: 7, title: 'Edited', embedText: 'Edited\nCategory: \nTags: \n\nBody', embedding: pack( [ 0.5, -1 ] ) } ],
	} );
	const docs = await new CorpusDb( conn, TABLES ).rankingSet( MODEL_NAME );
	const [ [ , sql, params ] ] = conn.calls;
	assert.match( sql, /WHERE d\.object_type = 'post' AND d\.status <> 'remove' ORDER/ );
	assert.doesNotMatch( sql, /status = 'indexed'/ );
	assert.deepEqual( params, [ MODEL_NAME ] );
	assert.deepEqual( docs, [
		{ postId: 7, title: 'Edited', embedText: 'Edited\nCategory: \nTags: \n\nBody', vector: Float32Array.from( [ 0.5, -1 ] ) },
	] );
} );

function writeConn( { failOn = null } = {} ) {
	const conn = fakeConn( { failOn } );
	conn.execute = async ( sql, params ) => {
		conn.calls.push( [ 'query', sql.replace( /\s+/g, ' ' ).trim(), params ] );
		return [ { insertId: 42, affectedRows: 1 } ];
	};
	return conn;
}

test( 'writeRun inserts the Run, replaces the lists in one transaction and returns the Run ID', async () => {
	const conn = writeConn();
	const related = new Map( [ [ 7, [ { relatedId: 9, position: 1, score: 0.8 } ] ] ] );
	const counts = { documents: 1, chunks: 2, removed: 0, failed: 0 };
	const runId = await new CorpusDb( conn, TABLES ).writeRun( { startedAt: '2026-10-30 12:00:00', model: MODEL_NAME, counts, related } );
	assert.equal( runId, 42 );
	assert.deepEqual(
		conn.calls.map( ( [ kind, sql ] ) => ( kind === 'query' ? sql.split( ' (' )[ 0 ] : kind ) ),
		[ 'START TRANSACTION', 'INSERT INTO `u`', 'DELETE FROM `r`', 'INSERT INTO `r`', 'commit' ]
	);
	assert.deepEqual( conn.calls[ 3 ][ 2 ], [ [ [ 7, 1, 9, 0.8, 42 ] ] ] );
} );

test( 'writeRun rolls back when the lists fail to insert, so the Run row never commits alone', async () => {
	const conn = writeConn( { failOn: 'INSERT INTO `r`' } );
	const related = new Map( [ [ 7, [ { relatedId: 9, position: 1, score: 0.8 } ] ] ] );
	const counts = { documents: 0, chunks: 0, removed: 0, failed: 0 };
	await assert.rejects(
		new CorpusDb( conn, TABLES ).writeRun( { startedAt: 'x', model: MODEL_NAME, counts, related } ),
		/lost connection/
	);
	assert.equal( conn.calls.at( -1 )[ 0 ], 'rollback' );
	assert.ok( ! conn.calls.some( ( [ kind ] ) => kind === 'commit' ) );
} );

test( 'removeDocument reports false and deletes nothing else when the row was re-queued', async () => {
	const conn = fakeConn();
	conn.execute = async ( sql, params ) => {
		conn.calls.push( [ 'query', sql.replace( /\s+/g, ' ' ).trim(), params ] );
		return [ { affectedRows: 0 } ];
	};
	assert.equal( await new CorpusDb( conn, TABLES ).removeDocument( 5 ), false );
	assert.deepEqual( steps( conn ), [ 'START TRANSACTION', "DELETE", 'commit' ] );
} );
