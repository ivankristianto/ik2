/**
 * IK2 — Articles filters (Interactivity API view module).
 *
 * Registers the `ik2/articles-filters` store namespace and imports
 * the core router so format pills can call `core/router::actions.navigate`
 * via the `data-wp-on--click="actions.navigate"` directive emitted by
 * the server-rendered pill markup.
 */
import { store } from '@wordpress/interactivity';
import '@wordpress/interactivity-router';

store( 'ik2/articles-filters', {} );
