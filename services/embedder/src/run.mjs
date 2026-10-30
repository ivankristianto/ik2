// Entry point. Exit 0 on success, no work or a held lock; 1 when the Run itself failed.
import { readConfig } from './config.mjs';
import { connect } from './db.mjs';
import { runOnce } from './worker.mjs';

async function loadEmbedder() {
	const { loadEmbedder: load } = await import( './embedder.mjs' );
	return load();
}

let db;
try {
	db = await connect( readConfig() );
	await runOnce( { db, loadEmbedder, log: ( line ) => console.log( line ) } );
} catch ( err ) {
	console.error( `ik2-embedder: run failed: ${ err?.stack ?? err }` );
	process.exitCode = 1;
} finally {
	await db?.close().catch( () => {} );
}
