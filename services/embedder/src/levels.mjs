export const MODEL_ID = 'nomic-ai/nomic-embed-text-v1.5';
export const MODEL_REVISION = 'e9b6763023c676ca8431644204f50c2b100d9aab';
export const MODEL_DTYPE = 'q8';
// Stored in the `model` columns; changing it makes every vector stale.
export const MODEL_NAME = `${ MODEL_ID }:${ MODEL_DTYPE }`;
export const DIMS = 768;

export const LEVELS = Object.freeze( {
	document: Object.freeze( { prefix: 'clustering: ', maxTokens: 1024 } ),
	chunk: Object.freeze( { prefix: 'search_document: ', maxTokens: 512 } ),
} );

function levelSpec( level ) {
	const spec = LEVELS[ level ];
	if ( ! spec ) {
		throw new Error( `Unknown vector level: ${ level }` );
	}
	return spec;
}

export function prefixFor( level ) {
	return levelSpec( level ).prefix;
}

export function maxTokensFor( level ) {
	return levelSpec( level ).maxTokens;
}

export function inputFor( level, text ) {
	return prefixFor( level ) + text;
}
