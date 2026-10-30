import assert from 'node:assert/strict';
import { test } from 'node:test';

import { cosine, pack, unpack } from '../src/vectors.mjs';

test( 'pack writes little-endian float32', () => {
	assert.deepEqual( [ ...pack( [ 1, -2 ] ) ], [ 0x00, 0x00, 0x80, 0x3f, 0x00, 0x00, 0x00, 0xc0 ] );
} );

test( 'unpack reverses pack', () => {
	const v = Float32Array.from( [ 0.25, -0.5, 0.125, 3 ] );
	assert.deepEqual( unpack( pack( v ) ), v );
} );

test( 'unpack reads a buffer at an offset that is not a multiple of 4', () => {
	const packed = pack( [ 0.6, 0.8 ] );
	const backing = Buffer.alloc( packed.length + 3 );
	packed.copy( backing, 3 );
	const misaligned = backing.subarray( 3 );
	assert.equal( misaligned.byteOffset % 4, 3 );
	assert.deepEqual( unpack( misaligned ), Float32Array.from( [ 0.6, 0.8 ] ) );
} );

test( 'unpack rejects a truncated blob', () => {
	assert.throws( () => unpack( Buffer.alloc( 7 ) ), /multiple of 4/ );
} );

test( 'cosine on packed float32 buffers', () => {
	const a = pack( [ 1, 0, 0 ] );
	assert.ok( Math.abs( cosine( a, pack( [ 1, 0, 0 ] ) ) - 1 ) < 1e-7 );
	assert.equal( cosine( a, pack( [ 0, 1, 0 ] ) ), 0 );
	assert.ok( Math.abs( cosine( a, pack( [ -1, 0, 0 ] ) ) + 1 ) < 1e-7 );
	assert.ok( Math.abs( cosine( pack( [ 3, 4 ] ), pack( [ 4, 3 ] ) ) - 0.96 ) < 1e-6 );
} );

test( 'cosine accepts a misaligned buffer and a Float32Array together', () => {
	const backing = Buffer.alloc( 9 );
	pack( [ 0.6, 0.8 ] ).copy( backing, 1 );
	assert.ok( Math.abs( cosine( backing.subarray( 1 ), Float32Array.from( [ 0.6, 0.8 ] ) ) - 1 ) < 1e-6 );
} );

test( 'cosine with a zero vector is 0, and mismatched lengths throw', () => {
	assert.equal( cosine( pack( [ 0, 0 ] ), pack( [ 1, 0 ] ) ), 0 );
	assert.throws( () => cosine( pack( [ 1, 0 ] ), pack( [ 1, 0, 0 ] ) ), /length mismatch/ );
} );
