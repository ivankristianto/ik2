import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const frontPage = readFileSync(
	'wp-content/themes/ik2/templates/front-page.html',
	'utf8'
);

const patternFiles = [
	'wp-content/themes/ik2/patterns/home-evergreen-guides.php',
	'wp-content/themes/ik2/patterns/home-featured-topics.php',
	'wp-content/themes/ik2/patterns/home-hero.php',
	'wp-content/themes/ik2/patterns/home-latest-notes.php',
	'wp-content/themes/ik2/patterns/home-speaking-preview.php',
];

const blockNames = [
	'ik2/home-evergreen-guides',
	'ik2/home-featured-topics',
	'ik2/home-hero',
	'ik2/home-latest-notes',
	'ik2/home-speaking-preview',
];

for ( const blockName of blockNames ) {
	assert.match(
		frontPage,
		new RegExp( `<!-- wp:${ blockName.replace( '/', '\\/' ) }\\b` ),
		`Expected front-page.html to render ${ blockName } directly so the Site Editor uses the server-rendered block preview.`
	);
}

for ( let index = 0; index < patternFiles.length; index += 1 ) {
	const pattern = readFileSync( patternFiles[ index ], 'utf8' );
	const blockName = blockNames[ index ];

	assert.match(
		pattern,
		new RegExp( `<!-- wp:${ blockName.replace( '/', '\\/' ) }\\s*\\/-->` ),
		`Expected ${ patternFiles[ index ] } to be a thin wrapper around ${ blockName }.`
	);

	assert.doesNotMatch(
		pattern,
		/<!-- wp:html\b|WP_Query|get_term_by|get_post_meta|file_exists|wp_count_posts|taxQuery/s,
		`Expected ${ patternFiles[ index ] } to avoid dynamic logic and Custom HTML once ${ blockName } exists.`
	);
}
