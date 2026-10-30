import assert from 'node:assert/strict';
import { test } from 'node:test';

import { buildIndex, score, search, stem, tokenize } from '../src/bm25.mjs';

const DOCS = new Map( [
	[ 1, 'Secure your WordPress site with plugins and backups' ],
	[ 2, 'WordPress plugins for caching pages' ],
	[ 3, 'Radio streaming server with IceCast2 on Ubuntu servers' ],
	[ 4, 'Node.js and C++ tips for servers' ],
] );

// Expected scores come from the Python prototype's bm25() run on the same corpus.
const PROTOTYPE = { 1: 1.3570750420330544, 2: 1.4820231277445377, 3: 0.8873982280981842, 4: 0.7410115638722689 };

test( 'tokenize drops stopwords, keeps symbol terms and strips plural s', () => {
	assert.deepEqual( tokenize( 'Node.js, C++ and C# servers: the WordPress plugins class' ), [
		'node.j',
		'c++',
		'c#',
		'server',
		'wordpress',
		'plugin',
		'class',
	] );
} );

test( 'stem leaves short words and double s alone', () => {
	assert.equal( stem( 'plugins' ), 'plugin' );
	assert.equal( stem( 'tips' ), 'tips' );
	assert.equal( stem( 'access' ), 'access' );
} );

test( 'score matches the prototype', () => {
	const scores = score( buildIndex( DOCS ), tokenize( 'wordpress plugins servers' ) );
	assert.equal( scores.size, 4 );
	for ( const [ id, expected ] of Object.entries( PROTOTYPE ) ) {
		assert.ok( Math.abs( scores.get( Number( id ) ) - expected ) < 1e-12, `doc ${ id }` );
	}
} );

test( 'score skips the source document', () => {
	const scores = score( buildIndex( DOCS ), tokenize( 'wordpress plugins servers' ), 2 );
	assert.equal( scores.has( 2 ), false );
	assert.ok( Math.abs( scores.get( 1 ) - PROTOTYPE[ 1 ] ) < 1e-12 );
} );

test( 'repeated query terms add no weight, as in the prototype', () => {
	const index = buildIndex( DOCS );
	assert.deepEqual( score( index, tokenize( 'wordpress wordpress plugins' ) ), score( index, tokenize( 'wordpress plugins' ) ) );
} );

test( 'unknown terms score nothing', () => {
	assert.equal( score( buildIndex( DOCS ), tokenize( 'kubernetes' ) ).size, 0 );
} );

test( 'search returns the best ids first and honours the limit', () => {
	const index = buildIndex( DOCS );
	assert.deepEqual( search( index, tokenize( 'wordpress plugins servers' ), 2 ), [ 2, 1 ] );
	assert.deepEqual( search( index, tokenize( 'wordpress plugins servers' ), 20, 2 ), [ 1, 3, 4 ] );
} );
