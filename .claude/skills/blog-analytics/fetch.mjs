#!/usr/bin/env node
/**
 * Read-only Search Console and GA4 data pulls for ivankristianto.com, authenticated as a service account.
 * Usage: node fetch.mjs <check|gsc|ga4|inspect|sitemaps> [options]  (see SKILL.md)
 */
import { createSign } from 'node:crypto';
import { existsSync, readFileSync, writeFileSync } from 'node:fs';
import { homedir } from 'node:os';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { parseArgs } from 'node:util';

const REPO_ROOT = resolve(
	dirname( fileURLToPath( import.meta.url ) ),
	'../../..'
);
const SCOPES = [
	'https://www.googleapis.com/auth/webmasters.readonly',
	'https://www.googleapis.com/auth/analytics.readonly',
];
const GSC = 'https://searchconsole.googleapis.com';
const GA_DATA = 'https://analyticsdata.googleapis.com/v1beta';
const GA_ADMIN = 'https://analyticsadmin.googleapis.com/v1beta';
const GSC_PAGE = 25000;
const GA4_PAGE = 100000;
// Search Console finalises a day's numbers two to three days later.
const GSC_LAG_DAYS = 3;
const GA4_LAG_DAYS = 1;

const OPERATORS = {
	equals: { gsc: 'equals', ga4: 'EXACT' },
	notEquals: { gsc: 'notEquals', ga4: 'EXACT', not: true },
	contains: { gsc: 'contains', ga4: 'CONTAINS' },
	notContains: { gsc: 'notContains', ga4: 'CONTAINS', not: true },
	regex: { gsc: 'includingRegex', ga4: 'PARTIAL_REGEXP' },
	notRegex: { gsc: 'excludingRegex', ga4: 'PARTIAL_REGEXP', not: true },
};

const OPTIONS = {
	start: { type: 'string' },
	end: { type: 'string' },
	days: { type: 'string', default: '28' },
	dimensions: { type: 'string' },
	metrics: { type: 'string' },
	filter: { type: 'string', multiple: true, default: [] },
	type: { type: 'string', default: 'web' },
	'data-state': { type: 'string', default: 'final' },
	'max-rows': { type: 'string' },
	body: { type: 'string' },
	out: { type: 'string' },
	site: { type: 'string' },
	property: { type: 'string' },
};

export function isoDate( date ) {
	return date.toISOString().slice( 0, 10 );
}

export function positiveInt( value, flag ) {
	const number = Number( value );
	if ( ! Number.isInteger( number ) || number < 1 ) {
		throw new Error(
			`--${ flag } must be a positive whole number, got "${ value }"`
		);
	}
	return number;
}

export function dateRange( { start, end, days }, lagDays, today = new Date() ) {
	const endDate = end
		? new Date( `${ end }T00:00:00Z` )
		: new Date(
				Date.UTC(
					today.getUTCFullYear(),
					today.getUTCMonth(),
					today.getUTCDate() - lagDays
				)
			);
	if ( start ) {
		return { startDate: start, endDate: isoDate( endDate ) };
	}
	const count = positiveInt( days, 'days' );
	const startDate = new Date( endDate );
	startDate.setUTCDate( startDate.getUTCDate() - ( count - 1 ) );
	return { startDate: isoDate( startDate ), endDate: isoDate( endDate ) };
}

export function parseFilter( spec ) {
	const match = spec.match( /^([^:]+):([^:]+):(.*)$/ );
	if ( ! match || ! OPERATORS[ match[ 2 ] ] ) {
		throw new Error(
			`bad --filter "${ spec }". Use dimension:operator:value with operator one of ${ Object.keys( OPERATORS ).join( ', ' ) }`
		);
	}
	return { dimension: match[ 1 ], operator: match[ 2 ], value: match[ 3 ] };
}

export function gscFilterGroups( specs ) {
	if ( ! specs.length ) {
		return undefined;
	}
	const filters = specs
		.map( parseFilter )
		.map( ( { dimension, operator, value } ) => ( {
			dimension,
			operator: OPERATORS[ operator ].gsc,
			expression: value,
		} ) );
	return [ { groupType: 'and', filters } ];
}

export function ga4DimensionFilter( specs ) {
	if ( ! specs.length ) {
		return undefined;
	}
	const expressions = specs
		.map( parseFilter )
		.map( ( { dimension, operator, value } ) => {
			const filter = {
				filter: {
					fieldName: dimension,
					stringFilter: {
						matchType: OPERATORS[ operator ].ga4,
						value,
					},
				},
			};
			return OPERATORS[ operator ].not
				? { notExpression: filter }
				: filter;
		} );
	return expressions.length === 1
		? expressions[ 0 ]
		: { andGroup: { expressions } };
}

export function flattenGscRows( rows = [], dimensions ) {
	return rows.map( ( { keys = [], ...metrics } ) => ( {
		...Object.fromEntries(
			dimensions.map( ( name, i ) => [ name, keys[ i ] ] )
		),
		...metrics,
	} ) );
}

export function flattenGa4Rows( report ) {
	const dims = ( report.dimensionHeaders ?? [] ).map( ( h ) => h.name );
	const mets = ( report.metricHeaders ?? [] ).map( ( h ) => h.name );
	return ( report.rows ?? [] ).map( ( row ) => ( {
		...Object.fromEntries(
			dims.map( ( name, i ) => [ name, row.dimensionValues[ i ].value ] )
		),
		...Object.fromEntries(
			mets.map( ( name, i ) => [
				name,
				Number( row.metricValues[ i ].value ),
			] )
		),
	} ) );
}

function list( value ) {
	return value
		.split( ',' )
		.map( ( s ) => s.trim() )
		.filter( Boolean );
}

function fail( message ) {
	process.stderr.write( `blog-analytics: ${ message }\n` );
	process.exit( 1 );
}

function config() {
	const envFile = resolve( REPO_ROOT, '.env' );
	if ( existsSync( envFile ) ) {
		process.loadEnvFile( envFile );
	}
	const keyPath = process.env.IK2_GOOGLE_SA_KEY?.replace(
		/^~(?=\/)/,
		homedir()
	);
	if ( ! keyPath ) {
		fail(
			'IK2_GOOGLE_SA_KEY is not set. Point it at the service account JSON key (see README.md, setup step 7).'
		);
	}
	if ( ! existsSync( keyPath ) ) {
		fail( `no key file at ${ keyPath }` );
	}
	return {
		key: JSON.parse( readFileSync( keyPath, 'utf8' ) ),
		site: process.env.IK2_GSC_SITE,
		property: process.env.IK2_GA4_PROPERTY,
	};
}

async function accessToken( key ) {
	const now = Math.floor( Date.now() / 1000 );
	const encode = ( part ) =>
		Buffer.from( JSON.stringify( part ) ).toString( 'base64url' );
	const unsigned = `${ encode( { alg: 'RS256', typ: 'JWT' } ) }.${ encode( {
		iss: key.client_email,
		scope: SCOPES.join( ' ' ),
		aud: key.token_uri,
		iat: now,
		exp: now + 3600,
	} ) }`;
	const signature = createSign( 'RSA-SHA256' )
		.update( unsigned )
		.sign( key.private_key, 'base64url' );
	const response = await fetch( key.token_uri, {
		method: 'POST',
		body: new URLSearchParams( {
			grant_type: 'urn:ietf:params:oauth:grant-type:jwt-bearer',
			assertion: `${ unsigned }.${ signature }`,
		} ),
	} );
	const json = await response.json();
	if ( ! response.ok ) {
		fail(
			`token exchange failed: ${ json.error_description ?? json.error ?? response.status }`
		);
	}
	return json.access_token;
}

function client( token ) {
	return async function call( url, body ) {
		const response = await fetch( url, {
			method: body ? 'POST' : 'GET',
			headers: {
				authorization: `Bearer ${ token }`,
				'content-type': 'application/json',
			},
			body: body ? JSON.stringify( body ) : undefined,
		} );
		const json = await response.json().catch( () => ( {} ) );
		if ( ! response.ok ) {
			const error = new Error(
				`${ response.status } ${ json.error?.message ?? response.statusText }`
			);
			error.status = response.status;
			throw error;
		}
		return json;
	};
}

function readBody( path ) {
	return path ? JSON.parse( readFileSync( path, 'utf8' ) ) : {};
}

function emit( result, out, summary ) {
	const json = JSON.stringify( result, null, '\t' );
	if ( out ) {
		writeFileSync( out, `${ json }\n` );
		process.stderr.write( `${ summary } -> ${ out }\n` );
	} else {
		process.stdout.write( `${ json }\n` );
		process.stderr.write( `${ summary }\n` );
	}
}

function requireSetting( value, flag, env ) {
	if ( ! value ) {
		fail(
			`no ${ flag }. Pass --${ flag } or set ${ env } (run \`check\` to list what the service account can see).`
		);
	}
	return value;
}

async function check( call, cfg ) {
	const result = {
		serviceAccount: cfg.key.client_email,
		gscSites: null,
		ga4Properties: null,
	};
	try {
		const { siteEntry = [] } = await call( `${ GSC }/webmasters/v3/sites` );
		result.gscSites = siteEntry;
	} catch ( error ) {
		result.gscError = error.message;
	}
	try {
		const { accountSummaries = [] } = await call(
			`${ GA_ADMIN }/accountSummaries`
		);
		result.ga4Properties = accountSummaries.flatMap( ( account ) =>
			( account.propertySummaries ?? [] ).map( ( p ) => ( {
				account: account.displayName,
				property: p.property.replace( 'properties/', '' ),
				name: p.displayName,
			} ) )
		);
	} catch ( error ) {
		result.ga4Error = error.message;
	}
	result.configured = {
		IK2_GSC_SITE: cfg.site ?? null,
		IK2_GA4_PROPERTY: cfg.property ?? null,
	};
	return result;
}

async function gsc( call, cfg, args ) {
	const site = requireSetting(
		args.site ?? cfg.site,
		'site',
		'IK2_GSC_SITE'
	);
	const dimensions = list( args.dimensions ?? 'query,page' );
	const maxRows = positiveInt( args[ 'max-rows' ] ?? 100000, 'max-rows' );
	const request = {
		...dateRange( args, GSC_LAG_DAYS ),
		dimensions,
		type: args.type,
		dataState: args[ 'data-state' ],
		dimensionFilterGroups: gscFilterGroups( args.filter ),
		...readBody( args.body ),
	};
	const url = `${ GSC }/webmasters/v3/sites/${ encodeURIComponent( site ) }/searchAnalytics/query`;
	const rows = [];
	while ( rows.length < maxRows ) {
		const rowLimit = Math.min( GSC_PAGE, maxRows - rows.length );
		const page = await call( url, {
			...request,
			rowLimit,
			startRow: rows.length,
		} );
		rows.push( ...flattenGscRows( page.rows, request.dimensions ?? [] ) );
		if ( ( page.rows?.length ?? 0 ) < rowLimit ) {
			break;
		}
	}
	return {
		result: { source: 'gsc', site, request, rowCount: rows.length, rows },
		summary: `gsc ${ site }: ${ rows.length } rows, ${ request.startDate }..${ request.endDate }, by ${ ( request.dimensions ?? [] ).join( ',' ) || '(total)' }`,
	};
}

async function ga4( call, cfg, args ) {
	const property = requireSetting(
		args.property ?? cfg.property,
		'property',
		'IK2_GA4_PROPERTY'
	);
	const maxRows = positiveInt( args[ 'max-rows' ] ?? 100000, 'max-rows' );
	const request = {
		dateRanges: [ dateRange( args, GA4_LAG_DAYS ) ],
		dimensions: list( args.dimensions ?? 'pagePath' ).map( ( name ) => ( {
			name,
		} ) ),
		metrics: list(
			args.metrics ?? 'screenPageViews,activeUsers,sessions'
		).map( ( name ) => ( { name } ) ),
		dimensionFilter: ga4DimensionFilter( args.filter ),
		...readBody( args.body ),
	};
	// After the merge, so a --body that swaps the metrics still sorts by one it requested.
	request.orderBys ??= [
		{ metric: { metricName: request.metrics[ 0 ].name }, desc: true },
	];
	const url = `${ GA_DATA }/properties/${ property }:runReport`;
	const rows = [];
	let total = Infinity;
	let metadata;
	while ( rows.length < Math.min( maxRows, total ) ) {
		const limit = Math.min( GA4_PAGE, maxRows - rows.length );
		const page = await call( url, {
			...request,
			limit,
			offset: rows.length,
		} );
		total = page.rowCount ?? 0;
		metadata ??= page.metadata;
		const pageRows = flattenGa4Rows( page );
		rows.push( ...pageRows );
		if ( pageRows.length < limit ) {
			break;
		}
	}
	const [ range ] = request.dateRanges;
	return {
		result: {
			source: 'ga4',
			property,
			request,
			rowCount: rows.length,
			totalRows: total,
			metadata,
			rows,
		},
		summary: `ga4 ${ property }: ${ rows.length } of ${ total } rows, ${ range.startDate }..${ range.endDate }`,
	};
}

async function inspect( call, cfg, args, urls ) {
	const site = requireSetting(
		args.site ?? cfg.site,
		'site',
		'IK2_GSC_SITE'
	);
	if ( ! urls.length ) {
		fail( 'inspect needs one or more page URLs' );
	}
	const results = [];
	for ( const inspectionUrl of urls ) {
		try {
			const { inspectionResult } = await call(
				`${ GSC }/v1/urlInspection/index:inspect`,
				{
					inspectionUrl,
					siteUrl: site,
				}
			);
			results.push( { url: inspectionUrl, ...inspectionResult } );
		} catch ( error ) {
			results.push( { url: inspectionUrl, error: error.message } );
		}
	}
	return {
		result: { source: 'gsc-inspect', site, results },
		summary: `inspect ${ site }: ${ results.length } URLs, ${ results.filter( ( r ) => r.error ).length } errors`,
	};
}

async function sitemaps( call, cfg, args ) {
	const site = requireSetting(
		args.site ?? cfg.site,
		'site',
		'IK2_GSC_SITE'
	);
	const { sitemap = [] } = await call(
		`${ GSC }/webmasters/v3/sites/${ encodeURIComponent( site ) }/sitemaps`
	);
	return {
		result: { source: 'gsc-sitemaps', site, sitemaps: sitemap },
		summary: `sitemaps ${ site }: ${ sitemap.length }`,
	};
}

async function main() {
	const { values: args, positionals } = parseArgs( {
		options: OPTIONS,
		allowPositionals: true,
	} );
	const [ command, ...rest ] = positionals;
	const commands = { gsc, ga4, sitemaps };
	if (
		! [ 'check', 'inspect', ...Object.keys( commands ) ].includes( command )
	) {
		fail( 'usage: fetch.mjs <check|gsc|ga4|inspect|sitemaps> [options]' );
	}
	const cfg = config();
	const call = client( await accessToken( cfg.key ) );
	try {
		if ( command === 'check' ) {
			emit( await check( call, cfg ), args.out, 'check done' );
			return;
		}
		const { result, summary } =
			command === 'inspect'
				? await inspect( call, cfg, args, rest )
				: await commands[ command ]( call, cfg, args );
		emit( result, args.out, summary );
	} catch ( error ) {
		fail(
			error.status === 403
				? `${ error.message }\nIs ${ cfg.key.client_email } added as a user on that property?`
				: error.message
		);
	}
}

if ( import.meta.main ) {
	await main();
}
