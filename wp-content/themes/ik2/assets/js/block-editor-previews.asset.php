<?php
/**
 * Hand-written asset manifest for the shared block editor previews script.
 *
 * The block-editor-previews.js next to this file is hand-authored vanilla JS
 * that uses WordPress globals (`wp.blocks`, `wp.element`, `wp.blockEditor`,
 * `wp.serverSideRender`). Listing the matching script handles here ensures
 * core enqueues them before our file runs.
 *
 * @package IK2
 */

return [
	'dependencies' => [
		'wp-blocks',
		'wp-element',
		'wp-block-editor',
		'wp-server-side-render',
	],
	'version'      => (string) filemtime( __DIR__ . '/block-editor-previews.js' ),
];
