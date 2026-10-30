import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

import {
	CURRENT_FLOOR,
	allocate,
	dcg,
	floors,
	floorSweep,
	mulberry32,
	ndcg,
	needsFallback,
	recommendFloor,
	seededShuffle,
	selectEvalSet,
	simulateBlock,
	summarise,
} from '../scripts/corpus-eval.mjs';

const range = ( n ) => Array.from( { length: n }, ( _, i ) => i + 1 );
const row = ( position, related_id, score ) => ( {
	position,
	related_id,
	score,
} );

test( 'CURRENT_FLOOR matches RELATED_MIN_SCORE in related.php', () => {
	const php = readFileSync( new URL( '../wp-content/plugins/ik2/inc/corpus/related.php', import.meta.url ), 'utf8' );
	const match = /const RELATED_MIN_SCORE = ([0-9.]+);/.exec( php );
	assert.ok( match, 'RELATED_MIN_SCORE not found in related.php' );
	assert.equal( CURRENT_FLOOR, Number( match[ 1 ] ) );
} );

test( 'the same seed gives the same shuffle', () => {
	assert.deepEqual(
		seededShuffle( range( 20 ), mulberry32( 7 ) ),
		seededShuffle( range( 20 ), mulberry32( 7 ) )
	);
} );

test( 'a shuffle keeps every item and leaves the input alone', () => {
	const input = range( 50 );
	const out = seededShuffle( input, mulberry32( 1 ) );
	assert.deepEqual(
		[ ...out ].sort( ( a, b ) => a - b ),
		input
	);
	assert.notDeepEqual( out, input );
	assert.deepEqual( input, range( 50 ) );
} );

test( 'different seeds give different orders', () => {
	assert.notDeepEqual(
		seededShuffle( range( 20 ), mulberry32( 1 ) ),
		seededShuffle( range( 20 ), mulberry32( 2 ) )
	);
} );

test( 'allocation fills the slots the fixed Articles leave, at least one per category', () => {
	const need = allocate( { Big: 200, Mid: 70, Tiny: 2 }, 10, { Big: 3 } );
	assert.equal(
		Object.values( need ).reduce( ( a, b ) => a + b, 0 ),
		7
	);
	assert.ok( need.Tiny >= 1 );
	assert.ok( need.Big > need.Mid );
} );

test( 'allocation never asks a category for more Articles than it has', () => {
	const need = allocate( { A: 1, B: 100 }, 10, { A: 1 } );
	assert.equal( need.A, 0 );
	assert.equal( need.B, 9 );
} );

test( 'the eval set starts with the fixed IDs and has no repeats', () => {
	const catalog = range( 60 ).map( ( id ) => ( {
		id,
		category: id % 3 ? 'Misc' : 'Tooling',
	} ) );
	catalog.push( { id: 99, category: 'Uncategorized' } );
	const { ids } = selectEvalSet( catalog, [ 3, 4 ], 12, 42 );
	assert.deepEqual( ids.slice( 0, 2 ), [ 3, 4 ] );
	assert.equal( ids.length, 12 );
	assert.equal( new Set( ids ).size, 12 );
	assert.ok( ! ids.includes( 99 ) );
	assert.deepEqual( selectEvalSet( catalog, [ 3, 4 ], 12, 42 ).ids, ids );
} );

test( 'dcg uses gain 2^g - 1 and a log2 discount', () => {
	assert.equal( dcg( [ 2, 1 ] ), 3 + 1 / Math.log2( 3 ) );
	assert.equal( dcg( [ null, 2 ] ), 3 / Math.log2( 3 ) );
} );

test( 'ndcg compares against the pooled grades, not the list itself', () => {
	assert.equal( ndcg( [ 2, 2, 1, 0 ], [ 2, 2, 1, 0 ] ), 1 );
	assert.ok( ndcg( [ 1, 1, 1, 1 ], [ 1, 1, 1, 1, 2 ] ) < 1 );
	assert.equal( ndcg( [ 0, 0 ], [ 0, 0, 0 ] ), null );
} );

test( 'summarise leaves ungraded picks out of the shares', () => {
	assert.deepEqual( summarise( [ 2, 0, null, 1 ] ), {
		n: 3,
		ungraded: 1,
		mean: 1,
		twos: 1 / 3,
		zeros: 1 / 3,
	} );
} );

test( 'the floor list runs from 0.50 to 0.85 in clean 0.025 steps', () => {
	const list = floors();
	assert.equal( list.length, 15 );
	assert.equal( list[ 0 ], 0.5 );
	assert.equal( list[ 1 ], 0.525 );
	assert.equal( list.at( -1 ), 0.85 );
} );

test( 'positions 5 to 8 move into the block when top picks fall under the floor', () => {
	const rows = [
		row( 1, 11, 0.7 ),
		row( 2, 12, 0.5 ),
		row( 3, 13, 0.75 ),
		row( 4, 14, 0.55 ),
		row( 5, 15, 0.8 ),
		row( 6, 16, 0.65 ),
	];
	const block = simulateBlock( 1, rows, [ 21, 22 ], 0.6 );
	assert.deepEqual(
		block.map( ( p ) => p.id ),
		[ 11, 13, 15, 16 ]
	);
	assert.ok( block.every( ( p ) => p.from === 'new' ) );
} );

test( 'the category top-up skips the post itself and picks already kept', () => {
	const rows = [ row( 1, 21, 0.9 ), row( 2, 12, 0.1 ) ];
	const block = simulateBlock( 1, rows, [ 21, 22, 23, 24 ], 0.6 );
	assert.deepEqual(
		block.map( ( p ) => [ p.id, p.from ] ),
		[
			[ 21, 'new' ],
			[ 22, 'fallback' ],
			[ 23, 'fallback' ],
			[ 24, 'fallback' ],
		]
	);
	assert.equal( needsFallback( 1, rows, 0.6 ), true );
} );

test( 'the floor sweep counts kept picks, kept zeros and dropped twos', () => {
	const picks = [
		{ score: 0.55, grade: 0 },
		{ score: 0.6, grade: 0 },
		{ score: 0.62, grade: 1 },
		{ score: 0.7, grade: 2 },
		{ score: 0.8, grade: 2 },
	];
	const articles = [
		{
			id: 1,
			rows: [
				row( 1, 2, 0.7 ),
				row( 2, 3, 0.7 ),
				row( 3, 4, 0.7 ),
				row( 4, 5, 0.6 ),
			],
		},
	];
	const [ low, high ] = floorSweep( picks, articles, [ 0.5, 0.65 ] );
	assert.deepEqual( low, {
		floor: 0.5,
		kept: 5,
		zerosKept: 2,
		twosDropped: 0,
		zeroDropShare: 0,
		twoDropShare: 0,
		meanKept: 1,
		fallbackArticles: 0,
	} );
	assert.equal( high.kept, 2 );
	assert.equal( high.zerosKept, 0 );
	assert.equal( high.meanKept, 2 );
	assert.equal( high.fallbackArticles, 1 );
} );

test( 'the recommended floor is the lowest one meeting the 80/10 rule', () => {
	const sweep = [
		{ floor: 0.5, zeroDropShare: 0.2, twoDropShare: 0 },
		{ floor: 0.55, zeroDropShare: 0.8, twoDropShare: 0.1 },
		{ floor: 0.6, zeroDropShare: 1, twoDropShare: 0.05 },
	];
	assert.deepEqual( recommendFloor( sweep ), { floor: 0.55, closest: null } );
} );

test( 'with no floor meeting the rule the closest safe one is named instead', () => {
	const sweep = [
		{ floor: 0.5, zeroDropShare: 0.1, twoDropShare: 0 },
		{ floor: 0.55, zeroDropShare: 0.5, twoDropShare: 0.1 },
		{ floor: 0.6, zeroDropShare: 0.9, twoDropShare: 0.3 },
	];
	assert.deepEqual( recommendFloor( sweep ), { floor: null, closest: 0.55 } );
} );
