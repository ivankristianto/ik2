import { buildIndex, search, tokenize } from './bm25.mjs';
import { fuse } from './rrf.mjs';
import { dot, norm } from './vectors.mjs';

export const CANDIDATES = 20;
export const KEEP = 8;

/** Tag names from line 3 of embed_text (`Tags: a, b`), empty when there are none. */
export function parseTags( embedText ) {
	const line = embedText.split( '\n' )[ 2 ] ?? '';
	const match = /^Tags:(.*)$/.exec( line.trim() );
	if ( ! match ) {
		return [];
	}
	return match[ 1 ]
		.split( ',' )
		.map( ( t ) => t.trim() )
		.filter( Boolean );
}

/** Unique terms of the title and tags, the query the prototype measured. */
export function bm25Query( title, tags ) {
	return [ ...new Set( tokenize( `${ title } ${ tags.join( ' ' ) }` ) ) ];
}

/**
 * Orders fused candidates and keeps the first `keep`.
 * Ties fall back to cosine, then the lower id, so output is stable.
 * @param {Map<number, number>} fused     id to RRF score
 * @param {Map<number, number>} cosineOf  id to cosine with the source Article
 */
export function cut( fused, cosineOf, keep = KEEP ) {
	return [ ...fused.keys() ]
		.sort(
			( a, b ) =>
				fused.get( b ) - fused.get( a ) ||
				( cosineOf.get( b ) ?? 0 ) - ( cosineOf.get( a ) ?? 0 ) ||
				a - b
		)
		.slice( 0, keep )
		.map( ( id, i ) => ( { relatedId: id, position: i + 1, score: cosineOf.get( id ) ?? 0 } ) );
}

/**
 * @param {{postId:number, title:string, embedText:string, vector:Float32Array}[]} docs
 * @return {Map<number, {relatedId:number, position:number, score:number}[]>} keyed by postId
 */
export function rankAll( docs, { candidates = CANDIDATES, keep = KEEP } = {} ) {
	const index = buildIndex( new Map( docs.map( ( d ) => [ d.postId, d.embedText ] ) ) );
	const norms = docs.map( ( d ) => norm( d.vector ) || 1 );
	const result = new Map();

	docs.forEach( ( doc, i ) => {
		const cosineOf = new Map();
		docs.forEach( ( other, j ) => {
			if ( i !== j ) {
				cosineOf.set( other.postId, dot( doc.vector, other.vector ) / ( norms[ i ] * norms[ j ] ) );
			}
		} );
		const vectorList = [ ...cosineOf ]
			.sort( ( a, b ) => b[ 1 ] - a[ 1 ] || a[ 0 ] - b[ 0 ] )
			.slice( 0, candidates )
			.map( ( [ id ] ) => id );
		const keywordList = search( index, bm25Query( doc.title, parseTags( doc.embedText ) ), candidates, doc.postId );
		result.set( doc.postId, cut( fuse( [ vectorList, keywordList ] ), cosineOf, keep ) );
	} );

	return result;
}
