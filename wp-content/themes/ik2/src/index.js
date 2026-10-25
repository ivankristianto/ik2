/**
 * Front-end JS entry, kept tiny: imports the command palette on ⌘K / Ctrl+K or a header button
 * click (hover or focus warms it), and the WebMCP article tools where the browser has them.
 */

const TRIGGER = '.ik-header__cmd';

let modulePromise = null;

/**
 * Import the palette module once; later calls reuse the same promise.
 *
 * @return {Promise<{open: (trigger?: HTMLElement) => void, toggle: () => void}>} The palette module.
 */
function loadPalette() {
	if ( ! modulePromise ) {
		modulePromise = import(
			/* webpackChunkName: "palette" */ './palette.js'
		);
	}
	return modulePromise;
}

/**
 * @param {KeyboardEvent} e The keydown event.
 * @return {boolean} Whether the event is the ⌘K / Ctrl+K shortcut.
 */
function isShortcut( e ) {
	return ( e.metaKey || e.ctrlKey ) && ( e.key === 'k' || e.key === 'K' );
}

function onKeydown( e ) {
	if ( ! isShortcut( e ) ) {
		return;
	}
	e.preventDefault();
	loadPalette().then( ( palette ) => palette.toggle() );
}

function onClick( e ) {
	const trigger = e.target.closest( TRIGGER );
	if ( ! trigger ) {
		return;
	}
	e.preventDefault();
	loadPalette().then( ( palette ) => palette.open( trigger ) );
}

function onIntent( e ) {
	if ( e.target.closest( TRIGGER ) ) {
		loadPalette();
	}
}

// WebMCP is behind a flag in Chromium; every other browser skips the chunk entirely.
if ( 'modelContext' in document ) {
	import( /* webpackChunkName: "webmcp" */ './webmcp.js' );
}

document.addEventListener( 'keydown', onKeydown );
document.addEventListener( 'click', onClick );
document.addEventListener( 'pointerover', onIntent, { passive: true } );
document.addEventListener( 'focusin', onIntent, { passive: true } );
