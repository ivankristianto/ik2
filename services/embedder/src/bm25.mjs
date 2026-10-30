export const K1 = 1.2;
export const B = 0.75;

export const STOPWORDS = new Set(
	(
		'a about an and are as at be by can did do does for from has have how i in is it its many much of on or our ' +
		'that the their there this to was we what when where which who why will with you your my me so if but not ' +
		'just all one also more get use using'
	).split( ' ' )
);

// Keeps dotted and symbol terms whole: node.js, c++, c#, wp-cli.
const TERM = /[a-z0-9][a-z0-9.+#-]*[a-z0-9+#]|[a-z0-9]/g;

export function stem( term ) {
	if ( term.length > 4 && term.endsWith( 's' ) && ! term.endsWith( 'ss' ) ) {
		return term.slice( 0, -1 );
	}
	return term;
}

export function tokenize( text ) {
	const terms = [];
	for ( const raw of text.toLowerCase().match( TERM ) ?? [] ) {
		if ( ! STOPWORDS.has( raw ) ) {
			terms.push( stem( raw ) );
		}
	}
	return terms;
}

/** @param {Map<number, string>} docs id to text */
export function buildIndex( docs ) {
	const termFreqs = new Map();
	const lengths = new Map();
	const docFreq = new Map();
	let total = 0;
	for ( const [ id, text ] of docs ) {
		const tf = new Map();
		const terms = tokenize( text );
		for ( const t of terms ) {
			tf.set( t, ( tf.get( t ) ?? 0 ) + 1 );
		}
		for ( const t of tf.keys() ) {
			docFreq.set( t, ( docFreq.get( t ) ?? 0 ) + 1 );
		}
		termFreqs.set( id, tf );
		lengths.set( id, terms.length );
		total += terms.length;
	}
	const avgLength = docs.size ? total / docs.size : 0;
	return { termFreqs, lengths, docFreq, avgLength, size: docs.size };
}

/** @return {Map<number, number>} id to score, only ids that matched */
export function score( index, queryTerms, skipId = null ) {
	const scores = new Map();
	// Unique query terms, as in the measured prototype: a repeated term adds no weight.
	for ( const term of new Set( queryTerms ) ) {
		const df = index.docFreq.get( term );
		if ( ! df ) {
			continue;
		}
		const idf = Math.log( 1 + ( index.size - df + 0.5 ) / ( df + 0.5 ) );
		for ( const [ id, tfs ] of index.termFreqs ) {
			const tf = tfs.get( term );
			if ( ! tf || id === skipId ) {
				continue;
			}
			const lengthNorm = 1 - B + ( B * index.lengths.get( id ) ) / index.avgLength;
			const s = ( idf * tf * ( K1 + 1 ) ) / ( tf + K1 * lengthNorm );
			scores.set( id, ( scores.get( id ) ?? 0 ) + s );
		}
	}
	return scores;
}

export function topIds( scores, n ) {
	return [ ...scores ]
		.sort( ( a, b ) => b[ 1 ] - a[ 1 ] || a[ 0 ] - b[ 0 ] )
		.slice( 0, n )
		.map( ( [ id ] ) => id );
}

export function search( index, queryTerms, n, skipId = null ) {
	return topIds( score( index, queryTerms, skipId ), n );
}
