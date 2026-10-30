#!/usr/bin/env node
/**
 * Relevance eval for the Corpus related picks against the old category picks.
 * Usage: node scripts/corpus-eval.mjs pairs [--out <dir>] [--reselect] | report <grades.json> [--out <dir>]
 */
import { execFileSync } from 'node:child_process';
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const ROOT = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' );
const FIXTURE = join( ROOT, 'tests/fixtures/corpus-eval.json' );
const DEFAULT_OUT = join( tmpdir(), 'ik2-corpus-eval' );

export const SEED = 20261030;
export const FIXED_IDS = [ 503, 494, 985, 576, 536, 522, 701, 1981 ];
export const EVAL_SIZE = 30;
export const LIMIT = 4;
// Mirrors RELATED_MIN_SCORE in wp-content/plugins/ik2/inc/corpus/related.php; a test keeps them equal.
export const CURRENT_FLOOR = 0.65;
export const ZERO_DROP_MIN = 0.8;
export const TWO_DROP_MAX = 0.1;
const EXCLUDED_CATEGORIES = [ 'Uncategorized' ];

export function mulberry32( seed ) {
	let a = seed >>> 0;
	return () => {
		a = ( a + 0x6d2b79f5 ) >>> 0;
		let t = a;
		t = Math.imul( t ^ ( t >>> 15 ), t | 1 );
		t ^= t + Math.imul( t ^ ( t >>> 7 ), t | 61 );
		return ( ( t ^ ( t >>> 14 ) ) >>> 0 ) / 4294967296;
	};
}

export function seededShuffle( items, rng ) {
	const out = [ ...items ];
	for ( let i = out.length - 1; i > 0; i-- ) {
		const j = Math.floor( rng() * ( i + 1 ) );
		[ out[ i ], out[ j ] ] = [ out[ j ], out[ i ] ];
	}
	return out;
}

/**
 * Extra picks per category, summing to total minus fixed: `total` split by category size
 * (largest remainder, at least one each), less what the fixed Articles already cover.
 */
export function allocate( counts, total, fixedCounts = {} ) {
	const names = Object.keys( counts ).sort();
	const size = names.reduce( ( sum, name ) => sum + counts[ name ], 0 );
	const target = {};
	const remainders = [];
	let assigned = 0;
	for ( const name of names ) {
		const exact = ( counts[ name ] * total ) / size;
		target[ name ] = Math.max( 1, Math.floor( exact ) );
		assigned += target[ name ];
		remainders.push( [ exact - Math.floor( exact ), name ] );
	}
	remainders.sort(
		( a, b ) => b[ 0 ] - a[ 0 ] || a[ 1 ].localeCompare( b[ 1 ] )
	);
	for ( let i = 0; assigned < total && i < remainders.length; i++ ) {
		target[ remainders[ i ][ 1 ] ]++;
		assigned++;
	}

	const fixedTotal = Object.values( fixedCounts ).reduce(
		( a, b ) => a + b,
		0
	);
	const extra = total - fixedTotal;
	const need = {};
	const room = ( name ) =>
		counts[ name ] - ( fixedCounts[ name ] ?? 0 ) - need[ name ];
	for ( const name of names ) {
		need[ name ] = Math.min(
			Math.max( 0, target[ name ] - ( fixedCounts[ name ] ?? 0 ) ),
			counts[ name ] - ( fixedCounts[ name ] ?? 0 )
		);
	}
	const bySize = [ ...names ].sort(
		( a, b ) => counts[ b ] - counts[ a ] || a.localeCompare( b )
	);
	let sum = Object.values( need ).reduce( ( a, b ) => a + b, 0 );
	while ( sum > extra ) {
		const name = [ ...names ].sort(
			( a, b ) => need[ b ] - need[ a ] || counts[ b ] - counts[ a ]
		)[ 0 ];
		need[ name ]--;
		sum--;
	}
	while ( sum < extra ) {
		const name = bySize.find( ( n ) => room( n ) > 0 );
		if ( ! name ) {
			break;
		}
		need[ name ]++;
		sum++;
	}
	return need;
}

/** The fixed Articles plus seeded picks per first category, from a catalog of `{ id, category }`. */
export function selectEvalSet( catalog, fixedIds, total, seed ) {
	const eligible = catalog.filter(
		( a ) => ! EXCLUDED_CATEGORIES.includes( a.category )
	);
	const counts = {};
	const fixedCounts = {};
	for ( const a of eligible ) {
		counts[ a.category ] = ( counts[ a.category ] ?? 0 ) + 1;
		if ( fixedIds.includes( a.id ) ) {
			fixedCounts[ a.category ] = ( fixedCounts[ a.category ] ?? 0 ) + 1;
		}
	}
	const allocation = allocate( counts, total, fixedCounts );
	const rng = mulberry32( seed );
	const ids = [ ...fixedIds ];
	for ( const name of Object.keys( allocation ).sort() ) {
		const pool = eligible
			.filter(
				( a ) => a.category === name && ! fixedIds.includes( a.id )
			)
			.map( ( a ) => a.id )
			.sort( ( a, b ) => a - b );
		ids.push(
			...seededShuffle( pool, rng ).slice( 0, allocation[ name ] )
		);
	}
	return { ids, allocation };
}

/**
 * Replay get_related() over all stored rows, then the theme's top-up from `newest`
 * (the newest posts in the first category, self excluded) for one Article.
 */
export function simulateBlock( selfId, rows, newest, floor, limit = LIMIT ) {
	const picks = [ ...rows ]
		.sort( ( a, b ) => a.position - b.position )
		.filter( ( r ) => r.score >= floor && r.related_id !== selfId )
		.slice( 0, limit )
		.map( ( r ) => ( {
			id: r.related_id,
			from: 'new',
			position: r.position,
			score: r.score,
		} ) );
	const taken = new Set( [ selfId, ...picks.map( ( p ) => p.id ) ] );
	for ( const id of newest ) {
		if ( picks.length >= limit ) {
			break;
		}
		if ( ! taken.has( id ) ) {
			picks.push( { id, from: 'fallback' } );
			taken.add( id );
		}
	}
	return picks;
}

export function needsFallback( selfId, rows, floor, limit = LIMIT ) {
	return (
		rows.filter( ( r ) => r.score >= floor && r.related_id !== selfId )
			.length < limit
	);
}

export function floors( from = 0.5, to = 0.85, step = 0.025 ) {
	const out = [];
	const n = Math.round( ( to - from ) / step );
	for ( let k = 0; k <= n; k++ ) {
		out.push( Number( ( from + k * step ).toFixed( 3 ) ) );
	}
	return out;
}

/** Gain 2^g - 1, discount log2(rank + 1). Ungraded slots pass as null and count as 0. */
export function dcg( grades ) {
	return grades.reduce(
		( sum, g, i ) => sum + ( 2 ** ( g ?? 0 ) - 1 ) / Math.log2( i + 2 ),
		0
	);
}

/** @return {number|null} null when every pooled grade is 0, so no ideal exists. */
export function ndcg( grades, pooled, k = LIMIT ) {
	const ideal = dcg( [ ...pooled ].sort( ( a, b ) => b - a ).slice( 0, k ) );
	return ideal > 0 ? dcg( grades.slice( 0, k ) ) / ideal : null;
}

/** @param {Array<number|null>} grades */
export function summarise( grades ) {
	const graded = grades.filter( ( g ) => g !== null && g !== undefined );
	const n = graded.length;
	const share = ( v ) =>
		n ? graded.filter( ( g ) => g === v ).length / n : null;
	return {
		n,
		ungraded: grades.length - n,
		mean: n ? graded.reduce( ( a, b ) => a + b, 0 ) / n : null,
		twos: share( 2 ),
		zeros: share( 0 ),
	};
}

/** One row per floor over the graded NEW picks, counting fallbacks over `articles`. */
export function floorSweep( picks, articles, floorList = floors() ) {
	const zeros = picks.filter( ( p ) => p.grade === 0 ).length;
	const twos = picks.filter( ( p ) => p.grade === 2 ).length;
	return floorList.map( ( floor ) => {
		const kept = picks.filter( ( p ) => p.score >= floor );
		const keptZeros = kept.filter( ( p ) => p.grade === 0 ).length;
		const keptTwos = kept.filter( ( p ) => p.grade === 2 ).length;
		return {
			floor,
			kept: kept.length,
			zerosKept: keptZeros,
			twosDropped: twos - keptTwos,
			// No zeros means there is nothing to drop, which meets the rule.
			zeroDropShare: zeros ? ( zeros - keptZeros ) / zeros : 1,
			twoDropShare: twos ? ( twos - keptTwos ) / twos : 0,
			meanKept: kept.length
				? kept.reduce( ( a, p ) => a + p.grade, 0 ) / kept.length
				: null,
			fallbackArticles: articles.filter( ( a ) =>
				needsFallback( a.id, a.rows, floor )
			).length,
		};
	} );
}

/** @return {{floor:number|null, closest:number|null}} */
export function recommendFloor(
	sweep,
	zeroDropMin = ZERO_DROP_MIN,
	twoDropMax = TWO_DROP_MAX
) {
	const ok = sweep.find(
		( r ) => r.zeroDropShare >= zeroDropMin && r.twoDropShare <= twoDropMax
	);
	if ( ok ) {
		return { floor: ok.floor, closest: null };
	}
	const safe = sweep.filter( ( r ) => r.twoDropShare <= twoDropMax );
	const best = safe.length
		? safe.reduce( ( a, b ) =>
				b.zeroDropShare > a.zeroDropShare ? b : a
			)
		: sweep[ 0 ];
	return { floor: null, closest: best ? best.floor : null };
}

export const pairKey = ( source, candidate ) => `${ source }:${ candidate }`;

const PHP = String.raw`<?php
$mode = $args[0] ?? '';
$ids  = array_map( 'intval', array_slice( $args, 1 ) );
$out  = [];

$words = function ( $id ) {
	$text = strip_shortcodes( (string) get_post_field( 'post_content', $id ) );
	$text = html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$list = preg_split( '/\s+/u', trim( $text ), -1, PREG_SPLIT_NO_EMPTY );
	return implode( ' ', array_slice( $list, 0, 120 ) );
};
$info = function ( $id ) use ( $words ) {
	return [
		'title'      => html_entity_decode( (string) get_post_field( 'post_title', $id ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
		'categories' => array_map( 'html_entity_decode', wp_list_pluck( get_the_category( $id ), 'name' ) ),
		'tags'       => array_map( 'html_entity_decode', wp_list_pluck( (array) get_the_tags( $id ) ?: [], 'name' ) ),
		'opening'    => $words( $id ),
	];
};

if ( 'catalog' === $mode ) {
	$all = get_posts( [ 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC' ] );
	foreach ( $all as $id ) {
		$cats  = wp_get_post_categories( $id );
		$out[] = [ 'id' => (int) $id, 'category' => $cats ? get_cat_name( $cats[0] ) : '' ];
	}
}

if ( 'collect' === $mode ) {
	global $wpdb;
	$table = $wpdb->prefix . 'ik2_corpus_related';
	$rows  = $wpdb->get_results( "SELECT post_id, position, related_id, score FROM $table ORDER BY post_id, position", ARRAY_A );
	$related = [];
	foreach ( $rows as $r ) {
		$related[ (int) $r['post_id'] ][] = [ 'position' => (int) $r['position'], 'related_id' => (int) $r['related_id'], 'score' => (float) $r['score'] ];
	}
	$articles = [];
	$needed   = [];
	foreach ( $ids as $id ) {
		$cats = wp_get_post_categories( $id );
		// Same args as filter_related_query() in the theme, with room for the top-up to skip kept picks.
		$query = [
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => 8,
			'post__not_in'        => [ $id ],
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'fields'              => 'ids',
		];
		if ( $cats ) {
			$query['category__in'] = [ (int) $cats[0] ];
		}
		$newest = array_map( 'intval', get_posts( $query ) );
		$articles[ $id ] = [
			'first_category' => $cats ? get_cat_name( $cats[0] ) : '',
			'rows'           => $related[ $id ] ?? [],
			'newest'         => $newest,
		];
		$needed[] = $id;
		$needed   = array_merge( $needed, $newest, wp_list_pluck( $related[ $id ] ?? [], 'related_id' ) );
	}
	$posts = [];
	foreach ( array_unique( $needed ) as $id ) {
		$posts[ $id ] = $info( $id );
	}
	$out = [ 'articles' => $articles, 'posts' => $posts, 'related' => $related ];
}

echo '@@JSON@@' . wp_json_encode( $out ) . '@@END@@';
`;

function fail( message ) {
	process.stderr.write( `corpus-eval: ${ message }\n` );
	process.exit( 1 );
}

function wp( mode, ids = [] ) {
	let output;
	try {
		output = execFileSync(
			'docker',
			[
				'compose',
				'exec',
				'-T',
				'wp-cli',
				'wp',
				'eval-file',
				'-',
				mode,
				...ids.map( String ),
			],
			{
				cwd: ROOT,
				input: PHP,
				encoding: 'utf8',
				maxBuffer: 64 * 1024 * 1024,
			}
		);
	} catch ( error ) {
		fail(
			`wp eval-file ${ mode } failed:\n${ error.stderr || error.message }`
		);
	}
	const match = output.match( /@@JSON@@([\s\S]*)@@END@@/ );
	if ( ! match ) {
		fail(
			`no JSON in wp eval-file ${ mode } output:\n${ output.slice( 0, 500 ) }`
		);
	}
	return JSON.parse( match[ 1 ] );
}

function option( args, name, fallback ) {
	const i = args.indexOf( name );
	return i >= 0 && args[ i + 1 ] ? args[ i + 1 ] : fallback;
}

function loadEvalSet( reselect ) {
	if ( existsSync( FIXTURE ) && ! reselect ) {
		return JSON.parse( readFileSync( FIXTURE, 'utf8' ) );
	}
	const { ids, allocation } = selectEvalSet(
		wp( 'catalog' ),
		FIXED_IDS,
		EVAL_SIZE,
		SEED
	);
	const fixture = {
		note: 'Articles for scripts/corpus-eval.mjs. The first 8 are fixed; the rest were drawn per first category with the seed. Regenerate only with --reselect.',
		seed: SEED,
		fixed: FIXED_IDS,
		allocation,
		ids,
	};
	mkdirSync( dirname( FIXTURE ), { recursive: true } );
	writeFileSync( FIXTURE, `${ JSON.stringify( fixture, null, '\t' ) }\n` );
	return fixture;
}

function pairsCommand( args ) {
	const out = resolve( option( args, '--out', DEFAULT_OUT ) );
	const evalSet = loadEvalSet( args.includes( '--reselect' ) );
	const data = wp( 'collect', evalSet.ids );

	const pairs = new Map();
	const add = ( source, candidate, system, detail ) => {
		const key = pairKey( source, candidate );
		if ( ! pairs.has( key ) ) {
			pairs.set( key, { source, candidate, systems: {} } );
		}
		pairs.get( key ).systems[ system ] = detail;
	};
	for ( const id of evalSet.ids ) {
		const article = data.articles[ id ];
		if ( ! article || article.rows.length === 0 ) {
			fail( `Article ${ id } has no rows in the related table` );
		}
		article.rows
			.filter( ( r ) => r.position <= LIMIT )
			.forEach( ( r ) =>
				add( id, r.related_id, 'new', {
					position: r.position,
					score: r.score,
				} )
			);
		article.newest
			.slice( 0, LIMIT )
			.forEach( ( candidate, i ) =>
				add( id, candidate, 'baseline', { position: i + 1 } )
			);
	}

	const shuffled = seededShuffle(
		[ ...pairs.values() ],
		mulberry32( SEED + 1 )
	);
	const width = String( shuffled.length ).length;
	const blind = [];
	const key = {};
	shuffled.forEach( ( pair, i ) => {
		const pairId = `p${ String( i + 1 ).padStart( width, '0' ) }`;
		blind.push( {
			pair_id: pairId,
			source: data.posts[ pair.source ],
			candidate: data.posts[ pair.candidate ],
		} );
		key[ pairId ] = pair;
	} );

	mkdirSync( out, { recursive: true } );
	writeFileSync(
		join( out, 'pairs.json' ),
		`${ JSON.stringify(
			{
				instructions:
					'Each item pairs a source blog post with a candidate post that could be shown under it as further reading. Grade every pair_id: 2 = a reader of the source would want this next, 1 = loosely related, 0 = unrelated. Return a JSON object {"p001": 2, ...}.',
				count: blind.length,
				pairs: blind,
			},
			null,
			'\t'
		) }\n`
	);

	const titles = Object.fromEntries(
		Object.entries( data.posts ).map( ( [ id, p ] ) => [ id, p.title ] )
	);
	writeFileSync(
		join( out, 'key.json' ),
		`${ JSON.stringify(
			{
				seed: SEED,
				eval_ids: evalSet.ids,
				fixed_ids: evalSet.fixed,
				pairs: key,
				articles: data.articles,
				titles,
				corpus: Object.entries( data.related ).map(
					( [ id, rows ] ) => ( {
						id: Number( id ),
						rows,
					} )
				),
			},
			null,
			'\t'
		) }\n`
	);

	const all = [ ...pairs.values() ];
	const both = all.filter(
		( p ) => p.systems.new && p.systems.baseline
	).length;
	const newSlots = all.filter( ( p ) => p.systems.new ).length;
	const baseSlots = all.filter( ( p ) => p.systems.baseline ).length;
	const thin = evalSet.ids
		.filter( ( id ) => data.articles[ id ].newest.length < LIMIT )
		.map( ( id ) => `${ id } has ${ data.articles[ id ].newest.length }` );
	process.stdout.write(
		[
			`Eval set: ${ evalSet.ids.length } Articles (${ FIXTURE.replace( `${ ROOT }/`, '' ) })`,
			`Pairs: ${ blind.length } unique from ${ newSlots } NEW + ${ baseSlots } BASELINE slots, ${ both } shared by both`,
			thin.length
				? `BASELINE short of ${ LIMIT } (category too small): ${ thin.join( ', ' ) }`
				: `BASELINE has ${ LIMIT } picks for every Article`,
			`Blind file: ${ join( out, 'pairs.json' ) }`,
			`Key file:   ${ join( out, 'key.json' ) }`,
			'',
		].join( '\n' )
	);
}

const fmt = ( n, digits = 2 ) =>
	n === null || n === undefined ? 'n/a' : n.toFixed( digits );
const pct = ( n ) =>
	n === null || n === undefined ? 'n/a' : `${ Math.round( n * 100 ) }%`;
const cell = ( s, max = 60 ) => {
	const t = String( s ).replace( /\|/g, '/' );
	return t.length > max ? `${ t.slice( 0, max - 3 ) }...` : t;
};

function reportCommand( args ) {
	const gradesPath = args.find( ( a ) => ! a.startsWith( '--' ) );
	if ( ! gradesPath ) {
		fail(
			'usage: node scripts/corpus-eval.mjs report <grades.json> [--out <dir>]'
		);
	}
	const out = resolve(
		option( args, '--out', dirname( resolve( gradesPath ) ) )
	);
	const key = JSON.parse( readFileSync( join( out, 'key.json' ), 'utf8' ) );
	const grades = JSON.parse( readFileSync( gradesPath, 'utf8' ) );

	const byPair = new Map();
	const problems = [];
	for ( const [ pairId, grade ] of Object.entries( grades ) ) {
		if ( ! key.pairs[ pairId ] ) {
			problems.push( `unknown pair id ${ pairId }` );
		} else if ( ! [ 0, 1, 2 ].includes( grade ) ) {
			problems.push(
				`${ pairId } has grade ${ JSON.stringify( grade ) }, expected 0, 1 or 2`
			);
		} else {
			const p = key.pairs[ pairId ];
			byPair.set( pairKey( p.source, p.candidate ), grade );
		}
	}
	const missing = Object.keys( key.pairs ).filter(
		( id ) => ! ( id in grades )
	);
	const gradeOf = ( source, candidate ) =>
		byPair.get( pairKey( source, candidate ) ) ?? null;
	const title = ( id ) => key.titles[ id ] ?? `#${ id }`;

	const lists = {};
	const pooled = {};
	for ( const id of key.eval_ids ) {
		const a = key.articles[ id ];
		lists[ id ] = {
			new: a.rows
				.filter( ( r ) => r.position <= LIMIT )
				.sort( ( x, y ) => x.position - y.position )
				.map( ( r ) => r.related_id ),
			baseline: a.newest.slice( 0, LIMIT ),
		};
		pooled[ id ] = [
			...new Set( [ ...lists[ id ].new, ...lists[ id ].baseline ] ),
		]
			.map( ( c ) => gradeOf( id, c ) )
			.filter( ( g ) => g !== null );
	}

	const newPicks = [];
	for ( const id of key.eval_ids ) {
		for ( const r of key.articles[ id ].rows.filter(
			( row ) => row.position <= LIMIT
		) ) {
			const grade = gradeOf( id, r.related_id );
			if ( grade !== null ) {
				newPicks.push( { source: id, score: r.score, grade } );
			}
		}
	}
	const evalArticles = key.eval_ids.map( ( id ) => ( {
		id,
		rows: key.articles[ id ].rows,
	} ) );
	const sweep = floorSweep( newPicks, evalArticles );
	const corpusFallback = new Map(
		floors().map( ( f ) => [
			f,
			key.corpus.filter( ( a ) => needsFallback( a.id, a.rows, f ) )
				.length,
		] )
	);
	const { floor: recommended, closest } = recommendFloor( sweep );
	const chosen = recommended ?? closest;

	const finalLists = ( floor ) =>
		Object.fromEntries(
			key.eval_ids.map( ( id ) => [
				id,
				simulateBlock(
					id,
					key.articles[ id ].rows,
					key.articles[ id ].newest,
					floor
				),
			] )
		);
	const systems = [
		[ 'NEW (top 4, no floor)', ( id ) => lists[ id ].new ],
		[
			'BASELINE (newest in first category)',
			( id ) => lists[ id ].baseline,
		],
	];
	const finals = {};
	for ( const floor of [
		...new Set( [ CURRENT_FLOOR, chosen ].filter( ( f ) => f !== null ) ),
	] ) {
		finals[ floor ] = finalLists( floor );
		systems.push( [
			`FINAL @ ${ floor.toFixed( 3 ) }`,
			( id ) => finals[ floor ][ id ].map( ( p ) => p.id ),
		] );
	}

	const lines = [];
	const push = ( ...l ) => lines.push( ...l );
	push( '# Corpus relevance eval', '' );
	push(
		`${ key.eval_ids.length } Articles, ${ Object.keys( key.pairs ).length } blind pairs, ${ byPair.size } graded, ${ missing.length } missing.`,
		'Grades: 0 unrelated, 1 loosely related, 2 a reader of the source would want this next.',
		''
	);
	if ( problems.length ) {
		push(
			'**Grade file problems:**',
			'',
			...problems.map( ( p ) => `- ${ p }` ),
			''
		);
	}

	push( '## Systems', '' );
	push(
		'nDCG@4 uses gain 2^grade - 1 and discount log2(rank + 1). The ideal list for each Article is its best 4 grades pooled across every graded pair for that Article. Articles whose pooled grades are all 0 are left out of nDCG. Ungraded picks count as 0 in nDCG and are left out of the other columns. A list shorter than 4 (an Article alone in its category gets no BASELINE picks) scores the missing slots as 0, which matches the old block hiding itself.',
		''
	);
	push(
		'| System | Graded | Ungraded | Mean grade | Share of 2s | Share of 0s | nDCG@4 | Articles in nDCG |',
		'| --- | --- | --- | --- | --- | --- | --- | --- |'
	);
	for ( const [ name, list ] of systems ) {
		const all = key.eval_ids.flatMap( ( id ) =>
			list( id ).map( ( c ) => gradeOf( id, c ) )
		);
		const s = summarise( all );
		const scores = key.eval_ids
			.map( ( id ) =>
				ndcg(
					list( id ).map( ( c ) => gradeOf( id, c ) ),
					pooled[ id ]
				)
			)
			.filter( ( v ) => v !== null );
		const mean = scores.length
			? scores.reduce( ( a, b ) => a + b, 0 ) / scores.length
			: null;
		push(
			`| ${ name } | ${ s.n } | ${ s.ungraded } | ${ fmt( s.mean ) } | ${ pct( s.twos ) } | ${ pct( s.zeros ) } | ${ fmt( mean, 3 ) } | ${ scores.length } |`
		);
	}
	push( '' );

	push( '## Floor sweep for NEW', '' );
	const zeros = newPicks.filter( ( p ) => p.grade === 0 ).length;
	const twos = newPicks.filter( ( p ) => p.grade === 2 ).length;
	push(
		`Over the ${ newPicks.length } graded NEW top-4 picks (${ zeros } graded 0, ${ twos } graded 2). An Article needs the category fallback when fewer than 4 of its 8 stored rows clear the floor, which is how get_related() and the theme fill the block.`,
		''
	);
	push(
		'| Floor | Picks kept | Zeros kept | Twos dropped | Mean grade of kept | Eval Articles needing fallback | All Articles needing fallback |',
		'| --- | --- | --- | --- | --- | --- | --- |'
	);
	for ( const r of sweep ) {
		const mark = r.floor === recommended ? ' (recommended)' : '';
		push(
			`| ${ r.floor.toFixed( 3 ) }${ mark } | ${ r.kept } | ${ r.zerosKept } | ${ r.twosDropped } | ${ fmt( r.meanKept ) } | ${ r.fallbackArticles } of ${ evalArticles.length } | ${ corpusFallback.get( r.floor ) } of ${ key.corpus.length } |`
		);
	}
	push( '' );
	push(
		`Rule: the recommended floor is the lowest value in the sweep that drops at least ${ pct( ZERO_DROP_MIN ) } of the zero-graded NEW picks while dropping at most ${ pct( TWO_DROP_MAX ) } of the two-graded ones.`,
		''
	);
	if ( recommended !== null ) {
		push(
			`**Recommended RELATED_MIN_SCORE: ${ recommended.toFixed( 3 ) }** (current floor ${ CURRENT_FLOOR }).`,
			''
		);
	} else {
		push(
			`**No floor in the sweep meets the rule.** The closest is ${ closest === null ? 'n/a' : closest.toFixed( 3 ) }, the floor that drops the most zero-graded picks while dropping at most ${ pct( TWO_DROP_MAX ) } of the twos. The FINAL simulation below uses it.`,
			''
		);
	}

	push( '## Fixed Articles side by side', '' );
	const fixedFloor = chosen ?? CURRENT_FLOOR;
	const fixedFinal = finals[ fixedFloor ] ?? finalLists( fixedFloor );
	const g = ( source, candidate ) => {
		const v = gradeOf( source, candidate );
		return v === null ? 'ungraded' : `grade ${ v }`;
	};
	for ( const id of key.fixed_ids ) {
		const a = key.articles[ id ];
		push(
			`### ${ id }: ${ title( id ) }`,
			'',
			`First category: ${ a.first_category || 'none' }`,
			''
		);
		push(
			`| # | NEW (score, grade) | BASELINE (grade) | FINAL @ ${ fixedFloor.toFixed( 3 ) } (source, grade) |`,
			'| --- | --- | --- | --- |'
		);
		const rows = a.rows
			.filter( ( r ) => r.position <= LIMIT )
			.sort( ( x, y ) => x.position - y.position );
		for ( let i = 0; i < LIMIT; i++ ) {
			const n = rows[ i ];
			const b = lists[ id ].baseline[ i ];
			const f = fixedFinal[ id ][ i ];
			const newCell = n
				? `${ cell( title( n.related_id ) ) } (${ n.score.toFixed( 3 ) }, ${ g( id, n.related_id ) })`
				: '';
			const baseCell = b
				? `${ cell( title( b ) ) } (${ g( id, b ) })`
				: '';
			const finalCell = f
				? `${ cell( title( f.id ) ) } (${ f.from === 'new' ? `new #${ f.position } ${ f.score.toFixed( 3 ) }` : 'fallback' }, ${ g( id, f.id ) })`
				: '';
			push(
				`| ${ i + 1 } | ${ newCell } | ${ baseCell } | ${ finalCell } |`
			);
		}
		push( '' );
	}

	push( '## Ungraded picks in the simulated block', '' );
	let ungraded = 0;
	for ( const floor of Object.keys( finals ) ) {
		const items = key.eval_ids.flatMap( ( id ) =>
			finals[ floor ][ id ]
				.filter( ( p ) => gradeOf( id, p.id ) === null )
				.map(
					( p ) =>
						`- @ ${ Number( floor ).toFixed( 3 ) }: ${ id } ${ cell( title( id ) ) } -> ${ p.id } ${ cell( title( p.id ) ) } (${ p.from === 'new' ? `new #${ p.position } ${ p.score.toFixed( 3 ) }` : 'fallback' })`
				)
		);
		ungraded += items.length;
		push( ...items );
	}
	push( ungraded ? '' : 'None.', '' );

	process.stdout.write( `${ lines.join( '\n' ) }\n` );
}

function main( argv ) {
	const [ command, ...args ] = argv;
	if ( 'pairs' === command ) {
		return pairsCommand( args );
	}
	if ( 'report' === command ) {
		return reportCommand( args );
	}
	fail(
		'usage: node scripts/corpus-eval.mjs pairs [--out <dir>] [--reselect] | report <grades.json> [--out <dir>]'
	);
}

if (
	process.argv[ 1 ] &&
	import.meta.url === pathToFileURL( resolve( process.argv[ 1 ] ) ).href
) {
	main( process.argv.slice( 2 ) );
}
