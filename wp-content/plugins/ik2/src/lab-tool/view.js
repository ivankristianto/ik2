/**
 * Lab tool frames: grow or shrink to the height the tool reports.
 */
import { resizeHeight } from './rules';

const tools = document.querySelectorAll( '.ik-lab-tool[data-lab-origin]' );

function onMessage( event ) {
	for ( const tool of tools ) {
		const height = resizeHeight(
			event,
			tool.querySelector( 'iframe' )?.contentWindow ?? null,
			tool.dataset.labOrigin
		);

		if ( height !== null ) {
			tool.style.setProperty( '--ik-lab-tool-height', `${ height }px` );
		}
	}
}

if ( tools.length ) {
	window.addEventListener( 'message', onMessage );
}
