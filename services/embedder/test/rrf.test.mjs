import assert from 'node:assert/strict';
import { test } from 'node:test';

import { RRF_K, fuse } from '../src/rrf.mjs';

test( 'k defaults to 60', () => {
	assert.equal( RRF_K, 60 );
} );

test( 'each list adds 1 / (k + rank)', () => {
	const scores = fuse( [
		[ 10, 20 ],
		[ 20, 30 ],
	] );
	assert.equal( scores.get( 10 ), 1 / 61 );
	assert.equal( scores.get( 20 ), 1 / 62 + 1 / 61 );
	assert.equal( scores.get( 30 ), 1 / 62 );
} );

test( 'an id in both lists beats one that tops a single list', () => {
	const scores = fuse( [
		[ 1, 2, 3 ],
		[ 4, 5, 3 ],
	] );
	assert.ok( scores.get( 3 ) > scores.get( 1 ) );
	assert.ok( scores.get( 3 ) > scores.get( 4 ) );
} );

test( 'equal weights make the fusion symmetric', () => {
	const ab = fuse( [
		[ 1, 2 ],
		[ 2, 1 ],
	] );
	assert.equal( ab.get( 1 ), ab.get( 2 ) );
} );

test( 'empty lists fuse to nothing', () => {
	assert.equal( fuse( [ [], [] ] ).size, 0 );
} );
