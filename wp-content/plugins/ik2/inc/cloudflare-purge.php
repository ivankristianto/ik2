<?php
/**
 * Add the site's own listing pages to the Cloudflare plugin's purge list.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CloudflarePurge;

defined( 'ABSPATH' ) || exit;

const ARTICLES_PAGE_PATH = 'articles';

// The Cloudflare plugin purges three pages of the blog index, so match it.
const ARTICLES_PAGES = 3;

/**
 * Register hooks owned by this module.
 */
function bootstrap(): void {
	add_filter( 'cloudflare_purge_by_url', __NAMESPACE__ . '\\add_listing_urls', 10, 2 );
}

/**
 * Append /articles/, its next pages and the Yoast sitemaps when a post changes.
 *
 * @param mixed $urls    URLs the plugin is about to purge.
 * @param mixed $post_id Post that changed.
 * @return mixed
 */
function add_listing_urls( $urls, $post_id ) {
	// The plugin only knows home and page_for_posts; /articles/ is a plain page with its own query loop.
	if ( ! is_array( $urls ) || get_post_type( (int) $post_id ) !== 'post' ) {
		return $urls;
	}

	return array_values( array_unique( array_merge( $urls, listing_urls() ) ) );
}

/**
 * Listing URLs the Cloudflare plugin doesn't purge on its own.
 *
 * @return string[]
 */
function listing_urls(): array {
	$urls     = [];
	$articles = get_page_by_path( ARTICLES_PAGE_PATH );

	if ( $articles instanceof \WP_Post ) {
		$base   = trailingslashit( (string) get_permalink( $articles ) );
		$urls[] = $base;

		for ( $page = 2; $page <= ARTICLES_PAGES; $page++ ) {
			$urls[] = $base . 'page/' . $page . '/';
		}
	}

	if ( defined( 'WPSEO_VERSION' ) ) {
		$urls[] = home_url( '/sitemap_index.xml' );
		$urls[] = home_url( '/post-sitemap.xml' );
	}

	return $urls;
}
