<?php
/**
 * Block registration. Auto-registers every block whose `block.json` lives
 * under `build/blocks/<name>/` (preferred — wp-scripts output) and falls
 * back to `blocks/<name>/` for hand-authored blocks without a build step.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\Blocks;

use const IK2\Plugin\PLUGIN_DIR;

defined( 'ABSPATH' ) || exit;

/**
 * Register hooks owned by this module.
 */
function bootstrap(): void {
	add_action( 'init', __NAMESPACE__ . '\\register_block_types' );
}

/**
 * Register every block whose `block.json` lives under build/blocks/ or blocks/.
 */
function register_block_types(): void {
	$candidates = [
		PLUGIN_DIR . '/build/blocks',
		PLUGIN_DIR . '/blocks',
	];

	foreach ( $candidates as $base ) {
		if ( ! is_dir( $base ) ) {
			continue;
		}

		$dirs = glob( $base . '/*', GLOB_ONLYDIR );

		if ( ! is_array( $dirs ) ) {
			continue;
		}

		foreach ( $dirs as $dir ) {
			if ( file_exists( $dir . '/block.json' ) ) {
				register_block_type( $dir );
			}
		}
	}
}
