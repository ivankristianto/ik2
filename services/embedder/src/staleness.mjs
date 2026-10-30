export const MAX_ATTEMPTS = 3;

// Document statuses, shared with wp-content/plugins/ik2/inc/corpus/queue.php.
export const STATUS = Object.freeze( { queued: 'queued', indexed: 'indexed', remove: 'remove', parked: 'parked' } );

export function isStale( vector, model, ownerHash ) {
	return ! vector || vector.model !== model || vector.contentHash !== ownerHash;
}

function vectorKey( level, ownerId ) {
	return `${ level }:${ ownerId }`;
}

/**
 * Decides what a Run has to do from the rows loadMetadata() returns, without text or blobs.
 * Parked rows and rows at MAX_ATTEMPTS are left alone, so a broken Article can't make every hourly Run load the model.
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
	const toIndex = [];
	for ( const doc of documents ) {
		if ( doc.attempts >= MAX_ATTEMPTS ) {
			continue;
		}
		if ( doc.status === STATUS.remove ) {
			removals.push( doc.id );
			continue;
		}
		if ( doc.status !== STATUS.queued && doc.status !== STATUS.indexed ) {
			continue;
		}
		const embedDocument = isStale( vectorOf.get( vectorKey( 'document', doc.id ) ), model, doc.embedHash );
		const chunkIds = ( chunksOf.get( doc.id ) ?? [] )
			.filter( ( c ) => isStale( vectorOf.get( vectorKey( 'chunk', c.id ) ), model, c.contentHash ) )
			.map( ( c ) => c.id );
		if ( doc.status === STATUS.queued || embedDocument || chunkIds.length ) {
			toIndex.push( { id: doc.id, contentHash: doc.contentHash, embedDocument, chunkIds } );
		}
	}

	const owners = {
		document: new Set( documents.map( ( d ) => d.id ) ),
		chunk: new Set( chunks.map( ( c ) => c.id ) ),
	};
	const orphanVectorIds = vectors.filter( ( v ) => ! owners[ v.level ]?.has( v.ownerId ) ).map( ( v ) => v.id );

	return {
		removals,
		toIndex,
		orphanVectorIds,
		hasWork: removals.length > 0 || toIndex.length > 0 || orphanVectorIds.length > 0,
	};
}
