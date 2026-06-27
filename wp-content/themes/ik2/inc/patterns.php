<?php
/**
 * Block pattern category registration. Individual patterns are auto-
 * registered by WordPress from the theme's patterns/ directory.
 *
 * @package IK2
 */

declare(strict_types=1);

namespace IK2\Theme\Patterns;

defined( 'ABSPATH' ) || exit;

/**
 * Register hooks owned by this module.
 */
function bootstrap(): void {
	add_action( 'init', __NAMESPACE__ . '\\register_pattern_categories' );
}

/**
 * Register theme pattern categories used by patterns under patterns/.
 */
function register_pattern_categories(): void {
	register_block_pattern_category(
		'ik2-home',
		[ 'label' => __( 'IK2 — Home', 'ik2' ) ]
	);
	register_block_pattern_category(
		'ik2-archive',
		[ 'label' => __( 'IK2 — Archive', 'ik2' ) ]
	);
	register_block_pattern_category(
		'ik2-page',
		[ 'label' => __( 'IK2 — Page', 'ik2' ) ]
	);
}
