export const MAX_ATTEMPTS = 3;

export function isStale( vector, model, ownerHash ) {
	return ! vector || vector.model !== model || vector.contentHash !== ownerHash;
}

function vectorKey( level, ownerId ) {
	return `${ level }:${ ownerId }`;
}

/**
 * Decides what a Run has to do from row metadata alone (no text, no blobs).
 * Parked rows and rows at MAX_ATTEMPTS are left alone, so a broken Article
 * can't make every hourly Run load the model.
 *
 * @param {object}   rows
 * @param {{id:number, status:string, attempts:number, embedHash:string, contentHash:string}[]} rows.documents
 * @param {{id:number, documentId:number, contentHash:string}[]} rows.chunks
 * @param {{id:number, level:string, ownerId:number, model:string, contentHash:string}[]} rows.vectors
 * @param {string}   model
 */
export function findWork( { documents, chunks, vectors }, model ) {
	const vectorOf = new Map( vectors.map( ( v ) => [ vectorKey( v.level, v.ownerId ), v ] ) );
	const chunksOf = new Map();
	for ( const c of chunks ) {
		if ( ! chunksOf.has( c.documentId ) ) {
			chunksOf.set( c.documentId, [] );
		}
		chunksOf.get( c.documentId ).push( c );
	}

	const removals = [];
	const process = [];
	for ( const doc of documents ) {
		if ( doc.attempts >= MAX_ATTEMPTS ) {
			continue;
		}
		if ( doc.status === 'remove' ) {
			removals.push( doc.id );
			continue;
		}
		if ( doc.status !== 'queued' && doc.status !== 'indexed' ) {
			continue;
		}
		const embedDocument = isStale( vectorOf.get( vectorKey( 'document', doc.id ) ), model, doc.embedHash );
		const chunkIds = ( chunksOf.get( doc.id ) ?? [] )
			.filter( ( c ) => isStale( vectorOf.get( vectorKey( 'chunk', c.id ) ), model, c.contentHash ) )
			.map( ( c ) => c.id );
		if ( doc.status === 'queued' || embedDocument || chunkIds.length ) {
			process.push( { id: doc.id, contentHash: doc.contentHash, embedDocument, chunkIds } );
		}
	}

	const owners = {
		document: new Set( documents.map( ( d ) => d.id ) ),
		chunk: new Set( chunks.map( ( c ) => c.id ) ),
	};
	const orphanVectorIds = vectors.filter( ( v ) => ! owners[ v.level ]?.has( v.ownerId ) ).map( ( v ) => v.id );

	return {
		removals,
		process,
		orphanVectorIds,
		hasWork: removals.length > 0 || process.length > 0 || orphanVectorIds.length > 0,
	};
}
