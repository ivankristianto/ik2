<?php
/**
 * Drop WordPress's emoji detection on the front end.
 *
 * Core ships an inline detection module, a `wp-emoji-settings` JSON blob and a
 * small stylesheet on every page so that platforms without native emoji fonts
 * can swap in Twemoji images. Every browser this site cares about renders
 * emoji natively, and the theme's own chrome never uses emoji, so the ~13 KB
 * of inline script plus the `s.w.org` DNS prefetch are dead weight on every
 * page load.
 *
 * @package IK2
 */

declare(strict_types=1);

namespace IK2\Theme\Emoji;

defined( 'ABSPATH' ) || exit;

/**
 * Unhook the front-end and embed emoji output core registers in
 * `wp-includes/default-filters.php`.
 *
 * `wp_print_styles` → `print_emoji_styles` is a back-compat hook that core
 * itself unhooks from `wp_enqueue_emoji_styles()`; removing both means it is
 * gone whether or not that function runs. The `emoji_svg_url` filter returning
 * false removes the `<link rel="dns-prefetch" href="//s.w.org">` resource hint
 * core adds for the Twemoji CDN.
 */
function bootstrap(): void {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'embed_head', 'print_emoji_detection_script' );
	remove_action( 'enqueue_embed_scripts', 'wp_enqueue_emoji_styles' );
	add_filter( 'emoji_svg_url', '__return_false' );
}
