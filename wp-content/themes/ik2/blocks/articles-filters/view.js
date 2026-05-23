/**
 * IK2 — Articles filters (Interactivity API view module).
 *
 * Provides a synchronous `navigate` action used by the format pills.
 * It calls `preventDefault` on the click event and hands the link's
 * href to the core router, which fetches the new page and swaps the
 * matching `data-wp-router-region` in place — no full reload.
 *
 * Topic pills are plain `<a>` links with no IAPI directive, so they
 * navigate normally and the whole page reloads (the archive context
 * itself changes, so a real navigation is honest).
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
		} ),
	},
} );
