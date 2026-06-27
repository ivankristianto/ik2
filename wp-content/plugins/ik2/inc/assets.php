<?php
/**
 * Front-end and editor asset enqueues for plugin-owned scripts/styles.
 * Block-specific assets are enqueued automatically by `register_block_type()`
 * via each block's `block.json`; this file handles plugin-wide bundles.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\Assets;

use const IK2\Plugin\PLUGIN_DIR;
use const IK2\Plugin\PLUGIN_FILE;

defined( 'ABSPATH' ) || exit;

/**
 * Register hooks owned by this module.
 */
function bootstrap(): void {
	add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\enqueue_editor_assets' );
}

/**
 * Enqueue the plugin's block editor JS bundle when the build is present.
 */
function enqueue_editor_assets(): void {
	$asset_file = PLUGIN_DIR . '/build/editor.asset.php';
	$script     = PLUGIN_DIR . '/build/editor.js';

	if ( ! file_exists( $asset_file ) || ! file_exists( $script ) ) {
		return;
	}

	$asset = require $asset_file;

	wp_enqueue_script(
		'ik2-plugin-editor',
		plugins_url( 'build/editor.js', PLUGIN_FILE ),
		$asset['dependencies'] ?? [],
		$asset['version'] ?? (string) filemtime( $script ),
		true
	);
}
