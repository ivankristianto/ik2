const REQUIRED = [ 'CORPUS_DB_HOST', 'CORPUS_DB_USER', 'CORPUS_DB_PASSWORD', 'CORPUS_DB_NAME' ];

export function readConfig( env = process.env ) {
	const missing = REQUIRED.filter( ( key ) => env[ key ] === undefined || env[ key ] === '' );
	if ( missing.length ) {
		throw new Error( `Missing environment: ${ missing.join( ', ' ) }` );
	}
	// The host comes from WORDPRESS_DB_HOST, which WordPress lets carry a port: db:3306 or [::1]:3306.
	const hostPort = /^(?:\[([^\]]+)\]|([^:]+)):(\d+)$/.exec( env.CORPUS_DB_HOST );
	const host = hostPort ? hostPort[ 1 ] ?? hostPort[ 2 ] : env.CORPUS_DB_HOST;
	const port = Number( env.CORPUS_DB_PORT || hostPort?.[ 3 ] || 3306 );
	if ( ! Number.isInteger( port ) || port < 1 || port > 65535 ) {
		throw new Error( `CORPUS_DB_PORT is not a port: ${ env.CORPUS_DB_PORT || env.CORPUS_DB_HOST }` );
	}
	const prefix = env.CORPUS_TABLE_PREFIX || 'wp_';
	// The prefix is spliced into table names, so it can't be a bound parameter.
	if ( ! /^[A-Za-z0-9_]+$/.test( prefix ) ) {
		throw new Error( `CORPUS_TABLE_PREFIX has characters outside [A-Za-z0-9_]: ${ prefix }` );
	}
	return {
		host,
		port,
		user: env.CORPUS_DB_USER,
		password: env.CORPUS_DB_PASSWORD,
		database: env.CORPUS_DB_NAME,
		tables: tableNames( prefix ),
	};
}

export function tableNames( prefix ) {
	return {
		documents: `${ prefix }ik2_corpus_documents`,
		chunks: `${ prefix }ik2_corpus_chunks`,
		vectors: `${ prefix }ik2_corpus_vectors`,
		related: `${ prefix }ik2_corpus_related`,
		runs: `${ prefix }ik2_corpus_runs`,
	};
}
