import assert from 'node:assert/strict';
import { test } from 'node:test';

import { tokenize } from '../src/bm25.mjs';
import { KEEP, bm25Query, cut, parseTags, rankAll } from '../src/rank.mjs';

test( 'parseTags reads line 3 of embed_text', () => {
	assert.deepEqual( parseTags( 'Title\nCategory: WordPress, Misc\nTags: security, wp-cli\n\nBody' ), [ 'security', 'wp-cli' ] );
} );

test( 'parseTags returns nothing for an empty Tags line', () => {
	assert.deepEqual( parseTags( 'Title\nCategory: \nTags: \n\nBody' ), [] );
	assert.deepEqual( parseTags( 'Title\nCategory: \nTags:\n\nBody' ), [] );
} );

test( 'parseTags ignores text that is not a Tags line', () => {
	assert.deepEqual( parseTags( 'Title\nCategory: Misc\nSome body text' ), [] );
	assert.deepEqual( parseTags( '' ), [] );
} );

test( 'bm25Query is the unique terms of the title and tags', () => {
	assert.deepEqual( bm25Query( 'Secure WordPress', [ 'wordpress', 'nginx' ] ), tokenize( 'Secure WordPress nginx' ) );
} );

test( 'cut keeps the top 8 by fused score with positions 1 to 8 and cosine scores', () => {
	const fused = new Map();
	const cosineOf = new Map();
	for ( let id = 1; id <= 12; id++ ) {
		fused.set( id, 1 / id );
		cosineOf.set( id, id / 100 );
	}
	const list = cut( fused, cosineOf );
	assert.equal( KEEP, 8 );
	assert.deepEqual(
		list.map( ( r ) => r.relatedId ),
		[ 1, 2, 3, 4, 5, 6, 7, 8 ]
	);
	assert.deepEqual(
		list.map( ( r ) => r.position ),
		[ 1, 2, 3, 4, 5, 6, 7, 8 ]
	);
	assert.equal( list[ 0 ].score, 0.01 );
} );

test( 'cut breaks fused ties by cosine, then by the lower id', () => {
	const fused = new Map( [
		[ 7, 0.5 ],
		[ 3, 0.5 ],
		[ 5, 0.5 ],
	] );
	const cosineOf = new Map( [
		[ 7, 0.9 ],
		[ 3, 0.4 ],
		[ 5, 0.4 ],
	] );
	assert.deepEqual(
		cut( fused, cosineOf ).map( ( r ) => r.relatedId ),
		[ 7, 3, 5 ]
	);
} );

test( 'cut returns fewer than 8 when there are fewer candidates', () => {
	assert.equal( cut( new Map( [ [ 1, 1 ] ] ), new Map( [ [ 1, 0.5 ] ] ) ).length, 1 );
} );

function doc( postId, title, tags, vector, body = '' ) {
	return { postId, title, embedText: `${ title }\nCategory: Misc\nTags: ${ tags.join( ', ' ) }\n\n${ body }`, vector: Float32Array.from( vector ) };
}

test( 'rankAll never lists an Article as related to itself and caps lists at 8', () => {
	const docs = [];
	for ( let i = 1; i <= 12; i++ ) {
		docs.push( doc( i, `Post ${ i } about servers`, [ 'servers' ], [ 1, i / 10, 0 ] ) );
	}
	const related = rankAll( docs );
	assert.equal( related.size, 12 );
	for ( const [ postId, list ] of related ) {
		assert.equal( list.length, 8 );
		assert.ok( list.every( ( r ) => r.relatedId !== postId ) );
	}
} );

test( 'rankAll puts the Article close on both lists first and scores by cosine', () => {
	const docs = [
		doc( 10, 'Secure your WordPress site', [ 'security', 'wordpress' ], [ 1, 0, 0 ] ),
		doc( 20, 'WordPress security checklist', [ 'security' ], [ 0.9, 0.1, 0 ], 'Harden logins.' ),
		doc( 30, 'IceCast2 radio on Ubuntu', [ 'ubuntu' ], [ 0, 1, 0 ] ),
		doc( 40, 'Nginx caching for WordPress', [ 'nginx' ], [ 0.5, 0.5, 0 ] ),
	];
	const list = rankAll( docs ).get( 10 );
	assert.equal( list[ 0 ].relatedId, 20 );
	assert.equal( list[ 0 ].position, 1 );
	const expected = 0.9 / Math.hypot( 0.9, 0.1 );
	assert.ok( Math.abs( list[ 0 ].score - expected ) < 1e-6 );
	assert.equal( list.at( -1 ).relatedId, 30 );
} );

test( 'rankAll handles a single Article', () => {
	assert.deepEqual( rankAll( [ doc( 1, 'Alone', [], [ 1, 0 ] ) ] ).get( 1 ), [] );
} );
