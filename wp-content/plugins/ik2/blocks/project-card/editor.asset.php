<?php
/**
 * Hand-written asset manifest for the project-card editor script.
 *
 * The editor.js next to this file is hand-authored vanilla JS that uses
 * WordPress globals (`wp.blocks`, `wp.element`, `wp.blockEditor`,
 * `wp.components`, `wp.i18n`, `wp.serverSideRender`); listing the matching
 * script handles here ensures core enqueues them before our file runs.
 *
 * @package IK2\Plugin
 */

return array(
	'dependencies' => array(
		'wp-blocks',
		'wp-element',
		'wp-block-editor',
		'wp-components',
		'wp-i18n',
		'wp-server-side-render',
	),
	'version'      => (string) filemtime( __DIR__ . '/editor.js' ),
);
