/**
 * Editor canvas only (the front end uses loader.js). RichText swaps text nodes
 * per keystroke, so repaint on mutation. Watches <html> because the canvas
 * replaces its <body>.
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

function init() {
	paint( document );
	watch();
}

if ( isSupported() ) {
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}
