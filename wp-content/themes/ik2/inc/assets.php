<?php
/**
 * Front-end asset enqueues.
 *
 * The theme's CSS is split for delivery:
 *
 *  - `build/critical.css` is inlined into <head> on every page — the site
 *    chrome (reset, type, layout, header navigation, footer) that first paint
 *    always needs. Inlining removes a render-blocking request from the
 *    critical path, which is the dominant LCP cost on the home page.
 *  - Block styles ship with their blocks (`block.json` `style`) and load only
 *    when the block is on the page.
 *  - Per-template section styles (`build/section-*.css`) are enqueued only on
 *    the templates that use them (see section_slugs_for_request()).
 *  - The command palette stylesheet loads asynchronously — it's chrome that is
 *    never needed for first paint.
 *
 * @package IK2
 */

declare(strict_types=1);

namespace IK2\Theme\Assets;

defined( 'ABSPATH' ) || exit;

const HERO_PORTRAIT = 'assets/images/ivan-portrait.webp';

/**
 * Register hooks owned by this module.
 */
function bootstrap(): void {
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_frontend_scripts' );
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_frontend_styles' );
	add_action( 'wp_head', __NAMESPACE__ . '\\preload_hero_portrait', 1 );
	add_filter( 'style_loader_tag', __NAMESPACE__ . '\\make_palette_style_async', 10, 4 );
	add_filter( 'get_site_icon_url', __NAMESPACE__ . '\\fallback_site_icon_url' );
	add_filter( 'wp_content_img_tag', __NAMESPACE__ . '\\prioritize_hero_portrait' );
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
 * Inline the critical CSS, enqueue the per-template section styles, and load
 * the command palette stylesheet asynchronously.
 */
function enqueue_frontend_styles(): void {
	$build_dir = __DIR__ . '/../build';
	$build_uri = get_theme_file_uri( 'build' );

	// Critical CSS — inlined via a src-less handle so it prints in the normal
	// styles position (after core block styles) and keeps the cascade intact.
	$inline = read_build_css( $build_dir . '/critical.css' );

	// On the front page — the page we optimise LCP for — the section styles are
	// inlined alongside the critical CSS too. That leaves the home page with no
	// render-blocking theme stylesheet at all, so the preloaded hero image no
	// longer waits behind a VeryHigh-priority CSS request on the wire. Other
	// templates keep their section styles as cacheable external files.
	$inline_sections = is_front_page();
	$section_slugs   = section_slugs_for_request();

	if ( $inline_sections ) {
		foreach ( $section_slugs as $slug ) {
			$inline .= read_build_css( $build_dir . "/section-{$slug}.css" );
		}
	}

	if ( $inline !== '' ) {
		wp_register_style( 'ik2-critical', false ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_enqueue_style( 'ik2-critical' );
		wp_add_inline_style( 'ik2-critical', $inline );
	}

	// Per-template section styles (external, cacheable). Depend on the critical
	// handle so they layer on top of it in source order.
	if ( ! $inline_sections ) {
		foreach ( $section_slugs as $slug ) {
			$path = $build_dir . "/section-{$slug}.css";
			if ( ! file_exists( $path ) ) {
				continue;
			}
			wp_enqueue_style(
				"ik2-section-{$slug}",
				$build_uri . "/section-{$slug}.css",
				[ 'ik2-critical' ],
				(string) filemtime( $path )
			);
		}
	}

	// Command palette styles — loaded asynchronously (see
	// make_palette_style_async): the palette is a hidden modal, never part of
	// first paint.
	$palette = $build_dir . '/palette.css';
	if ( file_exists( $palette ) ) {
		wp_enqueue_style(
			'ik2-palette',
			$build_uri . '/palette.css',
			[ 'ik2-critical' ],
			(string) filemtime( $palette )
		);
	}
}

/**
 * Read a built CSS file for inlining. Returns an empty string when the file is
 * missing so callers can concatenate safely.
 *
 * @param string $path Absolute path to a built CSS file.
 * @return string
 */
function read_build_css( string $path ): string {
	if ( ! file_exists( $path ) ) {
		return '';
	}

	$css = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

	return is_string( $css ) ? $css : '';
}

/**
 * The section stylesheet slugs the current request needs.
 *
 * Section styles cover template/pattern compositions that aren't theme blocks
 * (theme blocks ship their own styles). Single projects reuse the single-post
 * base and add the project-specific extras on top.
 *
 * @return array<int,string>
 */
function section_slugs_for_request(): array {
	if ( is_front_page() ) {
		return [ 'home' ];
	}

	if ( is_singular( 'project' ) ) {
		return [ 'single', 'project-single' ];
	}

	if ( is_singular( 'post' ) ) {
		return [ 'single' ];
	}

	if ( is_page( 'about' ) ) {
		return [ 'about' ];
	}

	if ( is_page( 'contact' ) ) {
		return [ 'contact' ];
	}

	if ( is_page( 'resume' ) ) {
		return [ 'resume' ];
	}

	if ( is_page( 'speaking' ) ) {
		return [ 'speaking' ];
	}

	// The Articles page plus every post archive (category, tag, author, date,
	// search, blog index) renders the article-card grid.
	if ( is_page( 'articles' ) || is_home() || is_archive() || is_search() ) {
		return [ 'articles' ];
	}

	return [];
}

/**
 * Preload the home hero portrait so the browser discovers the LCP image
 * immediately instead of after the render-blocking CSS. The single fixed
 * `src` (no srcset) means the preload URL always matches the rendered tag.
 */
function preload_hero_portrait(): void {
	if ( ! is_front_page() ) {
		return;
	}

	printf(
		'<link rel="preload" as="image" href="%s" type="image/webp" fetchpriority="high">' . "\n",
		esc_url( get_theme_file_uri( HERO_PORTRAIT ) )
	);
}

/**
 * Load the command palette stylesheet without blocking render.
 *
 * The `media="print"` + `onload` swap lets the browser fetch the sheet at low
 * priority off the critical path, then apply it once loaded. A <noscript>
 * fallback keeps it working with JavaScript disabled (though the palette
 * itself needs JS to open).
 *
 * @param string $html   The <link> tag HTML.
 * @param string $handle Stylesheet handle.
 * @param string $href   Stylesheet URL.
 * @param string $media  Media attribute.
 * @return string
 */
function make_palette_style_async( string $html, string $handle, string $href, string $media ): string {
	if ( 'ik2-palette' !== $handle ) {
		return $html;
	}

	$async = str_replace(
		" media='" . $media . "'",
		" media='print' onload=\"this.media='all'\"",
		$html
	);

	return $async . '<noscript>' . $html . '</noscript>';
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
