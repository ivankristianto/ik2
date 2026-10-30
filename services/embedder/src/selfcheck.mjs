// Image build step: one embedding per level with remote downloads off, so a
// missing or misplaced model file fails the build instead of the first Run.
import { loadEmbedder } from './embedder.mjs';
import { DIMS } from './levels.mjs';
import { cosine, norm } from './vectors.mjs';

const started = performance.now();
const { embed, countTokens } = await loadEmbedder();

const secure = await embed( 'document', 'Secure Your WordPress Site\nCategory: WordPress\nTags: security, wordpress\n\nHarden logins and update plugins.' );
const hacked = await embed( 'chunk', 'What To Do When Your WordPress Blog Got Hacked > Clean up\n\nRestore from backup and change every password.' );
const radio = await embed( 'chunk', 'Radio Streaming Server in Ubuntu Using IceCast2 > Install\n\nInstall icecast2 and point Winamp at it.' );
const long = await embed( 'chunk', 'word '.repeat( 5000 ) );

for ( const v of [ secure, hacked, radio, long ] ) {
	if ( v.length !== DIMS || Math.abs( norm( v ) - 1 ) > 1e-3 ) {
		throw new Error( `Bad vector: ${ v.length } dims, norm ${ norm( v ) }` );
	}
}
if ( cosine( secure, hacked ) <= cosine( secure, radio ) ) {
	throw new Error( 'WordPress security texts are not closer to each other than to the radio text' );
}

console.log(
	`selfcheck ok: ${ DIMS } dims, cos(security, hacked) ${ cosine( secure, hacked ).toFixed( 3 ) }, ` +
		`cos(security, radio) ${ cosine( secure, radio ).toFixed( 3 ) }, ` +
		`${ countTokens( 'word '.repeat( 5000 ) ) } tokens truncated, ${ Math.round( performance.now() - started ) } ms`
);
