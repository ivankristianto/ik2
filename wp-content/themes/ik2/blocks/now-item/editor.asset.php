<?php
/**
 * Hand-written asset manifest for the now-item editor script.
 *
 * The editor.js next to this file is hand-authored vanilla JS that uses
 * WordPress globals (`wp.blocks`, `wp.element`, `wp.blockEditor`, …);
 * listing the matching script handles here ensures core enqueues them
 * before our file runs.
 *
 * @package IK2
 */

return [
	'dependencies' => [
		'wp-blocks',
		'wp-element',
		'wp-block-editor',
		'wp-i18n',
	],
	'version'      => (string) filemtime( __DIR__ . '/editor.js' ),
];
