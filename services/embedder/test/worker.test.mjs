import assert from 'node:assert/strict';
import { test } from 'node:test';

import { MODEL_NAME } from '../src/levels.mjs';
import { runOnce } from '../src/worker.mjs';

function fakeDb( { lock = true, documents = [], chunks = [], vectors = [], texts = {}, rankingRows = [] } = {} ) {
	const calls = [];
	const record =
		( name, result ) =>
		async ( ...args ) => {
			calls.push( [ name, ...args ] );
			return typeof result === 'function' ? result( ...args ) : result;
		};
	return {
		calls,
		acquireLock: record( 'acquireLock', lock ),
		releaseLock: record( 'releaseLock' ),
		now: record( 'now', '2026-10-30 12:00:00' ),
		loadMetadata: record( 'loadMetadata', { documents, chunks, vectors } ),
		removeDocument: record( 'removeDocument', true ),
		deleteVectors: record( 'deleteVectors' ),
		documentText: record( 'documentText', ( id ) => texts[ `document:${ id }` ] ?? null ),
		chunkText: record( 'chunkText', ( id ) => texts[ `chunk:${ id }` ] ?? null ),
		saveVector: record( 'saveVector' ),
		recordFailure: record( 'recordFailure' ),
		rankingSet: record( 'rankingSet', rankingRows ),
		writeRun: record( 'writeRun', 42 ),
		markIndexed: record( 'markIndexed' ),
	};
}

function names( db ) {
	return db.calls.map( ( c ) => c[ 0 ] );
}

test( 'no work: one log line, lock released, model never loaded, no Run row', async () => {
	const db = fakeDb();
	const lines = [];
	let loaded = false;
	const result = await runOnce( {
		db,
		loadEmbedder: async () => {
			loaded = true;
		},
		log: ( l ) => lines.push( l ),
	} );
	assert.deepEqual( result, { noWork: true } );
	assert.equal( loaded, false );
	assert.equal( lines.length, 1 );
	assert.match( lines[ 0 ], /no work/ );
	assert.deepEqual( names( db ), [ 'acquireLock', 'now', 'loadMetadata', 'releaseLock' ] );
} );

test( 'a held lock exits without reading anything', async () => {
	const db = fakeDb( { lock: false } );
	const lines = [];
	const result = await runOnce( { db, loadEmbedder: async () => assert.fail(), log: ( l ) => lines.push( l ) } );
	assert.deepEqual( result, { skipped: true } );
	assert.deepEqual( names( db ), [ 'acquireLock' ] );
	assert.match( lines[ 0 ], /lock/ );
} );

test( 'a failing Document is charged an attempt and the others carry on', async () => {
	const db = fakeDb( {
		documents: [
			{ id: 1, status: 'queued', attempts: 0, embedHash: 'e1', contentHash: 'c1' },
			{ id: 2, status: 'queued', attempts: 2, embedHash: 'e2', contentHash: 'c2' },
		],
		chunks: [ { id: 21, documentId: 2, contentHash: 'k21' } ],
		texts: {
			'document:1': { text: 'ok', hash: 'e1' },
			'document:2': { text: 'ok too', hash: 'e2' },
			'chunk:21': { text: 'boom', hash: 'k21' },
		},
	} );
	const embedded = [];
	const lines = [];
	const result = await runOnce( {
		db,
		loadEmbedder: async () => ( {
			embed: async ( level, text ) => {
				if ( text === 'boom' ) {
					throw new Error( 'tokenizer exploded' );
				}
				embedded.push( [ level, text ] );
				return new Float32Array( 768 );
			},
		} ),
		log: ( l ) => lines.push( l ),
	} );

	assert.deepEqual( embedded, [
		[ 'document', 'ok' ],
		[ 'document', 'ok too' ],
	] );
	const failure = db.calls.find( ( c ) => c[ 0 ] === 'recordFailure' );
	assert.deepEqual( failure, [ 'recordFailure', 2, 'c2', 'tokenizer exploded', 3 ] );
	assert.deepEqual(
		db.calls.filter( ( c ) => c[ 0 ] === 'markIndexed' ),
		[ [ 'markIndexed', 1, 'c1' ] ]
	);
	assert.deepEqual( db.calls.find( ( c ) => c[ 0 ] === 'rankingSet' ), [ 'rankingSet', MODEL_NAME ] );
	const save = db.calls.find( ( c ) => c[ 0 ] === 'saveVector' )[ 1 ];
	assert.equal( save.prefix, 'clustering: ' );
	assert.equal( save.contentHash, 'e1' );
	assert.equal( save.model, MODEL_NAME );
	assert.equal( result.documents, 2 );
	assert.equal( result.failed, 1 );
	assert.equal( lines.length, 1 );
	assert.match( lines[ 0 ], /run 42, 2 documents and 0 chunks embedded, 0 removed, 1 failed/ );
} );

test( 'the Run row is written before Documents are marked indexed', async () => {
	const db = fakeDb( {
		documents: [ { id: 1, status: 'queued', attempts: 0, embedHash: 'e1', contentHash: 'c1' } ],
		vectors: [ { id: 9, level: 'document', ownerId: 1, model: MODEL_NAME, contentHash: 'e1' } ],
	} );
	await runOnce( { db, loadEmbedder: async () => assert.fail( 'nothing to embed' ), log: () => {} } );
	const order = names( db );
	assert.ok( order.indexOf( 'writeRun' ) < order.indexOf( 'markIndexed' ) );
	assert.equal( order.at( -1 ), 'releaseLock' );
} );

test( 'removals and orphans are cleaned without loading the model', async () => {
	const db = fakeDb( {
		documents: [ { id: 5, status: 'remove', attempts: 0, embedHash: 'e5', contentHash: 'c5' } ],
		vectors: [ { id: 77, level: 'chunk', ownerId: 999, model: MODEL_NAME, contentHash: 'x' } ],
	} );
	const result = await runOnce( { db, loadEmbedder: async () => assert.fail( 'nothing to embed' ), log: () => {} } );
	assert.deepEqual( db.calls.find( ( c ) => c[ 0 ] === 'removeDocument' ), [ 'removeDocument', 5 ] );
	assert.deepEqual( db.calls.find( ( c ) => c[ 0 ] === 'deleteVectors' ), [ 'deleteVectors', [ 77 ] ] );
	assert.equal( result.removed, 1 );
	assert.equal( result.orphans, 1 );
} );

test( 'a database error fails the Run and still releases the lock', async () => {
	const db = fakeDb();
	db.loadMetadata = async () => {
		throw new Error( 'gone away' );
	};
	await assert.rejects( runOnce( { db, loadEmbedder: async () => {}, log: () => {} } ), /gone away/ );
	assert.equal( names( db ).at( -1 ), 'releaseLock' );
} );
