/**
 * Lab tool frames: grow or shrink to the height the tool reports, and say so
 * when the tool never reports at all.
 */
import { resizeHeight } from './rules';

// A tool posts its height as soon as it paints, so this long without one means the lab host is down or blocked.
const FAIL_AFTER_MS = 8000;

const tools = document.querySelectorAll( '.ik-lab-tool[data-lab-origin]' );
const timers = new WeakMap();

function onMessage( event ) {
	for ( const tool of tools ) {
		const height = resizeHeight(
			event,
			tool.querySelector( 'iframe' )?.contentWindow ?? null,
			tool.dataset.labOrigin
		);

		if ( height !== null ) {
			clearTimeout( timers.get( tool ) );
			tool.dataset.state = 'ready';
			tool.style.setProperty( '--ik-lab-tool-height', `${ height }px` );
			tool.style.setProperty(
				'--ik-lab-tool-height-narrow',
				`${ height }px`
			);
		}
	}
}

function startTimer( tool ) {
	if ( tool.dataset.state !== 'loading' || timers.has( tool ) ) {
		return;
	}

	timers.set(
		tool,
		setTimeout( () => {
			if ( tool.dataset.state === 'loading' ) {
				tool.dataset.state = 'failed';
			}
		}, FAIL_AFTER_MS )
	);
}

if ( tools.length ) {
	window.addEventListener( 'message', onMessage );

	// The frame loads lazily, so the clock starts when it nears the viewport, not at page load.
	const nearViewport = new IntersectionObserver(
		( entries ) => {
			for ( const entry of entries ) {
				if ( entry.isIntersecting ) {
					startTimer( entry.target );
					nearViewport.unobserve( entry.target );
				}
			}
		},
		{ rootMargin: '50%' }
	);

	for ( const tool of tools ) {
		tool.dataset.state = 'loading';
		nearViewport.observe( tool );
	}
}
