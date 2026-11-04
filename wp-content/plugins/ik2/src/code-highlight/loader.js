/**
 * Front end: imports the painter (Prism, ~60KB raw) after `load` and only once
 * a code block nears the viewport, so it never competes with the LCP image.
 */
const BLOCKS = 'pre.wp-block-code[data-language]';

function paintWhenNear() {
	const blocks = document.querySelectorAll( BLOCKS );
	if ( ! blocks.length ) {
		return;
	}
	let pending = false;
	const observer = new IntersectionObserver(
		( entries ) => {
			if (
				pending ||
				! entries.some( ( entry ) => entry.isIntersecting )
			) {
				return;
			}
			pending = true;
			import( /* webpackChunkName: "code-highlight-paint" */ './paint' )
				.then( ( { paint } ) => {
					observer.disconnect();
					paint( document );
				} )
				// A failed chunk load retries the next time a block scrolls into range.
				.catch( () => {
					pending = false;
				} );
		},
		{ rootMargin: '100% 0px' }
	);
	blocks.forEach( ( block ) => observer.observe( block ) );
}

if ( typeof CSS !== 'undefined' && 'highlights' in CSS ) {
	if ( document.readyState === 'complete' ) {
		paintWhenNear();
	} else {
		window.addEventListener( 'load', paintWhenNear, { once: true } );
	}
}
