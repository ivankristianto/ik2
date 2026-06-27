<?php
/**
 * Front-end asset enqueues. Stylesheet header lives in style.css; the real
 * CSS comes from build/style-index.css produced by wp-scripts.
 *
 * @package IK2
 */

declare(strict_types=1);

namespace IK2\Theme\Assets;

defined( 'ABSPATH' ) || exit;

/**
 * Register hooks owned by this module.
 */
function bootstrap(): void {
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_frontend_scripts' );
	add_filter( 'get_site_icon_url', __NAMESPACE__ . '\\fallback_site_icon_url' );
	add_action( 'enqueue_block_assets', __NAMESPACE__ . '\\enqueue_theme_stylesheet' );
	add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\enqueue_block_editor_previews' );
}

/**
 * Enqueue the theme's front-end JS bundle and dashicons.
 */
function enqueue_frontend_scripts(): void {
	$build_dir = __DIR__ . '/../build';
	$build_uri = get_theme_file_uri( 'build' );

	wp_enqueue_style( 'dashicons' );

	if ( file_exists( $build_dir . '/index.js' ) ) {
		wp_enqueue_script(
			'ik2',
			$build_uri . '/index.js',
			[],
			(string) filemtime( $build_dir . '/index.js' ),
			true
		);
	}
}

/**
 * Theme-bundled favicon. Wired as the WP "Site Icon" so it drives
 * `<link rel=icon>`, `<link rel=apple-touch-icon>`, and the admin
 * chrome. An uploaded Site Icon (Settings → General) still wins.
 *
 * @param string $url Site icon URL resolved by core.
 */
function fallback_site_icon_url( string $url ): string {
	return $url === '' ? get_theme_file_uri( 'assets/favicon/favicon.svg' ) : $url;
}

/**
 * Theme stylesheet — enqueued on the front-end and inside the block editor iframe.
 */
function enqueue_theme_stylesheet(): void {
	$build_dir = __DIR__ . '/../build';
	$build_uri = get_theme_file_uri( 'build' );

	if ( ! file_exists( $build_dir . '/style-index.css' ) ) {
		return;
	}

	$style_path = $build_dir . '/style-index.css';

	wp_enqueue_style(
		'ik2',
		$build_uri . '/style-index.css',
		[],
		(string) filemtime( $style_path )
	);

	// Lets WordPress inline the stylesheet inside the block editor iframe.
	wp_style_add_data( 'ik2', 'path', $style_path );
}

/**
 * Enqueue the shared editor previews script so server-rendered theme blocks
 * have a `ServerSideRender`-backed edit component in the site editor.
 */
function enqueue_block_editor_previews(): void {
	$script_path = __DIR__ . '/../assets/js/block-editor-previews.js';
	$asset_path  = __DIR__ . '/../assets/js/block-editor-previews.asset.php';

	if ( ! file_exists( $script_path ) ) {
		return;
	}

	$asset = file_exists( $asset_path )
		? require $asset_path
		: [
			'dependencies' => [],
			'version'      => (string) filemtime( $script_path ),
		];

	wp_enqueue_script(
		'ik2-block-editor-previews',
		get_theme_file_uri( 'assets/js/block-editor-previews.js' ),
		$asset['dependencies'] ?? [],
		$asset['version'] ?? (string) filemtime( $script_path ),
		true
	);
}
