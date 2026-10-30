export const RRF_K = 60;

/**
 * Reciprocal rank fusion with equal weights.
 * @param {number[][]} lists ranked ids, best first
 * @return {Map<number, number>} id to fused score
 */
export function fuse( lists, k = RRF_K ) {
	const scores = new Map();
	for ( const list of lists ) {
		list.forEach( ( id, i ) => {
			scores.set( id, ( scores.get( id ) ?? 0 ) + 1 / ( k + i + 1 ) );
		} );
	}
	return scores;
}
