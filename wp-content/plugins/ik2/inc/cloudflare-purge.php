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

// The lab-archive block lists every entry on one page, so there are no paged URLs.
const LAB_PAGE_PATH = 'lab';

/**
 * Register hooks owned by this module.
 */
function bootstrap(): void {
	add_filter( 'cloudflare_purge_by_url', __NAMESPACE__ . '\\add_listing_urls', 10, 2 );
}

/**
 * Append the listing pages that show the changed post: /articles/ for posts, /lab/ for lab entries.
 *
 * @param mixed $urls    URLs the plugin is about to purge.
 * @param mixed $post_id Post that changed.
 * @return mixed
 */
function add_listing_urls( $urls, $post_id ) {
	if ( ! is_array( $urls ) ) {
		return $urls;
	}

	// The plugin only knows home, page_for_posts and post type archives; both indexes are plain pages with their own query.
	switch ( get_post_type( (int) $post_id ) ) {
		case 'post':
			$extra = listing_urls();
			break;
		case 'lab':
			$extra = page_urls( LAB_PAGE_PATH, 1 );
			break;
		default:
			return $urls;
	}

	return array_values( array_unique( array_merge( $urls, $extra ) ) );
}

/**
 * Listing URLs for posts the Cloudflare plugin doesn't purge on its own.
 *
 * @return string[]
 */
function listing_urls(): array {
	$urls = page_urls( ARTICLES_PAGE_PATH, ARTICLES_PAGES );

	if ( defined( 'WPSEO_VERSION' ) ) {
		$urls[] = home_url( '/sitemap_index.xml' );
		$urls[] = home_url( '/post-sitemap.xml' );
	}

	return $urls;
}

/**
 * URL of a page and its paged URLs, or none when the page is missing.
 *
 * @param string $path  Page path, as get_page_by_path() takes it.
 * @param int    $pages Highest page number to include.
 * @return string[]
 */
function page_urls( string $path, int $pages ): array {
	$page = get_page_by_path( $path );

	if ( ! $page instanceof \WP_Post ) {
		return [];
	}

	$base = trailingslashit( (string) get_permalink( $page ) );
	$urls = [ $base ];

	for ( $n = 2; $n <= $pages; $n++ ) {
		$urls[] = $base . 'page/' . $n . '/';
	}

	return $urls;
}
