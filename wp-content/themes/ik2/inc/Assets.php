<?php
/**
 * Front-end asset enqueues. Stylesheet header lives in style.css; the real
 * CSS comes from build/style-index.css produced by wp-scripts.
 *
 * @package IK2
 */

declare(strict_types=1);

namespace IK2\Theme;

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		$build_dir = __DIR__ . '/../build';
		$build_uri = get_theme_file_uri( 'build' );

		wp_enqueue_style( 'dashicons' );

		if ( file_exists( $build_dir . '/index.js' ) ) {
			wp_enqueue_script(
				'ik2',
				$build_uri . '/index.js',
				array(),
				(string) filemtime( $build_dir . '/index.js' ),
				true
			);
		}
	}
);

// Theme-bundled favicon. Wired as the WP "Site Icon" so it drives
// `<link rel=icon>`, `<link rel=apple-touch-icon>`, and the admin
// chrome. An uploaded Site Icon (Settings → General) still wins.
add_filter(
	'get_site_icon_url',
	static function ( string $url ): string {
		return '' === $url ? get_theme_file_uri( 'assets/favicon/favicon.svg' ) : $url;
	}
);

// Theme stylesheet — enqueued on the front-end and inside the block editor iframe.
add_action(
	'enqueue_block_assets',
	static function (): void {
		$build_dir = __DIR__ . '/../build';
		$build_uri = get_theme_file_uri( 'build' );

		if ( ! file_exists( $build_dir . '/style-index.css' ) ) {
			return;
		}

		$style_path = $build_dir . '/style-index.css';

		wp_enqueue_style(
			'ik2',
			$build_uri . '/style-index.css',
			array(),
			(string) filemtime( $style_path )
		);

		// Lets WordPress inline the stylesheet inside the block editor iframe.
		wp_style_add_data( 'ik2', 'path', $style_path );
	}
);
