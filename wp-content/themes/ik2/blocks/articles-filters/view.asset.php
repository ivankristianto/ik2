<?php
/**
 * Hand-written asset manifest for the articles-filters IAPI view module.
 *
 * The view.js next to this file is plain ESM (no bundler step). Listing the
 * dependencies here gets `@wordpress/interactivity` and the router added to
 * WordPress's script-module importmap so the imports resolve at runtime.
 *
 * @package IK2
 */

return array(
	'dependencies' => array(
		'@wordpress/interactivity',
		'@wordpress/interactivity-router',
	),
	'version'      => (string) filemtime( __DIR__ . '/view.js' ),
);
