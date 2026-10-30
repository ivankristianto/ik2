export const RRF_K = 60;

/** Reciprocal rank fusion with equal weights: ranked id lists in, a Map of id to fused score out. */
export function fuse( lists, k = RRF_K ) {
	const scores = new Map();
	for ( const list of lists ) {
		list.forEach( ( id, i ) => {
			scores.set( id, ( scores.get( id ) ?? 0 ) + 1 / ( k + i + 1 ) );
		} );
	}
	return scores;
}
