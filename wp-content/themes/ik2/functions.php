<?php
/**
 * IK2 theme bootstrap.
 *
 * @package IK2
 */

declare(strict_types=1);

namespace IK2\Theme;

defined( 'ABSPATH' ) || exit;

const VERSION = '0.1.0';

/**
 * Register theme supports.
 */
add_action(
	'after_setup_theme',
	static function (): void {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'editor-styles' );
		add_theme_support(
			'html5',
			array( 'style', 'script', 'comment-form', 'comment-list', 'gallery', 'caption' )
		);

		load_theme_textdomain( 'ik2', __DIR__ . '/languages' );
	}
);

/**
 * Enqueue the theme stylesheet.
 */
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		wp_enqueue_style(
			'ik2',
			get_stylesheet_uri(),
			array(),
			VERSION
		);
	}
);
