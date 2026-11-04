/**
 * Front end only: imports the painter (Prism, ~60KB) after `load`, once a code
 * block nears the viewport, so it never competes with the article's LCP image.
 * Highlights only recolor text, so painting late shifts nothing.
 */
const BLOCKS = 'pre.wp-block-code[data-language]';

function paintAll() {
	import( /* webpackChunkName: "code-highlight-paint" */ './paint' ).then(
		( { paint } ) => paint( document )
	);
}

function observe() {
	const blocks = document.querySelectorAll( BLOCKS );
	if ( ! blocks.length ) {
		return;
	}
	const observer = new IntersectionObserver(
		( entries ) => {
			if ( entries.some( ( entry ) => entry.isIntersecting ) ) {
				observer.disconnect();
				paintAll();
			}
		},
		{ rootMargin: '100% 0px' }
	);
	blocks.forEach( ( block ) => observer.observe( block ) );
}

if ( typeof CSS !== 'undefined' && 'highlights' in CSS ) {
	if ( document.readyState === 'complete' ) {
		observe();
	} else {
		window.addEventListener( 'load', observe, { once: true } );
	}
}
