/**
 * Front-end JS entry: a lazy loader for the command palette.
 *
 * Nothing about the palette is needed for first paint, so this entry stays
 * tiny and only listens for the two ways to open it — ⌘K / Ctrl+K and the
 * header button. On first use it imports the palette module (which pulls in
 * its own stylesheet chunk) and hands over the request. Hovering or focusing
 * the header button warms the module so a click opens instantly.
 *
 * Styles are not bundled here — critical CSS is inlined in <head>, block
 * styles load on demand, and page-section styles are enqueued per template
 * (see inc/assets.php).
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

document.addEventListener( 'keydown', onKeydown );
document.addEventListener( 'click', onClick );
document.addEventListener( 'pointerover', onIntent, { passive: true } );
document.addEventListener( 'focusin', onIntent, { passive: true } );
