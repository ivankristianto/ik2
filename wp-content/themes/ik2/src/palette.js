/**
 * IK2 — Command palette.
 *
 * Front-end-only, no build dependency on the Interactivity API yet.
 * Opens on ⌘K / Ctrl+K, and from the header search button.
 * Searches posts via the WordPress REST API, with static nav fallbacks.
 */

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

const state = {
	open: false,
	query: '',
	active: 0,
	results: [],
	searching: false,
};

let abortController = null;
let searchTimer = null;
let palette;
let input;
let listEl;
let emptyEl;

function filteredNav( q ) {
	if ( ! q ) {
		return NAV_ITEMS.concat( ACTIONS );
	}
	const lower = q.toLowerCase();
	return NAV_ITEMS.concat( ACTIONS ).filter( ( it ) =>
		it.label.toLowerCase().includes( lower )
	);
}

async function searchPosts( q ) {
	if ( ! q ) {
		return [];
	}
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
			return [];
		}
		const data = await res.json();
		return data.map( ( item ) => ( {
			group: String( item.subtype || 'post' ).replace( /^./, ( c ) =>
				c.toUpperCase()
			),
			glyph: '·',
			label: String( item.title || '' ),
			href: String( item.url || '#' ),
		} ) );
	} catch ( e ) {
		return [];
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
	a.setAttribute( 'role', 'option' );
	a.dataset.index = String( index );
	a.href = it.href;

	const glyph = document.createElement( 'span' );
	glyph.className = 'ik-cmdk__item-glyph';
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

	while ( listEl.firstChild ) {
		listEl.removeChild( listEl.firstChild );
	}

	if ( items.length === 0 ) {
		emptyEl.hidden = false;
		emptyEl.textContent = state.searching
			? 'Searching…'
			: 'No matches. Try “wordpress”, “performance”, or “resume”.';
		return;
	}
	emptyEl.hidden = true;

	let lastGroup = null;
	items.forEach( ( it, i ) => {
		if ( it.group !== lastGroup ) {
			const heading = document.createElement( 'div' );
			heading.className = 'ik-cmdk__group-title';
			heading.textContent = it.group;
			listEl.appendChild( heading );
			lastGroup = it.group;
		}
		listEl.appendChild( buildItemEl( it, i, i === state.active ) );
	} );

	const activeEl = listEl.querySelector( '.ik-cmdk__item.is-active' );
	if ( activeEl && typeof activeEl.scrollIntoView === 'function' ) {
		activeEl.scrollIntoView( { block: 'nearest' } );
	}
}

function activate( index ) {
	state.active = index;
	listEl
		.querySelectorAll( '.ik-cmdk__item.is-active' )
		.forEach( ( el ) => el.classList.remove( 'is-active' ) );
	const el = listEl.querySelector( '[data-index="' + index + '"]' );
	if ( el ) {
		el.classList.add( 'is-active' );
		el.scrollIntoView( { block: 'nearest' } );
	}
}

function runActive() {
	const items = combine();
	const it = items[ state.active ];
	if ( it && it.href ) {
		window.location.href = it.href;
	}
}

function open() {
	if ( state.open ) {
		return;
	}
	state.open = true;
	palette.hidden = false;
	window.requestAnimationFrame( () => palette.classList.add( 'is-open' ) );
	document.body.style.overflow = 'hidden';
	state.query = '';
	state.results = [];
	state.active = 0;
	input.value = '';
	render();
	setTimeout( () => input && input.focus(), 30 );
}

function close() {
	if ( ! state.open ) {
		return;
	}
	state.open = false;
	palette.classList.remove( 'is-open' );
	palette.hidden = true;
	document.body.style.overflow = '';
}

function onKeydown( e ) {
	if ( ( e.metaKey || e.ctrlKey ) && ( e.key === 'k' || e.key === 'K' ) ) {
		e.preventDefault();
		if ( state.open ) {
			close();
		} else {
			open();
		}
		return;
	}
	if ( ! state.open ) {
		return;
	}
	if ( e.key === 'Escape' ) {
		e.preventDefault();
		close();
		return;
	}
	if ( e.key === 'ArrowDown' ) {
		e.preventDefault();
		const items = combine();
		activate( Math.min( items.length - 1, state.active + 1 ) );
		return;
	}
	if ( e.key === 'ArrowUp' ) {
		e.preventDefault();
		activate( Math.max( 0, state.active - 1 ) );
		return;
	}
	if ( e.key === 'Enter' ) {
		e.preventDefault();
		runActive();
	}
}

function onInput( e ) {
	state.query = e.target.value;
	state.active = 0;
	state.results = [];
	render();

	if ( searchTimer ) {
		clearTimeout( searchTimer );
	}
	const q = state.query.trim();
	if ( ! q ) {
		return;
	}
	state.searching = true;
	searchTimer = setTimeout( async () => {
		const results = await searchPosts( q );
		if ( q !== state.query.trim() ) {
			return;
		}
		state.results = results;
		state.searching = false;
		render();
	}, 180 );
}

function buildSkeleton() {
	const overlay = document.createElement( 'div' );
	overlay.className = 'ik-cmdk__overlay';
	overlay.hidden = true;
	overlay.setAttribute( 'role', 'dialog' );
	overlay.setAttribute( 'aria-modal', 'true' );
	overlay.setAttribute( 'aria-label', 'Command palette' );

	const card = document.createElement( 'div' );
	card.className = 'ik-cmdk';

	const inputRow = document.createElement( 'div' );
	inputRow.className = 'ik-cmdk__input-row';
	const caret = document.createElement( 'span' );
	caret.className = 'ik-cmdk__caret';
	caret.setAttribute( 'aria-hidden', 'true' );
	caret.textContent = '›';
	const inp = document.createElement( 'input' );
	inp.className = 'ik-cmdk__input';
	inp.type = 'search';
	inp.placeholder = 'Search articles, topics, projects, or jump to a page…';
	inp.autocomplete = 'off';
	inp.spellcheck = false;
	const esc = document.createElement( 'span' );
	esc.className = 'ik-cmdk__esc';
	esc.textContent = 'esc';
	inputRow.append( caret, inp, esc );

	const list = document.createElement( 'div' );
	list.className = 'ik-cmdk__list';
	list.setAttribute( 'role', 'listbox' );

	const empty = document.createElement( 'div' );
	empty.className = 'ik-cmdk__empty';
	empty.hidden = true;

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

	card.append( inputRow, list, empty, footer );
	overlay.appendChild( card );

	return { overlay, card, input: inp, list, empty };
}

function build() {
	if ( palette ) {
		return;
	}
	const parts = buildSkeleton();
	palette = parts.overlay;
	input = parts.input;
	listEl = parts.list;
	emptyEl = parts.empty;
	document.body.appendChild( palette );

	palette.addEventListener( 'click', ( e ) => {
		if ( e.target === palette ) {
			close();
		}
	} );
	listEl.addEventListener( 'mouseover', ( e ) => {
		const target = e.target.closest( '.ik-cmdk__item' );
		if ( target ) {
			activate( Number( target.dataset.index ) );
		}
	} );
	input.addEventListener( 'input', onInput );
}

function wireTriggers() {
	document.querySelectorAll( '.ik-header__cmd' ).forEach( ( btn ) => {
		btn.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			open();
		} );
	} );
}

function init() {
	build();
	wireTriggers();
	document.addEventListener( 'keydown', onKeydown );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
