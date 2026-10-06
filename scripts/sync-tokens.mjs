#!/usr/bin/env node
/**
 * Copy the theme's theme.json into design-system/ and generate its CSS custom properties.
 * Usage: pnpm tokens [--check]
 */
import { existsSync, readFileSync, writeFileSync } from 'node:fs';

const SOURCE = 'wp-content/themes/ik2/theme.json';
const COPY = 'design-system/theme.json';
const CSS = 'design-system/tokens.css';

const PRESETS = [
	[ 'color', 'palette', 'color' ],
	[ 'typography', 'fontSizes', 'size', 'font-size' ],
	[ 'typography', 'fontFamilies', 'fontFamily', 'font-family' ],
	[ 'spacing', 'spacingSizes', 'size', 'spacing' ],
	[ 'border', 'radiusSizes', 'size', 'border-radius' ],
	[ 'shadow', 'presets', 'shadow' ],
];

// Same word split as WordPress's _wp_to_kebab_case(): "webApis" -> "web-apis", "2xl" -> "2-xl".
function kebab( value ) {
	return String( value )
		.match( /[A-Z]{2,}(?=[A-Z][a-z]+\d*|\b)|[A-Z]?[a-z]+\d*|[A-Z]|\d+/g )
		.join( '-' )
		.toLowerCase();
}

function presetLines( settings ) {
	return PRESETS.flatMap( ( [ group, list, field, type = group ] ) =>
		( settings[ group ]?.[ list ] ?? [] ).map(
			( preset ) =>
				`--wp--preset--${ type }--${ kebab( preset.slug ) }: ${
					preset[ field ]
				};`
		)
	);
}

function customLines( node, path = [] ) {
	return Object.entries( node ).flatMap( ( [ key, value ] ) =>
		typeof value === 'object'
			? customLines( value, [ ...path, kebab( key ) ] )
			: [
					`--wp--custom--${ [ ...path, kebab( key ) ].join(
						'--'
					) }: ${ value };`,
				]
	);
}

function layoutLines( layout = {} ) {
	return [
		[ 'content-size', layout.contentSize ],
		[ 'wide-size', layout.wideSize ],
	]
		.filter( ( [ , value ] ) => value )
		.map(
			( [ name, value ] ) => `--wp--style--global--${ name }: ${ value };`
		);
}

function renderCss( theme ) {
	const lines = [
		...presetLines( theme.settings ),
		...customLines( theme.settings.custom ?? {} ),
		...layoutLines( theme.settings.layout ),
	];
	return [
		`/* Generated from ${ SOURCE } by scripts/sync-tokens.mjs. Do not edit; run \`pnpm tokens\`. */`,
		'',
		':root {',
		...lines.map( ( line ) => `  ${ line }` ),
		'}',
		'',
	].join( '\n' );
}

const source = readFileSync( SOURCE, 'utf8' );
const outputs = new Map( [
	[ COPY, source ],
	[ CSS, renderCss( JSON.parse( source ) ) ],
] );

if ( process.argv.includes( '--check' ) ) {
	const stale = [ ...outputs ].filter(
		( [ file, content ] ) =>
			! existsSync( file ) || readFileSync( file, 'utf8' ) !== content
	);
	if ( stale.length ) {
		process.stderr.write(
			`tokens: out of date with ${ SOURCE }: ${ stale
				.map( ( [ file ] ) => file )
				.join( ', ' ) }. Run \`pnpm tokens\`.\n`
		);
		process.exit( 1 );
	}
} else {
	outputs.forEach( ( content, file ) => writeFileSync( file, content ) );
}
