import assert from 'node:assert/strict';
import { test } from 'node:test';

import { findWork, isStale } from '../src/staleness.mjs';

const MODEL = 'nomic-ai/nomic-embed-text-v1.5:q8';

function vec( id, level, ownerId, contentHash, model = MODEL ) {
	return { id, level, ownerId, model, contentHash };
}

test( 'isStale: missing, other model or other hash', () => {
	assert.equal( isStale( undefined, MODEL, 'h' ), true );
	assert.equal( isStale( vec( 1, 'document', 1, 'h', 'other:q8' ), MODEL, 'h' ), true );
	assert.equal( isStale( vec( 1, 'document', 1, 'old' ), MODEL, 'h' ), true );
	assert.equal( isStale( vec( 1, 'document', 1, 'h' ), MODEL, 'h' ), false );
} );

test( 'an indexed Corpus with fresh vectors has no work', () => {
	const work = findWork(
		{
			documents: [ { id: 1, status: 'indexed', attempts: 0, embedHash: 'e1', contentHash: 'c1' } ],
			chunks: [ { id: 11, documentId: 1, contentHash: 'k11' } ],
			vectors: [ vec( 100, 'document', 1, 'e1' ), vec( 101, 'chunk', 11, 'k11' ) ],
		},
		MODEL
	);
	assert.deepEqual( work, { removals: [], toIndex: [], orphanVectorIds: [], hasWork: false } );
} );

test( 'document vectors check embed_hash and chunk vectors check the Chunk content_hash', () => {
	const work = findWork(
		{
			documents: [
				// content_hash changed but embed_hash did not: only the edited Chunk is stale.
				{ id: 1, status: 'queued', attempts: 0, embedHash: 'e1', contentHash: 'c1-new' },
			],
			chunks: [
				{ id: 11, documentId: 1, contentHash: 'k11' },
				{ id: 12, documentId: 1, contentHash: 'k12-new' },
				{ id: 13, documentId: 1, contentHash: 'k13' },
			],
			vectors: [ vec( 100, 'document', 1, 'e1' ), vec( 101, 'chunk', 11, 'k11' ), vec( 102, 'chunk', 12, 'k12' ) ],
		},
		MODEL
	);
	assert.deepEqual( work.toIndex, [ { id: 1, contentHash: 'c1-new', embedDocument: false, chunkIds: [ 12, 13 ] } ] );
} );

test( 'a queued Document with nothing stale is still processed so it becomes indexed', () => {
	const work = findWork(
		{
			documents: [ { id: 1, status: 'queued', attempts: 0, embedHash: 'e1', contentHash: 'c1' } ],
			chunks: [],
			vectors: [ vec( 100, 'document', 1, 'e1' ) ],
		},
		MODEL
	);
	assert.deepEqual( work.toIndex, [ { id: 1, contentHash: 'c1', embedDocument: false, chunkIds: [] } ] );
	assert.equal( work.hasWork, true );
} );

test( 'a model change makes every vector of indexed Documents stale', () => {
	const work = findWork(
		{
			documents: [
				{ id: 1, status: 'indexed', attempts: 0, embedHash: 'e1', contentHash: 'c1' },
				{ id: 2, status: 'indexed', attempts: 0, embedHash: 'e2', contentHash: 'c2' },
			],
			chunks: [ { id: 21, documentId: 2, contentHash: 'k21' } ],
			vectors: [ vec( 100, 'document', 1, 'e1', 'old:q8' ), vec( 200, 'document', 2, 'e2', 'old:q8' ), vec( 201, 'chunk', 21, 'k21', 'old:q8' ) ],
		},
		MODEL
	);
	assert.deepEqual( work.toIndex, [
		{ id: 1, contentHash: 'c1', embedDocument: true, chunkIds: [] },
		{ id: 2, contentHash: 'c2', embedDocument: true, chunkIds: [ 21 ] },
	] );
} );

test( 'parked Documents and those at 3 attempts are left alone', () => {
	const work = findWork(
		{
			documents: [
				{ id: 1, status: 'parked', attempts: 3, embedHash: 'e1', contentHash: 'c1' },
				{ id: 2, status: 'queued', attempts: 3, embedHash: 'e2', contentHash: 'c2' },
				{ id: 3, status: 'remove', attempts: 3, embedHash: 'e3', contentHash: 'c3' },
				{ id: 4, status: 'queued', attempts: 2, embedHash: 'e4', contentHash: 'c4' },
			],
			chunks: [],
			vectors: [],
		},
		MODEL
	);
	assert.deepEqual( work.removals, [] );
	assert.deepEqual(
		work.toIndex.map( ( p ) => p.id ),
		[ 4 ]
	);
} );

test( 'remove rows are removals, not embeddings', () => {
	const work = findWork(
		{
			documents: [ { id: 5, status: 'remove', attempts: 0, embedHash: 'e5', contentHash: 'c5' } ],
			chunks: [ { id: 51, documentId: 5, contentHash: 'k51' } ],
			vectors: [],
		},
		MODEL
	);
	assert.deepEqual( work.removals, [ 5 ] );
	assert.deepEqual( work.toIndex, [] );
} );

test( 'vectors whose owner is gone are orphans', () => {
	const work = findWork(
		{
			documents: [ { id: 1, status: 'indexed', attempts: 0, embedHash: 'e1', contentHash: 'c1' } ],
			chunks: [ { id: 11, documentId: 1, contentHash: 'k11' } ],
			vectors: [
				vec( 100, 'document', 1, 'e1' ),
				vec( 101, 'chunk', 11, 'k11' ),
				vec( 102, 'chunk', 12, 'k12' ),
				vec( 103, 'document', 9, 'e9' ),
				// Same owner id as a live Chunk, but at the document level.
				vec( 104, 'document', 11, 'x' ),
			],
		},
		MODEL
	);
	assert.deepEqual( work.orphanVectorIds, [ 102, 103, 104 ] );
	assert.deepEqual( work.toIndex, [] );
	assert.equal( work.hasWork, true );
} );
