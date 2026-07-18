<?php
/**
 * Register block style variations used inside single-post content.
 *
 * Three callout variants for core/group: Note, Updated, Outdated. Authors pick
 * a style from the block sidebar and WordPress adds is-style-callout-* to the
 * group's wrapper.
 *
 * @package IK2
 */

declare(strict_types=1);

namespace IK2\Theme\BlockStyles;

defined( 'ABSPATH' ) || exit;

/**
 * Register hooks owned by this module.
 */
function bootstrap(): void {
	add_action( 'init', __NAMESPACE__ . '\\register_callout_block_styles' );
	add_action( 'init', __NAMESPACE__ . '\\register_button_block_styles' );
}

/**
 * Register button variants.
 *
 * Hero CTA: the oversized text link with the animated Signal underline used
 * as the homepage hero's primary action.
 */
function register_button_block_styles(): void {
	register_block_style(
		'core/button',
		[
			'name'  => 'hero-cta',
			'label' => __( 'Hero CTA', 'ik2' ),
		]
	);
}

/**
 * Register the three callout variants on core/group.
 */
function register_callout_block_styles(): void {
	register_block_style(
		'core/group',
		[
			'name'  => 'callout-note',
			'label' => __( 'Callout — Note', 'ik2' ),
		]
	);
	register_block_style(
		'core/group',
		[
			'name'  => 'callout-updated',
			'label' => __( 'Callout — Updated', 'ik2' ),
		]
	);
	register_block_style(
		'core/group',
		[
			'name'  => 'callout-outdated',
			'label' => __( 'Callout — Outdated', 'ik2' ),
		]
	);
}
