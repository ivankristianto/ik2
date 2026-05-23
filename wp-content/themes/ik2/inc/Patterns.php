<?php
/**
 * Block pattern category registration. Individual patterns are auto-
 * registered by WordPress from the theme's patterns/ directory.
 *
 * @package IK2
 */

declare(strict_types=1);

namespace IK2\Theme;

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	static function (): void {
		register_block_pattern_category(
			'ik2-home',
			array( 'label' => __( 'IK2 — Home', 'ik2' ) )
		);
		register_block_pattern_category(
			'ik2-archive',
			array( 'label' => __( 'IK2 — Archive', 'ik2' ) )
		);
		register_block_pattern_category(
			'ik2-page',
			array( 'label' => __( 'IK2 — Page', 'ik2' ) )
		);
	}
);
