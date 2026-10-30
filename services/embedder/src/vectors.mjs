// mysql2 can hand back a Buffer at an offset that is not a multiple of 4, so
// every read and write goes through readFloatLE / writeFloatLE.

export function pack( values ) {
	const buf = Buffer.alloc( values.length * 4 );
	for ( let i = 0; i < values.length; i++ ) {
		buf.writeFloatLE( values[ i ], i * 4 );
	}
	return buf;
}

export function unpack( buf ) {
	if ( buf.length % 4 !== 0 ) {
		throw new Error( `Vector blob length ${ buf.length } is not a multiple of 4` );
	}
	const out = new Float32Array( buf.length / 4 );
	for ( let i = 0; i < out.length; i++ ) {
		out[ i ] = buf.readFloatLE( i * 4 );
	}
	return out;
}

export function norm( v ) {
	let sum = 0;
	for ( let i = 0; i < v.length; i++ ) {
		sum += v[ i ] * v[ i ];
	}
	return Math.sqrt( sum );
}

export function dot( a, b ) {
	if ( a.length !== b.length ) {
		throw new Error( `Vector length mismatch: ${ a.length } vs ${ b.length }` );
	}
	let sum = 0;
	for ( let i = 0; i < a.length; i++ ) {
		sum += a[ i ] * b[ i ];
	}
	return sum;
}

/** Accepts Float32Arrays or packed little-endian Buffers. */
export function cosine( a, b ) {
	const va = Buffer.isBuffer( a ) ? unpack( a ) : a;
	const vb = Buffer.isBuffer( b ) ? unpack( b ) : b;
	const denom = norm( va ) * norm( vb );
	return denom === 0 ? 0 : dot( va, vb ) / denom;
}
