<?php
/**
 * Theme supports and editor configuration.
 *
 * @package IK2
 */

declare(strict_types=1);

namespace IK2\Theme;

defined( 'ABSPATH' ) || exit;

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

		add_editor_style( 'build/editor.css' );

		load_theme_textdomain( 'ik2', __DIR__ . '/../languages' );
	}
);
