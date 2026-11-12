<?php
/**
 * Hand-written asset manifest for the lab-tool editor script (vanilla JS on WordPress globals).
 *
 * @package IK2\Plugin
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
