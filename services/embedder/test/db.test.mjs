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
