/**
 * IK2 — Articles filters (Interactivity API view module).
 *
 * Provides a synchronous `navigate` action used by the archive filter pills.
 * It calls `preventDefault` on the click event and hands the link's
 * href to the core router, which fetches the new page and swaps the
 * matching `data-wp-router-region` in place — no full reload.
 *
 * The shared `ik-articles` router region includes both the archive
 * header and the grid, so topic and format changes can update in place.
 */
import { store, getElement, withSyncEvent } from '@wordpress/interactivity';

const isValidLink = ( ref ) =>
	ref &&
	ref instanceof window.HTMLAnchorElement &&
	ref.href &&
	( ! ref.target || ref.target === '_self' ) &&
	ref.origin === window.location.origin;

const isValidEvent = ( event ) =>
	event.button === 0 &&
	! event.metaKey &&
	! event.ctrlKey &&
	! event.altKey &&
	! event.shiftKey &&
	! event.defaultPrevented;

store( 'ik2/articles-filters', {
	actions: {
		navigate: withSyncEvent( function* ( event ) {
			const { ref } = getElement();
			if ( ! isValidLink( ref ) || ! isValidEvent( event ) ) {
				return;
			}
			event.preventDefault();
			const { actions } = yield import(
				'@wordpress/interactivity-router'
			);
			yield actions.navigate( ref.href );

			// The router has swapped the region in place; keyboard and
			// screen-reader users get no page reload to signal the change.
			// Move focus to the refreshed results so the new context (and the
			// aria-live count) is announced and keyboard focus stays sensible.
			const results = document.querySelector(
				'.ik-articles-archive__query'
			);
			if ( results ) {
				results.setAttribute( 'tabindex', '-1' );
				results.focus( { preventScroll: true } );
			}
		} ),
	},
} );
