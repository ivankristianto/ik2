<?php
/**
 * Hand-written asset manifest for the home-projects-preview editor script.
 *
 * The editor.js next to this file is hand-authored vanilla JS that uses
 * WordPress globals (`wp.blocks`, `wp.element`, `wp.blockEditor`, …);
 * listing the matching script handles here ensures core enqueues them
 * before our file runs.
 *
 * @package IK2
 */

return array(
	'dependencies' => array(
		'wp-blocks',
		'wp-element',
		'wp-block-editor',
		'wp-components',
		'wp-data',
		'wp-i18n',
	),
	'version'      => (string) filemtime( __DIR__ . '/editor.js' ),
);
