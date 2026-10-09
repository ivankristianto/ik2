/**
 * IK2 — Command palette.
 *
 * Loaded on demand by src/index.js the first time ⌘K / Ctrl+K is pressed or
 * the header button is clicked; the stylesheet chunk comes along with it.
 * Rendered as a native modal <dialog>, so the browser owns the top layer,
 * focus containment, Escape and (via `closedby="any"`) light dismiss.
 * Searches posts via the WordPress REST API, with static nav fallbacks.
 */

import './styles/_palette.scss';

const NAV_ITEMS = [
	{ group: 'Navigate', glyph: '→', label: 'Home', href: '/' },
	{ group: 'Navigate', glyph: '→', label: 'Articles', href: '/articles' },
	{ group: 'Navigate', glyph: '→', label: 'Projects', href: '/projects' },
	{ group: 'Navigate', glyph: '→', label: 'Speaking', href: '/speaking' },
	{ group: 'Navigate', glyph: '→', label: 'About', href: '/about' },
	{ group: 'Navigate', glyph: '→', label: 'Contact', href: '/contact' },
	{ group: 'Navigate', glyph: '→', label: 'Resume', href: '/resume' },
];

const ACTIONS = [
	{
		group: 'Actions',
		glyph: '⤓',
		label: 'Subscribe via RSS',
		href: '/feed/',
	},
	{
		group: 'Actions',
		glyph: '✉',
		label: 'Email Ivan',
		href: 'mailto:hello@ivankristianto.com',
	},
];

let restRoot = '/wp-json/';
if (
	typeof window !== 'undefined' &&
	window.wpApiSettings &&
	window.wpApiSettings.root
) {
	restRoot = window.wpApiSettings.root;
}

const LIST_ID = 'ik-cmdk-list';
const OPTION_ID_PREFIX = 'ik-cmdk-option-';
// Gives a screen reader a beat to finish echoing the keystroke before the result count.
const ANNOUNCE_DELAY = 150;

const state = {
	query: '',
	active: 0,
	results: [],
	searching: false,
	failed: false,
};

let abortController = null;
let searchTimer = null;
let palette;
let input;
let listEl;
let emptyEl;
let statusEl;
let announceTimer = null;
// Element focus is returned to when the palette closes (the trigger that
// opened it, or whatever held focus when ⌘K fired). The dialog restores
// focus itself; this is the explicit fallback for engines that don't.
let lastFocused = null;

/**
 * Reflect the open/closed state on every trigger button so assistive tech
 * announces it.
 *
 * @param {boolean} expanded Whether the palette is open.
 */
function setTriggersExpanded( expanded ) {
	document
		.querySelectorAll( '.ik-header__cmd' )
		.forEach( ( btn ) =>
			btn.setAttribute( 'aria-expanded', expanded ? 'true' : 'false' )
		);
}

function filteredNav( q ) {
	if ( ! q ) {
		return NAV_ITEMS.concat( ACTIONS );
	}
	const lower = q.toLowerCase();
	return NAV_ITEMS.concat( ACTIONS ).filter( ( it ) =>
		it.label.toLowerCase().includes( lower )
	);
}

/**
 * @param {string} q Trimmed, non-empty query.
 * @return {Promise<?{items: Array<Object>, failed: boolean}>} Null when a newer search aborted this one.
 */
async function searchPosts( q ) {
	if ( abortController ) {
		abortController.abort();
	}
	abortController = new AbortController();
	try {
		const url =
			restRoot +
			'wp/v2/search?per_page=8&search=' +
			encodeURIComponent( q );
		const res = await fetch( url, { signal: abortController.signal } );
		if ( ! res.ok ) {
			return { items: [], failed: true };
		}
		const data = await res.json();
		const items = data.map( ( item ) => ( {
			group: String( item.subtype || 'post' ).replace( /^./, ( c ) =>
				c.toUpperCase()
			),
			glyph: '·',
			label: String( item.title || '' ),
			href: String( item.url || '#' ),
		} ) );
		return { items, failed: false };
	} catch ( err ) {
		if ( err && err.name === 'AbortError' ) {
			return null;
		}
		return { items: [], failed: true };
	}
}

function combine() {
	const q = state.query.trim();
	const nav = filteredNav( q );
	return nav.concat( state.results );
}

function buildItemEl( it, index, isActive ) {
	const a = document.createElement( 'a' );
	a.className = 'ik-cmdk__item' + ( isActive ? ' is-active' : '' );
	a.id = OPTION_ID_PREFIX + index;
	a.setAttribute( 'role', 'option' );
	a.setAttribute( 'aria-selected', isActive ? 'true' : 'false' );
	a.dataset.index = String( index );
	a.href = it.href;

	const glyph = document.createElement( 'span' );
	glyph.className = 'ik-cmdk__item-glyph';
	glyph.setAttribute( 'aria-hidden', 'true' );
	glyph.textContent = it.glyph;
	a.appendChild( glyph );

	const label = document.createElement( 'span' );
	label.className = 'ik-cmdk__item-label';
	label.textContent = it.label;
	a.appendChild( label );

	return a;
}

function render() {
	if ( ! palette ) {
		return;
	}
	const items = combine();
	state.active = Math.max( 0, Math.min( state.active, items.length - 1 ) );

	listEl.replaceChildren();
	input.setAttribute( 'aria-expanded', items.length > 0 ? 'true' : 'false' );

	if ( items.length === 0 ) {
		input.removeAttribute( 'aria-activedescendant' );
		emptyEl.hidden = false;
		if ( state.searching ) {
			emptyEl.textContent = 'Searching…';
		} else if ( state.failed ) {
			emptyEl.textContent =
				'Search is unavailable right now. Try again in a moment.';
		} else {
			emptyEl.textContent =
				'No matches. Try “wordpress”, “performance”, or “resume”.';
		}
		return;
	}
	emptyEl.hidden = true;

	let lastGroup = null;
	let groupEl = null;
	items.forEach( ( it, i ) => {
		if ( it.group !== lastGroup ) {
			groupEl = document.createElement( 'div' );
			groupEl.className = 'ik-cmdk__group';
			groupEl.setAttribute( 'role', 'group' );
			groupEl.setAttribute( 'aria-label', it.group );
			// The group's aria-label already names it; the visible title would be read twice.
			const heading = document.createElement( 'div' );
			heading.className = 'ik-cmdk__group-title';
			heading.setAttribute( 'aria-hidden', 'true' );
			heading.textContent = it.group;
			groupEl.appendChild( heading );
			listEl.appendChild( groupEl );
			lastGroup = it.group;
		}
		groupEl.appendChild( buildItemEl( it, i, i === state.active ) );
	} );

	input.setAttribute(
		'aria-activedescendant',
		OPTION_ID_PREFIX + state.active
	);
	const activeEl = listEl.querySelector( '.ik-cmdk__item.is-active' );
	if ( activeEl && typeof activeEl.scrollIntoView === 'function' ) {
		activeEl.scrollIntoView( { block: 'nearest' } );
	}
}

/**
 * Move the highlight, `aria-selected` and `aria-activedescendant` together.
 * @param {number} index Option index in combine() order.
 * @return {?HTMLElement} The option element, if rendered.
 */
function activate( index ) {
	state.active = index;
	let activeEl = null;
	listEl.querySelectorAll( '.ik-cmdk__item' ).forEach( ( el ) => {
		const isActive = el.dataset.index === String( index );
		el.classList.toggle( 'is-active', isActive );
		el.setAttribute( 'aria-selected', isActive ? 'true' : 'false' );
		if ( isActive ) {
			activeEl = el;
		}
	} );
	if ( activeEl ) {
		input.setAttribute( 'aria-activedescendant', activeEl.id );
		activeEl.scrollIntoView( { block: 'nearest' } );
	}
	return activeEl;
}

// Cleared first so the same text twice in a row (two searches, same count) is still announced.
function announce( message ) {
	if ( announceTimer ) {
		clearTimeout( announceTimer );
	}
	statusEl.textContent = '';
	if ( ! message ) {
		return;
	}
	announceTimer = setTimeout( () => {
		statusEl.textContent = message;
	}, ANNOUNCE_DELAY );
}

function searchSummary() {
	const count = combine().length;
	if ( count === 0 ) {
		return state.failed
			? 'Search is unavailable right now.'
			: 'No matches.';
	}
	const found = count === 1 ? '1 result.' : count + ' results.';
	return state.failed
		? found + ' Article search is unavailable right now.'
		: found;
}

function runActive() {
	const items = combine();
	const it = items[ state.active ];
	if ( it && it.href ) {
		window.location.href = it.href;
	}
}

/**
 * Open the palette as a modal dialog.
 *
 * @param {HTMLElement} [trigger] The button that opened it, if any.
 */
export function open( trigger ) {
	build();
	if ( palette.open ) {
		return;
	}
	const activeEl = palette.ownerDocument.activeElement;
	if ( trigger instanceof window.HTMLElement ) {
		lastFocused = trigger;
	} else if ( activeEl instanceof window.HTMLElement ) {
		lastFocused = activeEl;
	} else {
		lastFocused = null;
	}
	state.query = '';
	state.results = [];
	state.active = 0;
	state.failed = false;
	input.value = '';
	announce( '' );
	render();
	palette.showModal();
	setTriggersExpanded( true );
	input.focus();
}

/**
 * Close the palette. The dialog's `close` event does the bookkeeping, so this
 * is the same path whether we close it or the browser does (Esc, backdrop).
 */
export function close() {
	if ( palette && palette.open ) {
		palette.close();
	}
}

/**
 * Toggle the palette — what ⌘K does.
 */
export function toggle() {
	if ( palette && palette.open ) {
		close();
	} else {
		open();
	}
}

function onClose() {
	if ( abortController ) {
		abortController.abort();
	}
	if ( searchTimer ) {
		clearTimeout( searchTimer );
	}
	if ( announceTimer ) {
		clearTimeout( announceTimer );
	}
	state.searching = false;
	setTriggersExpanded( false );
	// Return focus to the trigger so keyboard users aren't dropped at the
	// top of the document.
	if ( lastFocused && typeof lastFocused.focus === 'function' ) {
		lastFocused.focus();
	}
	lastFocused = null;
}

/**
 * List navigation. ⌘K lives in the loader; Escape and focus containment are
 * the dialog's own.
 *
 * Enter is only taken over in the input: a result reached with Tab keeps
 * its native Enter, so the link the user is on is the one that opens.
 *
 * @param {KeyboardEvent} e The keydown event, scoped to the dialog.
 */
function onKeydown( e ) {
	const option = e.target.closest( '.ik-cmdk__item' );
	if ( e.target !== input && ! option ) {
		return;
	}
	if ( e.key === 'ArrowDown' || e.key === 'ArrowUp' ) {
		e.preventDefault();
		const last = combine().length - 1;
		const next =
			e.key === 'ArrowDown'
				? Math.min( last, state.active + 1 )
				: Math.max( 0, state.active - 1 );
		const el = activate( next );
		if ( option && el ) {
			el.focus();
		}
		return;
	}
	if ( e.key === 'Enter' && e.target === input ) {
		e.preventDefault();
		runActive();
	}
}

// Hovering or tabbing onto a result makes it the active one.
function activateEventTarget( e ) {
	const option = e.target.closest( '.ik-cmdk__item' );
	if ( option ) {
		activate( Number( option.dataset.index ) );
	}
}

function onInput( e ) {
	state.query = e.target.value;
	state.active = 0;
	state.results = [];
	state.failed = false;

	if ( searchTimer ) {
		clearTimeout( searchTimer );
	}
	const q = state.query.trim();
	state.searching = q !== '';
	render();
	if ( ! q ) {
		if ( abortController ) {
			abortController.abort();
		}
		announce( '' );
		return;
	}
	searchTimer = setTimeout( async () => {
		const outcome = await searchPosts( q );
		if ( ! outcome || q !== state.query.trim() ) {
			return;
		}
		state.results = outcome.items;
		state.failed = outcome.failed;
		state.searching = false;
		render();
		announce( searchSummary() );
	}, 180 );
}

function buildSkeleton() {
	const dialog = document.createElement( 'dialog' );
	dialog.className = 'ik-cmdk';
	dialog.id = 'ik-command-palette';
	dialog.setAttribute( 'aria-label', 'Command palette' );
	// Light dismiss (backdrop click) and close requests (Esc), declaratively.
	dialog.setAttribute( 'closedby', 'any' );

	const inputRow = document.createElement( 'div' );
	inputRow.className = 'ik-cmdk__input-row';
	const caret = document.createElement( 'span' );
	caret.className = 'ik-cmdk__caret';
	caret.setAttribute( 'aria-hidden', 'true' );
	caret.textContent = '›';
	const inp = document.createElement( 'input' );
	inp.className = 'ik-cmdk__input';
	inp.type = 'search';
	inp.setAttribute( 'role', 'combobox' );
	inp.setAttribute( 'aria-label', 'Search the site' );
	inp.setAttribute( 'aria-controls', LIST_ID );
	inp.setAttribute( 'aria-autocomplete', 'list' );
	inp.setAttribute( 'aria-expanded', 'false' );
	inp.placeholder = 'Search articles, topics, projects, or jump to a page…';
	inp.autocomplete = 'off';
	inp.spellcheck = false;
	inp.autofocus = true;
	const esc = document.createElement( 'span' );
	esc.className = 'ik-cmdk__esc';
	esc.setAttribute( 'aria-hidden', 'true' );
	esc.textContent = 'esc';
	inputRow.append( caret, inp, esc );

	const list = document.createElement( 'div' );
	list.className = 'ik-cmdk__list';
	list.id = LIST_ID;
	list.setAttribute( 'role', 'listbox' );
	list.setAttribute( 'aria-label', 'Results' );

	// The status region announces the outcome, so the visible copy stays silent.
	const empty = document.createElement( 'div' );
	empty.className = 'ik-cmdk__empty';
	empty.setAttribute( 'aria-hidden', 'true' );
	empty.hidden = true;

	// Present before the first search so screen readers are already watching it.
	const status = document.createElement( 'div' );
	status.className = 'ik-cmdk__status';
	status.setAttribute( 'role', 'status' );

	const footer = document.createElement( 'div' );
	footer.className = 'ik-cmdk__footer';
	const make = ( html ) => {
		const span = document.createElement( 'span' );
		span.innerHTML = html;
		return span;
	};
	footer.append(
		make( '<kbd>↑</kbd> <kbd>↓</kbd> navigate' ),
		make( '<kbd>↵</kbd> go' ),
		make( '<kbd>esc</kbd> close' )
	);
	const meta = document.createElement( 'span' );
	meta.className = 'ik-cmdk__footer-meta';
	meta.textContent = '⌘K from anywhere';
	footer.append( meta );

	dialog.append( inputRow, list, empty, status, footer );

	return { dialog, input: inp, list, empty, status };
}

/**
 * Light-dismiss fallback for engines without `closedby` (Safari): a click
 * whose target is the dialog itself but lands outside its box is a backdrop
 * click.
 *
 * @param {MouseEvent} e The click event.
 */
function onBackdropClick( e ) {
	if ( e.target !== palette ) {
		return;
	}
	const rect = palette.getBoundingClientRect();
	const inside =
		rect.top <= e.clientY &&
		e.clientY <= rect.top + rect.height &&
		rect.left <= e.clientX &&
		e.clientX <= rect.left + rect.width;
	if ( ! inside ) {
		close();
	}
}

function build() {
	if ( palette ) {
		return;
	}
	const parts = buildSkeleton();
	palette = parts.dialog;
	input = parts.input;
	listEl = parts.list;
	emptyEl = parts.empty;
	statusEl = parts.status;
	document.body.appendChild( palette );

	palette.addEventListener( 'close', onClose );
	palette.addEventListener( 'keydown', onKeydown );
	if ( ! ( 'closedBy' in window.HTMLDialogElement.prototype ) ) {
		palette.addEventListener( 'click', onBackdropClick );
	}
	listEl.addEventListener( 'mouseover', activateEventTarget );
	listEl.addEventListener( 'focusin', activateEventTarget );
	input.addEventListener( 'input', onInput );
}
