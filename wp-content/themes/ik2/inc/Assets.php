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

		if ( file_exists( $build_dir . '/style-index.css' ) ) {
			wp_enqueue_style(
				'ik2',
				$build_uri . '/style-index.css',
				array(),
				(string) filemtime( $build_dir . '/style-index.css' )
			);
		}

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
