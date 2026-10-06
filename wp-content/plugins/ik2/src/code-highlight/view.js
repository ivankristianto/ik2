/**
 * Runs on the front end and in the editor canvas, where RichText swaps text
 * nodes per keystroke. Watches <html> because the canvas replaces its <body>.
 */
import { isSupported, paint } from './paint';

function watch() {
	let frame = 0;
	const repaint = () => {
		frame = 0;
		paint( document );
	};
	new MutationObserver( () => {
		frame ||= requestAnimationFrame( repaint );
	} ).observe( document.documentElement, {
		subtree: true,
		childList: true,
		characterData: true,
		attributeFilter: [ 'data-language' ],
	} );
}

// The canvas iframe is a blob: document whose body classes arrive after this
// runs; a non-iframed editor renders blocks straight into wp-admin.
function isEditor() {
	return (
		location.protocol === 'blob:' ||
		document.body.classList.contains( 'wp-admin' )
	);
}

function init() {
	paint( document );
	if ( isEditor() ) {
		watch();
	}
}

if ( isSupported() ) {
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}
