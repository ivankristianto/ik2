/**
 * Front-end entry for single Articles: puts an "Enlarge image" button over
 * every article image the lightbox can show better (see lightbox-rules.js),
 * and imports the lightbox itself, stylesheet included, on first use.
 * Enqueued only on single posts by inc/assets.php.
 */

import { isEnlargeable, stageFor } from './lightbox-rules.js';

const HOSTS = 'figure.wp-block-image, .blocks-gallery-item, .wp-caption';
const TRIGGER = 'ik-lightbox-trigger';

const body = document.querySelector( '.ik-article__body' );
const images = body ? [ ...body.querySelectorAll( 'img' ) ] : [];
const triggers = new Map();

let modulePromise = null;
let frame = 0;

function loadLightbox() {
	if ( ! modulePromise ) {
		modulePromise = import(
			/* webpackChunkName: "lightbox" */ './lightbox.js'
		);
	}
	return modulePromise;
}

function measure( img ) {
	const link = img.closest( 'a[href]' );
	return {
		inFigure: img.closest( HOSTS ) !== null,
		naturalWidth: img.naturalWidth,
		naturalHeight: img.naturalHeight,
		renderedWidth: img.getBoundingClientRect().width,
		href: link ? link.href : null,
		src: img.currentSrc || img.src,
	};
}

function createTrigger( img ) {
	const host = img.closest( HOSTS );
	const button = document.createElement( 'button' );
	button.type = 'button';
	button.className = TRIGGER;
	button.setAttribute(
		'aria-label',
		img.alt ? `Enlarge image: ${ img.alt }` : 'Enlarge image'
	);
	host.classList.add( 'ik-lightbox-host' );
	host.append( button );
	return button;
}

// The button is the image's keyboard-reachable stand-in, so it covers exactly the image box.
function place( button, img ) {
	button.style.top = `${ img.offsetTop }px`;
	button.style.left = `${ img.offsetLeft }px`;
	button.style.width = `${ img.offsetWidth }px`;
	button.style.height = `${ img.offsetHeight }px`;
}

function refresh() {
	frame = 0;
	const root = document.documentElement;
	const stage = stageFor( {
		width: root.clientWidth,
		height: root.clientHeight,
	} );

	for ( const img of images ) {
		let button = triggers.get( img );
		if ( ! isEnlargeable( measure( img ), stage ) ) {
			button?.remove();
			triggers.delete( img );
			continue;
		}
		if ( ! button ) {
			button = createTrigger( img );
			triggers.set( img, button );
		}
		place( button, img );
	}
}

function scheduleRefresh() {
	if ( ! frame ) {
		frame = window.requestAnimationFrame( refresh );
	}
}

function slideFor( img ) {
	const host = img.closest( HOSTS );
	return {
		src: img.src,
		srcset: img.getAttribute( 'srcset' ),
		alt: img.alt,
		caption: host.querySelector( 'figcaption, .wp-caption-text' ),
	};
}

function onClick( e ) {
	const button = e.target.closest( `.${ TRIGGER }` );
	if ( ! button ) {
		return;
	}
	const enlargeable = images.filter( ( img ) => triggers.has( img ) );
	const index = enlargeable.findIndex(
		( img ) => triggers.get( img ) === button
	);
	loadLightbox().then( ( lightbox ) =>
		lightbox.open( enlargeable.map( slideFor ), index, button )
	);
}

function onIntent( e ) {
	if ( e.target.closest( `.${ TRIGGER }` ) ) {
		loadLightbox();
	}
}

if ( body ) {
	images
		.filter( ( img ) => ! img.complete )
		.forEach( ( img ) =>
			img.addEventListener( 'load', scheduleRefresh, { once: true } )
		);
	window.addEventListener( 'resize', scheduleRefresh, { passive: true } );
	body.addEventListener( 'click', onClick );
	body.addEventListener( 'pointerover', onIntent, { passive: true } );
	body.addEventListener( 'focusin', onIntent, { passive: true } );
	refresh();
}
