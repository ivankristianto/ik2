/**
 * Single Articles only: lays an "Enlarge image" button over each article image
 * the lightbox can show better, and imports the lightbox chunk on first use.
 */

import {
	isEnlargeable,
	largestSrcsetWidth,
	stageFor,
} from './lightbox-rules.js';

const HOSTS = 'figure.wp-block-image, .blocks-gallery-item, .wp-caption';
const TRIGGER = 'ik-lightbox-trigger';

const articleBody = document.querySelector( '.ik-article__body' );
const images = articleBody ? [ ...articleBody.querySelectorAll( 'img' ) ] : [];
const triggers = new Map();

let modulePromise = null;
let refreshFrame = 0;

function loadLightbox() {
	if ( ! modulePromise ) {
		modulePromise = import(
			/* webpackChunkName: "lightbox" */ './lightbox.js'
		);
	}
	return modulePromise;
}

function measure( img, host ) {
	const link = img.closest( 'a[href]' );
	const fileWidth = largestSrcsetWidth( img.getAttribute( 'srcset' ) );
	const ratio = img.naturalWidth ? img.naturalHeight / img.naturalWidth : 0;
	const naturalWidth = fileWidth || img.naturalWidth;
	return {
		inFigure: host !== null,
		naturalWidth,
		naturalHeight: naturalWidth * ratio,
		renderedWidth: img.getBoundingClientRect().width,
		href: link ? link.href : null,
		src: img.currentSrc || img.src,
	};
}

function createTrigger( img, host ) {
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

// A media-file link under the button would be a second tab stop that leaves the page.
function setLinkShadowed( img, shadowed ) {
	const link = img.closest( 'a[href]' );
	if ( ! link ) {
		return;
	}
	if ( shadowed ) {
		link.tabIndex = -1;
		link.setAttribute( 'aria-hidden', 'true' );
	} else {
		link.removeAttribute( 'tabindex' );
		link.removeAttribute( 'aria-hidden' );
	}
}

// The button is the image's keyboard-reachable stand-in, so it covers exactly the image box.
function place( button, img ) {
	button.style.top = `${ img.offsetTop }px`;
	button.style.left = `${ img.offsetLeft }px`;
	button.style.width = `${ img.offsetWidth }px`;
	button.style.height = `${ img.offsetHeight }px`;
}

function refresh() {
	refreshFrame = 0;
	// Removing the open lightbox's trigger would leave focus nowhere to return on close.
	if ( document.querySelector( '.ik-lightbox[open]' ) ) {
		return;
	}
	const root = document.documentElement;
	const stage = stageFor( {
		width: root.clientWidth,
		height: root.clientHeight,
	} );

	for ( const img of images ) {
		const host = img.closest( HOSTS );
		let button = triggers.get( img );
		if ( ! isEnlargeable( measure( img, host ), stage ) ) {
			// Focus lands back here when the lightbox closes; pulling it then would drop focus to <body>.
			if ( button && button === button.ownerDocument.activeElement ) {
				button.addEventListener( 'blur', scheduleRefresh, {
					once: true,
				} );
			} else if ( button ) {
				button.remove();
				triggers.delete( img );
				setLinkShadowed( img, false );
			}
			continue;
		}
		if ( ! button ) {
			button = createTrigger( img, host );
			triggers.set( img, button );
			setLinkShadowed( img, true );
		}
		place( button, img );
	}
}

function scheduleRefresh() {
	if ( ! refreshFrame ) {
		refreshFrame = window.requestAnimationFrame( refresh );
	}
}

function slideFor( img ) {
	return {
		src: img.src,
		srcset: img.getAttribute( 'srcset' ),
		fileWidth: largestSrcsetWidth( img.getAttribute( 'srcset' ) ),
		alt: img.alt,
		caption: img
			.closest( HOSTS )
			.querySelector( 'figcaption, .wp-caption-text' ),
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

if ( articleBody ) {
	images
		.filter( ( img ) => ! img.complete )
		.forEach( ( img ) =>
			img.addEventListener( 'load', scheduleRefresh, { once: true } )
		);
	window.addEventListener( 'resize', scheduleRefresh, { passive: true } );
	// `close` doesn't bubble; capture catches the lightbox closing to catch up on missed resizes.
	document.addEventListener( 'close', scheduleRefresh, true );
	articleBody.addEventListener( 'click', onClick );
	articleBody.addEventListener( 'pointerover', onIntent, { passive: true } );
	articleBody.addEventListener( 'focusin', onIntent, { passive: true } );
	refresh();
}
