/**
 * IK2: image lightbox.
 *
 * Loaded on demand by src/lightbox-loader.js the first time a reader opens an
 * article image. A native modal <dialog>, so the browser owns the top layer,
 * focus containment and close requests (Esc, Android back). Slides sit in a
 * scroll-snap track: swiping is the browser's own scrolling, and the buttons
 * and arrow keys jump the track one slide at a time.
 */

import './styles/_lightbox.scss';

// Lucide icon paths: x, chevron-left, chevron-right.
const ICONS = {
	close: '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
	prev: '<path d="m15 18-6-6 6-6"/>',
	next: '<path d="m9 18 6-6-6-6"/>',
};

let dialog;
let track;
let counter;
let status;
let navRow;
let prevButton;
let nextButton;
let slides = [];
let current = 0;
let scrollFrame = 0;
// Safari doesn't focus a clicked button, so the dialog can't restore focus to it on its own.
let lastFocused = null;

function iconButton( className, label, icon ) {
	const button = document.createElement( 'button' );
	button.type = 'button';
	button.className = className;
	button.setAttribute( 'aria-label', label );
	button.innerHTML = `<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">${ ICONS[ icon ] }</svg>`;
	return button;
}

function build() {
	if ( dialog ) {
		return;
	}
	dialog = document.createElement( 'dialog' );
	dialog.className = 'ik-lightbox';
	dialog.setAttribute( 'aria-label', 'Image viewer' );

	const bar = document.createElement( 'div' );
	bar.className = 'ik-lightbox__bar';
	counter = document.createElement( 'span' );
	counter.className = 'ik-lightbox__counter';
	counter.setAttribute( 'aria-hidden', 'true' );
	status = document.createElement( 'span' );
	status.className = 'ik-lightbox__status';
	status.setAttribute( 'aria-live', 'polite' );
	const closeButton = iconButton( 'ik-lightbox__close', 'Close', 'close' );
	closeButton.autofocus = true;
	closeButton.addEventListener( 'click', () => dialog.close() );
	bar.append( counter, status, closeButton );

	track = document.createElement( 'div' );
	track.className = 'ik-lightbox__track';

	navRow = document.createElement( 'div' );
	navRow.className = 'ik-lightbox__nav';
	prevButton = iconButton(
		'ik-lightbox__step ik-lightbox__step--prev',
		'Previous image',
		'prev'
	);
	nextButton = iconButton(
		'ik-lightbox__step ik-lightbox__step--next',
		'Next image',
		'next'
	);
	prevButton.addEventListener( 'click', () => step( -1 ) );
	nextButton.addEventListener( 'click', () => step( 1 ) );
	navRow.append( prevButton, nextButton );

	dialog.append( bar, track, navRow );
	document.body.appendChild( dialog );

	dialog.addEventListener( 'close', onClose );
	dialog.addEventListener( 'keydown', onKeydown );
	dialog.addEventListener( 'click', onEmptyClick );
	track.addEventListener( 'scroll', onScroll, { passive: true } );
}

function buildSlide( slide, index, total ) {
	const figure = document.createElement( 'figure' );
	figure.className = 'ik-lightbox__slide';
	figure.setAttribute( 'role', 'group' );
	figure.setAttribute( 'aria-roledescription', 'slide' );
	figure.setAttribute( 'aria-label', `${ index + 1 } of ${ total }` );

	const img = document.createElement( 'img' );
	img.className = 'ik-lightbox__image';
	img.alt = slide.alt;
	img.decoding = 'async';
	img.loading = 'lazy';
	if ( slide.srcset ) {
		img.srcset = slide.srcset;
		img.sizes = '100vw';
	}
	img.src = slide.src;
	figure.append( img );

	if ( slide.caption ) {
		const caption = document.createElement( 'figcaption' );
		caption.className = 'ik-lightbox__caption';
		caption.append(
			...[ ...slide.caption.childNodes ].map( ( node ) =>
				node.cloneNode( true )
			)
		);
		figure.append( caption );
	}
	return figure;
}

function setCurrent( index ) {
	current = index;
	const total = slides.length;
	counter.textContent = `${ index + 1 } / ${ total }`;
	status.textContent = `Image ${ index + 1 } of ${ total }`;
	// aria-disabled rather than disabled, so a focused button keeps focus at the ends.
	prevButton.setAttribute( 'aria-disabled', String( index === 0 ) );
	nextButton.setAttribute( 'aria-disabled', String( index === total - 1 ) );
	// Lazy loading inside a horizontal scroller is unreliable, so fetch the neighbours outright.
	slides.slice( Math.max( 0, index - 1 ), index + 2 ).forEach( ( slide ) => {
		slide.querySelector( 'img' ).loading = 'eager';
	} );
}

function go( index ) {
	const target = Math.max( 0, Math.min( slides.length - 1, index ) );
	track.scrollTo( { left: target * track.clientWidth, behavior: 'instant' } );
	setCurrent( target );
}

function step( delta ) {
	const button = delta < 0 ? prevButton : nextButton;
	if ( button.getAttribute( 'aria-disabled' ) !== 'true' ) {
		go( current + delta );
	}
}

function onScroll() {
	if ( scrollFrame ) {
		return;
	}
	scrollFrame = window.requestAnimationFrame( () => {
		scrollFrame = 0;
		const index = Math.round( track.scrollLeft / track.clientWidth );
		if ( index !== current ) {
			setCurrent( index );
		}
	} );
}

function onKeydown( e ) {
	if ( e.key === 'ArrowLeft' ) {
		e.preventDefault();
		step( -1 );
	} else if ( e.key === 'ArrowRight' ) {
		e.preventDefault();
		step( 1 );
	}
}

// The dialog fills the viewport, so there's no backdrop to light-dismiss on; empty space plays that part.
function onEmptyClick( e ) {
	const target = e.target;
	if (
		target === dialog ||
		target === track ||
		target.classList.contains( 'ik-lightbox__slide' )
	) {
		dialog.close();
	}
}

function onClose() {
	if ( lastFocused && typeof lastFocused.focus === 'function' ) {
		lastFocused.focus();
	}
	lastFocused = null;
}

/**
 * Open the lightbox on one slide of an Article's enlargeable images.
 *
 * @param {Array}       items   Slides in document order: src, srcset, alt, caption.
 * @param {number}      index   The slide to open on.
 * @param {HTMLElement} trigger The button that opened it; focus returns here.
 */
export function open( items, index, trigger ) {
	build();
	if ( dialog.open ) {
		return;
	}
	lastFocused = trigger;
	slides = items.map( ( item, i ) => buildSlide( item, i, items.length ) );
	track.replaceChildren( ...slides );
	navRow.hidden = slides.length < 2;
	counter.hidden = slides.length < 2;
	dialog.showModal();
	go( index );
}
