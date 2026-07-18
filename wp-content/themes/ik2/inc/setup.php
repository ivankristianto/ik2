<?php
/**
 * Theme supports and editor configuration.
 *
 * @package IK2
 */

declare(strict_types=1);

namespace IK2\Theme\Setup;

defined( 'ABSPATH' ) || exit;

/**
 * Register hooks owned by this module.
 */
function bootstrap(): void {
	add_action( 'after_setup_theme', __NAMESPACE__ . '\\register_theme_supports' );
	add_action( 'init', __NAMESPACE__ . '\\register_primary_nav_menu' );
}

/**
 * Declare theme supports, editor styles, and load the theme text domain.
 */
function register_theme_supports(): void {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'editor-styles' );
	add_theme_support(
		'html5',
		[ 'style', 'script', 'comment-form', 'comment-list', 'gallery', 'caption' ]
	);

	// Feed the front-end stylesheet into the editor canvas (scoped to
	// `.editor-styles-wrapper`, loaded inside the iframe only) so block
	// previews match the front end without leaking into the editor chrome.
	// editor.css holds the small set of editor-only overrides and loads last.
	add_editor_style( [ 'build/style-index.css', 'build/editor.css' ] );

	load_theme_textdomain( 'ik2', __DIR__ . '/../languages' );
}

/**
 * Register the Primary nav menu location and seed an initial menu on first run.
 */
function register_primary_nav_menu(): void {
	register_nav_menu( 'primary', __( 'Primary', 'ik2' ) );

	if ( is_admin() || wp_doing_ajax() ) {
		return;
	}

	if ( wp_get_nav_menu_object( 'IK2 Primary' ) !== false ) {
		return;
	}

	$menu_id = wp_create_nav_menu( 'IK2 Primary' );

	if ( is_wp_error( $menu_id ) ) {
		return;
	}

	$items = [
		[
			'title' => 'Home',
			'url'   => home_url( '/' ),
		],
		[
			'title' => 'Articles',
			'url'   => home_url( '/articles' ),
		],
		[
			'title' => 'Projects',
			'url'   => home_url( '/projects' ),
		],
		[
			'title' => 'Speaking',
			'url'   => home_url( '/speaking' ),
		],
		[
			'title' => 'About',
			'url'   => home_url( '/about' ),
		],
		[
			'title' => 'Contact',
			'url'   => home_url( '/contact' ),
		],
	];

	foreach ( $items as $item ) {
		wp_update_nav_menu_item(
			$menu_id,
			0,
			[
				'menu-item-title'  => $item['title'],
				'menu-item-url'    => $item['url'],
				'menu-item-status' => 'publish',
			]
		);
	}
}
