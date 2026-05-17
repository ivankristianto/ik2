/**
 * IK2 — Command palette (scaffold only).
 *
 * This file is intentionally a no-op. Real wiring (REST search,
 * keyboard handling) lands in a follow-up. Keeping the store
 * declaration here so the Interactivity API runtime is reachable
 * the moment we register the block.
 */
import { store } from '@wordpress/interactivity';

store( 'ik2/cmd-palette', {
	state: {
		isOpen: false,
		query: '',
	},
	actions: {
		toggle() {},
		close() {},
	},
} );
