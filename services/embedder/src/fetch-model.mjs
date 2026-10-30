// Image build step: downloads the pinned model revision into MODELS_DIR.
import { mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';

import { MODEL_ID, MODEL_REVISION } from './levels.mjs';
import { MODEL_FILES, modelDir } from './model-files.mjs';

for ( const file of MODEL_FILES ) {
	const url = `https://huggingface.co/${ MODEL_ID }/resolve/${ MODEL_REVISION }/${ file }`;
	const res = await fetch( url );
	if ( ! res.ok ) {
		throw new Error( `GET ${ url }: ${ res.status } ${ res.statusText }` );
	}
	const target = path.join( modelDir(), file );
	await mkdir( path.dirname( target ), { recursive: true } );
	const body = Buffer.from( await res.arrayBuffer() );
	await writeFile( target, body );
	console.log( `${ file } ${ ( body.length / 1048576 ).toFixed( 1 ) } MB` );
}
