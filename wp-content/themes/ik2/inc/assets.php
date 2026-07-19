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
	add_filter( 'wp_content_img_tag', __NAMESPACE__ . '\\prioritize_hero_portrait' );
	add_action( 'enqueue_block_assets', __NAMESPACE__ . '\\enqueue_theme_stylesheet' );
	add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\enqueue_block_editor_previews' );
}

/**
 * Enqueue the theme's front-end JS bundle.
 *
 * Dashicons (~62KB) is only loaded when the admin bar is showing. The theme's
 * own markup uses inline SVG and Lucide for iconography, so anonymous visitors
 * (nearly all traffic) never need the dashicons font sheet. Logged-in users
 * still get it for the admin bar's icons.
 */
function enqueue_frontend_scripts(): void {
	$build_dir = __DIR__ . '/../build';
	$build_uri = get_theme_file_uri( 'build' );

	if ( is_admin_bar_showing() ) {
		wp_enqueue_style( 'dashicons' );
	}

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
 * Mark the home hero portrait as the LCP image.
 *
 * The portrait is the largest above-the-fold element on the front page, so it
 * should be fetched eagerly at high priority instead of at default priority.
 * Doing this at render time (rather than baking attributes into the image
 * block markup) keeps the block valid in the editor. Runs on the last filter
 * in `wp_filter_content_tags()`, so it overrides any `loading="lazy"` core
 * assigned to the tag.
 *
 * @param string $image The full `<img>` tag HTML.
 * @return string
 */
function prioritize_hero_portrait( string $image ): string {
	if ( ! is_front_page() || ! str_contains( $image, 'ivan-portrait' ) ) {
		return $image;
	}

	$image = preg_replace( '/\s(?:loading|fetchpriority)="[^"]*"/', '', $image );

	return str_replace( '<img ', '<img fetchpriority="high" loading="eager" ', $image );
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
 * Theme stylesheet — front-end only.
 *
 * `enqueue_block_assets` fires on the front end *and* in the admin block
 * editor. In the editor it loads into the outer editor document, so the
 * theme's bare element selectors (`body`, headings, `button`, `svg`) would
 * leak into the editor chrome — restyling the inspector, toolbars, and
 * sidebar. We bail in the admin and feed the same CSS into the editor
 * canvas (scoped to `.editor-styles-wrapper`, iframe only) via
 * `add_editor_style()` in inc/setup.php instead.
 */
function enqueue_theme_stylesheet(): void {
	if ( is_admin() ) {
		return;
	}

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
