<?php
/**
 * 301s for URLs from the old site that now 404: dated permalinks, renamed pages, old upload paths.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\LegacyRedirects;

defined( 'ABSPATH' ) || exit;

/**
 * Old path => new path, for pages that changed slug.
 */
const RENAMED = [
	'/about-me/' => '/about/',
];

/**
 * Register hooks owned by this module.
 */
function bootstrap(): void {
	add_action( 'template_redirect', __NAMESPACE__ . '\\redirect_legacy_url', 5 );
}

/**
 * On a 404, send a known legacy URL to its new home in one hop.
 */
function redirect_legacy_url(): void {
	if ( ! is_404() ) {
		return;
	}

	$target = legacy_target( request_path() );

	if ( null === $target ) {
		return;
	}

	wp_safe_redirect( $target, 301, 'IK2 legacy redirect' );
	exit;
}

/**
 * The request path relative to the site root, with a trailing slash on non-file paths.
 */
function request_path(): string {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	$base = untrailingslashit( (string) wp_parse_url( home_url(), PHP_URL_PATH ) );

	if ( '' !== $base && str_starts_with( $path, $base ) ) {
		$path = substr( $path, strlen( $base ) );
	}

	return '/' . ltrim( $path, '/' );
}

/**
 * Where a legacy path lives now, or null to let it 404.
 *
 * @param string $path Request path, e.g. /2009/04/some-slug/.
 */
function legacy_target( string $path ): ?string {
	$key = trailingslashit( $path );

	if ( isset( RENAMED[ $key ] ) ) {
		return home_url( RENAMED[ $key ] );
	}

	if ( preg_match( '#^/\d{4}/\d{2}/(?:\d{2}/)?([a-z0-9-]+)/?$#i', $path, $m ) ) {
		return post_url_by_slug( $m[1] );
	}

	if ( preg_match( '#^/(?:wp-content/)?uploads/\d{4}/\d{2}/([^/]+\.(?:jpe?g|gif|png|webp|svg|pdf))$#i', $path, $m ) ) {
		return attachment_url_by_filename( $m[1] );
	}

	return null;
}

/**
 * Permalink of the published post with this slug.
 *
 * @param string $slug Post slug from the old dated permalink.
 */
function post_url_by_slug( string $slug ): ?string {
	$post = get_page_by_path( sanitize_title( $slug ), OBJECT, 'post' );

	if ( ! $post instanceof \WP_Post || 'publish' !== $post->post_status ) {
		return null;
	}

	$url = get_permalink( $post );

	return false === $url ? null : $url;
}

/**
 * URL of the media-library copy of an old upload, matched on its file name.
 *
 * The migration re-uploaded media under new year/month folders, so the
 * old path differs only in the folder. Size suffixes (-300x200) map to
 * the full-size file.
 *
 * @param string $filename Old upload file name.
 */
function attachment_url_by_filename( string $filename ): ?string {
	global $wpdb;

	$filename = sanitize_file_name( (string) preg_replace( '/-\d+x\d+(?=\.[a-z0-9]+$)/i', '', $filename ) );
	$cache    = 'ik2_legacy_media_' . md5( $filename );
	$id       = wp_cache_get( $cache, 'ik2' );

	if ( false === $id ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- one indexed meta lookup per legacy 404, cached below.
		$id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s ORDER BY post_id DESC LIMIT 1",
				'%/' . $wpdb->esc_like( $filename )
			)
		);
		wp_cache_set( $cache, $id, 'ik2', DAY_IN_SECONDS );
	}

	if ( $id <= 0 ) {
		return null;
	}

	$url = wp_get_attachment_url( (int) $id );

	return false === $url ? null : $url;
}
