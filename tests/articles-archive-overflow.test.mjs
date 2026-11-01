/**
 * Archive article cards must wrap long words and stay inside the viewport, phones to desktop.
 * Needs `pnpm build` and a local Chrome (CHROME_PATH overrides). SCREENSHOT_DIR saves PNGs.
 */
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { spawn } from 'node:child_process';
import { existsSync, mkdtempSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { createServer } from 'node:http';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const CHROME =
	process.env.CHROME_PATH ??
	'/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const BUILD = 'wp-content/themes/ik2/build';
const FIXTURE = 'tests/fixtures/articles-archive-wordpress.html';
const STEP_TIMEOUT = 10_000;

const VIEWPORTS = [
	{ name: 'phone-320', width: 320, height: 640, mobile: true, scale: 2 },
	{ name: 'phone-375', width: 375, height: 812, mobile: true, scale: 3 },
	{ name: 'iphone-390', width: 390, height: 844, mobile: true, scale: 3 },
	{ name: 'phone-430', width: 430, height: 932, mobile: true, scale: 3 },
	{ name: 'tablet-768', width: 768, height: 1024, mobile: true, scale: 2 },
	{ name: 'tablet-1024', width: 1024, height: 768, mobile: false, scale: 2 },
	{ name: 'desktop-1280', width: 1280, height: 900, mobile: false, scale: 1 },
];

// Tokens with no break opportunity. Each must wrap inside its card when it is wider than the card.
const LONG_TOKENS = [
	'https://make.wordpress.org/core/2015/04/25/wordpress-4-1-3-released/',
	'Supercalifragilisticexpialidocious_wp_enqueue_block_style_with_a_very_long_identifier',
	'an-extremely-long-tag-name-without-any-spaces-at-all-for-stress',
];

// Runs in the page. Returns a list of failures, empty when the layout holds.
const pageChecks = ( width ) => `( () => {
	const failures = [];
	const label = ( el ) => el.tagName.toLowerCase() + '.' + String( el.className ).split( ' ' )[ 0 ];
	const root = document.documentElement;
	const viewport = root.clientWidth;
	if ( root.scrollWidth > viewport ) {
		failures.push( 'document scrollWidth ' + root.scrollWidth + ' > clientWidth ' + viewport );
	}
	if ( innerWidth > ${ width } ) {
		failures.push( 'layout viewport grew: innerWidth ' + innerWidth + ' > device width ${ width }' );
	}
	const cards = [ ...document.querySelectorAll( '.ik-articles-grid > li' ) ];
	if ( cards.length !== 4 ) {
		failures.push( 'expected 4 fixture cards, found ' + cards.length );
	}
	for ( const li of cards ) {
		const title = li.querySelector( 'h2' ).textContent.trim().slice( 0, 30 );
		const box = li.getBoundingClientRect();
		if ( box.left < -0.5 || box.right > viewport + 0.5 ) {
			failures.push( title + ': card spans ' + box.left.toFixed( 1 ) + '..' + box.right.toFixed( 1 ) + ' in a ' + viewport + 'px viewport' );
		}
		for ( const el of [ li, ...li.querySelectorAll( '*' ) ] ) {
			const r = el.getBoundingClientRect();
			if ( r.width && ( r.right > box.right + 0.5 || r.left < box.left - 0.5 ) ) {
				failures.push( title + ': ' + label( el ) + ' leaves the card' );
			}
			if ( el.clientWidth && el.scrollWidth > el.clientWidth + 1 ) {
				failures.push( title + ': ' + label( el ) + ' content ' + el.scrollWidth + ' > box ' + el.clientWidth );
			}
		}
		for ( const el of [ li, ...li.querySelectorAll( '.ik-article-card, .ik-article-card__title, .ik-article-card__excerpt, .ik-article-card__excerpt p, .ik-article-card__tags, .ik-article-card__tags a' ) ] ) {
			const overflow = getComputedStyle( el ).overflowX;
			if ( overflow !== 'visible' ) {
				failures.push( title + ': ' + label( el ) + ' hides overflow (' + overflow + ')' );
			}
		}
	}
	const walker = document.createTreeWalker( document.querySelector( '.ik-articles-grid' ), NodeFilter.SHOW_TEXT );
	const found = new Set();
	for ( let node = walker.nextNode(); node; node = walker.nextNode() ) {
		for ( const token of ${ JSON.stringify( LONG_TOKENS ) } ) {
			const at = node.data.indexOf( token );
			if ( at < 0 ) {
				continue;
			}
			found.add( token );
			const card = node.parentElement.closest( 'li' ).getBoundingClientRect();
			const range = document.createRange();
			range.setStart( node, at );
			range.setEnd( node, at + token.length );
			const lines = [ ...range.getClientRects() ].filter( ( r ) => r.width );
			const span = Math.max( ...lines.map( ( r ) => r.right ) ) - Math.min( ...lines.map( ( r ) => r.left ) );
			const name = token.slice( 0, 24 ) + '...';
			if ( lines.some( ( r ) => r.left < card.left - 0.5 || r.right > card.right + 0.5 ) ) {
				failures.push( name + ' is drawn outside its card' );
			}
			const oneLine = document.createElement( 'span' );
			oneLine.style.cssText = 'position:absolute;white-space:nowrap;visibility:hidden';
			oneLine.textContent = token;
			node.parentElement.append( oneLine );
			const natural = oneLine.getBoundingClientRect().width;
			oneLine.remove();
			if ( natural > card.width && lines.length < 2 ) {
				failures.push( name + ' (' + Math.round( natural ) + 'px) does not wrap in a ' + Math.round( card.width ) + 'px card' );
			}
			if ( span > card.width + 0.5 ) {
				failures.push( name + ' spans ' + Math.round( span ) + 'px in a ' + Math.round( card.width ) + 'px card' );
			}
		}
	}
	for ( const token of ${ JSON.stringify( LONG_TOKENS ) } ) {
		if ( ! found.has( token ) ) {
			failures.push( 'fixture is missing ' + token.slice( 0, 24 ) );
		}
	}
	return [ ...new Set( failures ) ];
} )()`;

const sleep = ( ms ) => new Promise( ( resolve ) => setTimeout( resolve, ms ) );

function withTimeout( promise, what ) {
	let timer;
	return Promise.race( [
		promise,
		new Promise( ( _, reject ) => {
			timer = setTimeout( () => reject( new Error( `timed out: ${ what }` ) ), STEP_TIMEOUT );
		} ),
	] ).finally( () => clearTimeout( timer ) );
}

function page() {
	const css = ( file ) => readFileSync( join( BUILD, file ), 'utf8' );
	return readFileSync( FIXTURE, 'utf8' )
		.replace( '/* ik2:critical */', () => css( 'critical.css' ) )
		.replace( '/* ik2:section-articles */', () => css( 'section-articles.css' ) );
}

// Chrome picks a free port and writes it to the fresh profile, so we never attach to another browser.
async function devtoolsPort( profile ) {
	const file = join( profile, 'DevToolsActivePort' );
	for ( let attempt = 0; attempt < 40; attempt++ ) {
		if ( existsSync( file ) ) {
			const port = Number( readFileSync( file, 'utf8' ).split( '\n' )[ 0 ] );
			if ( port ) {
				return port;
			}
		}
		await sleep( 250 );
	}
	throw new Error( `Chrome did not write DevToolsActivePort (${ CHROME })` );
}

function connect( wsUrl ) {
	const ws = new WebSocket( wsUrl );
	const pending = new Map();
	const listeners = new Set();
	let id = 0;
	ws.addEventListener( 'message', ( event ) => {
		const msg = JSON.parse( event.data );
		if ( msg.id && pending.has( msg.id ) ) {
			pending.get( msg.id )( msg );
			pending.delete( msg.id );
		} else {
			listeners.forEach( ( fn ) => fn( msg ) );
		}
	} );
	const send = ( method, params = {} ) =>
		withTimeout(
			new Promise( ( resolve ) => {
				pending.set( ++id, resolve );
				ws.send( JSON.stringify( { id, method, params } ) );
			} ),
			method
		);
	const once = ( method ) =>
		withTimeout(
			new Promise( ( resolve ) => {
				const fn = ( msg ) => {
					if ( msg.method === method ) {
						listeners.delete( fn );
						resolve( msg );
					}
				};
				listeners.add( fn );
			} ),
			method
		);
	return withTimeout(
		new Promise( ( resolve, reject ) => {
			ws.addEventListener( 'open', () => resolve( { send, once, close: () => ws.close() } ) );
			ws.addEventListener( 'error', () => reject( new Error( 'DevTools socket error' ) ) );
		} ),
		'DevTools socket'
	);
}

const ready = existsSync( CHROME ) && existsSync( join( BUILD, 'section-articles.css' ) );

test(
	'article cards wrap long words and fit the viewport on archives',
	{ skip: ready ? false : `needs ${ CHROME } and pnpm build`, timeout: 90_000 },
	async ( t ) => {
		const server = createServer( ( req, res ) => {
			res.writeHead( 200, { 'content-type': 'text/html; charset=utf-8' } );
			res.end( page() );
		} );
		const profile = mkdtempSync( join( tmpdir(), 'ik2-archive-' ) );
		let chrome;
		let cdp;
		t.after( async () => {
			cdp?.close();
			chrome?.kill();
			server.close();
			await sleep( 300 );
			rmSync( profile, { recursive: true, force: true } );
		} );

		await new Promise( ( resolve ) => server.listen( 0, '127.0.0.1', resolve ) );
		const url = `http://127.0.0.1:${ server.address().port }/category/wordpress/`;
		chrome = spawn(
			CHROME,
			[ '--headless=new', '--remote-debugging-port=0', `--user-data-dir=${ profile }`, '--no-first-run', 'about:blank' ],
			{ stdio: 'ignore' }
		);
		const port = await devtoolsPort( profile );
		const target = await withTimeout(
			fetch( `http://127.0.0.1:${ port }/json/new?about:blank`, { method: 'PUT' } ).then( ( res ) => res.json() ),
			'DevTools target'
		);
		cdp = await connect( target.webSocketDebuggerUrl );
		await cdp.send( 'Page.enable' );

		for ( const vp of VIEWPORTS ) {
			await t.test( vp.name, async () => {
				await cdp.send( 'Emulation.setDeviceMetricsOverride', {
					width: vp.width,
					height: vp.height,
					deviceScaleFactor: vp.scale,
					mobile: vp.mobile,
				} );
				const loaded = cdp.once( 'Page.loadEventFired' );
				await cdp.send( 'Page.navigate', { url } );
				await loaded;
				const { result } = await cdp.send( 'Runtime.evaluate', {
					expression: pageChecks( vp.width ),
					returnByValue: true,
				} );

				if ( process.env.SCREENSHOT_DIR ) {
					await cdp.send( 'Runtime.evaluate', {
						expression: `document.querySelector( '.post-523' ).scrollIntoView( { block: 'start' } ); scrollBy( 0, -24 )`,
					} );
					const shot = await cdp.send( 'Page.captureScreenshot', { format: 'png' } );
					writeFileSync( join( process.env.SCREENSHOT_DIR, `${ vp.name }.png` ), Buffer.from( shot.result.data, 'base64' ) );
				}

				assert.ok( ! result.exceptionDetails, result.exceptionDetails?.exception?.description );
				assert.deepEqual( result.result.value, [], `${ vp.name } (${ vp.width }px)` );
			} );
		}
	}
);
