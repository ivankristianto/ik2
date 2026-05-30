<?php
/**
 * Theme block registration and Query Loop filter behaviour.
 *
 * @package IK2
 */

declare(strict_types=1);

namespace IK2\Theme\Blocks;

defined( 'ABSPATH' ) || exit;

const ARTICLES_PAGE_SLUG = 'articles';
const ARTICLES_QUERY_ID  = 42;
const ARCHIVE_QUERY_ID   = 43;
const FORMAT_SLUGS       = array( 'guide', 'note', 'experiment' );
const REWRITE_VERSION    = 2;

/**
 * Register hooks owned by this module.
 */
function bootstrap(): void {
	add_action( 'init', __NAMESPACE__ . '\\register_theme_blocks' );
	add_filter( 'query_loop_block_query_vars', __NAMESPACE__ . '\\filter_query_loop_format', 10, 2 );
	add_action( 'pre_get_posts', __NAMESPACE__ . '\\narrow_archive_query_by_format' );
	add_action( 'parse_request', __NAMESPACE__ . '\\stash_archive_context' );
	add_filter( 'query_vars', __NAMESPACE__ . '\\register_format_query_var' );
	add_action( 'init', __NAMESPACE__ . '\\register_archive_rewrite_rules' );
	add_action( 'init', __NAMESPACE__ . '\\maybe_flush_rewrite_rules', 20 );
	add_filter( 'render_block_core/query', __NAMESPACE__ . '\\rewrite_query_loop_pagination_hrefs', 10, 2 );
	add_filter( 'get_canonical_url', __NAMESPACE__ . '\\filter_articles_canonical_url', 10, 2 );
	add_action( 'after_switch_theme', __NAMESPACE__ . '\\flush_rewrite_on_theme_switch' );
}

/**
 * Auto-register every block whose `block.json` lives under `blocks/<name>/`.
 */
function register_theme_blocks(): void {
	$blocks_dir = __DIR__ . '/../blocks';
	$dirs       = glob( $blocks_dir . '/*', GLOB_ONLYDIR );

	if ( ! is_array( $dirs ) ) {
		return;
	}

	foreach ( $dirs as $dir ) {
		if ( file_exists( $dir . '/block.json' ) ) {
			register_block_type( $dir );
		}
	}
}

/**
 * Apply the `format` query var to the Articles and Archive Query Loops.
 *
 * Reads `format` (populated by the rewrite rules in this file) and ANDs
 * a category tax_query onto whatever the Query Loop already has — so
 * inherit:true loops keep their category/tag context and get narrowed
 * by format on top.
 *
 * Allowed format slugs match the pills in the articles-filters block.
 *
 * @param array<string,mixed> $query Query vars for the loop.
 * @param mixed               $block Block instance (typically \WP_Block).
 * @return array<string,mixed>
 */
function filter_query_loop_format( array $query, $block ): array {
	$context  = is_object( $block ) && isset( $block->context ) ? $block->context : array();
	$query_id = isset( $context['queryId'] ) ? (int) $context['queryId'] : 0;

	if ( ARTICLES_QUERY_ID !== $query_id && ARCHIVE_QUERY_ID !== $query_id ) {
		return $query;
	}

	$format = (string) get_query_var( 'format', '' );

	if ( ! ik2_is_valid_format( $format ) ) {
		return $query;
	}

	// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_tax_query
	$query['tax_query'] = ik2_append_format_tax_query(
		isset( $query['tax_query'] ) && is_array( $query['tax_query'] ) ? $query['tax_query'] : array(),
		$format
	);
	// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_tax_query

	return $query;
}

/**
 * Apply the `format` query var to the main query on category/tag archives.
 *
 * The Query Loop block's `inherit:true` mode skips `query_loop_block_query_vars`
 * and uses the main query verbatim, so we narrow the main query itself here.
 * Page archives (where the Query Loop uses inherit:false with queryId 42 or 43)
 * are handled by the filter above.
 *
 * Appends to `tax_query` so the format category is ANDed onto the archive's own
 * category/tag constraint. The companion `parse_query` action below preserves
 * the URL category as the queried object so header patterns still see the
 * archive term and not the format term.
 *
 * @param \WP_Query $wp_query The main query.
 */
function narrow_archive_query_by_format( $wp_query ): void {
	if ( is_admin() || ! $wp_query->is_main_query() ) {
		return;
	}

	if ( ! $wp_query->is_category() && ! $wp_query->is_tag() ) {
		return;
	}

	$format = (string) $wp_query->get( 'format', '' );

	if ( ! ik2_is_valid_format( $format ) ) {
		return;
	}

	$existing_tax_query = $wp_query->get( 'tax_query' );
	if ( ! is_array( $existing_tax_query ) ) {
		$existing_tax_query = array();
	}

	$wp_query->set( 'tax_query', ik2_append_format_tax_query( $existing_tax_query, $format ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
}

/**
 * Stash the URL-derived archive term so consumers can recover it after
 * `pre_get_posts` mutates `category_name` (WP rewrites it to the last
 * category in the appended `tax_query`).
 *
 * Reads `category_name` / `tag` once during `parse_request` — before any
 * tax_query expansion — and stores the slug on a global so the filter
 * block and the archive header pattern can read it back cleanly.
 *
 * @param \WP $wp The WP environment instance.
 */
function stash_archive_context( $wp ): void {
	$GLOBALS['ik2_archive_context'] = array(
		'category' => isset( $wp->query_vars['category_name'] ) ? (string) $wp->query_vars['category_name'] : '',
		'tag'      => isset( $wp->query_vars['tag'] ) ? (string) $wp->query_vars['tag'] : '',
		'format'   => isset( $wp->query_vars['format'] ) ? (string) $wp->query_vars['format'] : '',
	);

	if (
		ARTICLES_PAGE_SLUG === (string) ( $wp->query_vars['pagename'] ?? '' ) &&
		isset( $wp->query_vars['paged'] )
	) {
		$_GET[ 'query-' . ARTICLES_QUERY_ID . '-page' ] = (string) $wp->query_vars['paged']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
}

/**
 * Helper to read the stashed archive context.
 *
 * @return array{category:string,tag:string,format:string}
 */
function ik2_get_archive_context(): array {
	$ctx = isset( $GLOBALS['ik2_archive_context'] ) && is_array( $GLOBALS['ik2_archive_context'] )
		? $GLOBALS['ik2_archive_context']
		: array();

	return array(
		'category' => isset( $ctx['category'] ) ? (string) $ctx['category'] : '',
		'tag'      => isset( $ctx['tag'] ) ? (string) $ctx['tag'] : '',
		'format'   => isset( $ctx['format'] ) ? (string) $ctx['format'] : '',
	);
}

/**
 * Register `format` as a public query var so the rewrite rules below
 * can populate it from the URL path.
 *
 * @param array<int,string> $vars Public query vars.
 * @return array<int,string>
 */
function register_format_query_var( array $vars ): array {
	$vars[] = 'format';
	return $vars;
}

/**
 * Pretty URLs for the format filter.
 *
 *   /articles/format/{slug}/                 → pagename=articles&format={slug}
 *   /category/{cat}/format/{slug}/           → category_name={cat}&format={slug}
 *   /tag/{tag}/format/{slug}/                → tag={tag}&format={slug}
 *
 * `top` priority ensures these win against WP defaults like
 * `/category/{slug}/page/{n}/` and `/category/{slug}/feed/`.
 */
function register_archive_rewrite_rules(): void {
	add_rewrite_rule(
		'^articles/format/([^/]+)/page/([0-9]+)/?$',
		'index.php?pagename=articles&format=$matches[1]&paged=$matches[2]',
		'top'
	);
	add_rewrite_rule(
		'^articles/format/([^/]+)/?$',
		'index.php?pagename=articles&format=$matches[1]',
		'top'
	);
	add_rewrite_rule(
		'^articles/page/([0-9]+)/?$',
		'index.php?pagename=articles&paged=$matches[1]',
		'top'
	);
	add_rewrite_rule(
		'^category/([^/]+)/format/([^/]+)/page/([0-9]+)/?$',
		'index.php?category_name=$matches[1]&format=$matches[2]&paged=$matches[3]',
		'top'
	);
	add_rewrite_rule(
		'^category/([^/]+)/format/([^/]+)/?$',
		'index.php?category_name=$matches[1]&format=$matches[2]',
		'top'
	);
	add_rewrite_rule(
		'^tag/([^/]+)/format/([^/]+)/page/([0-9]+)/?$',
		'index.php?tag=$matches[1]&format=$matches[2]&paged=$matches[3]',
		'top'
	);
	add_rewrite_rule(
		'^tag/([^/]+)/format/([^/]+)/?$',
		'index.php?tag=$matches[1]&format=$matches[2]',
		'top'
	);
}

/**
 * Flush rewrite rules once after deploys that change the archive routes.
 */
function maybe_flush_rewrite_rules(): void {
	if ( (int) get_option( 'ik2_rewrite_version', 0 ) >= REWRITE_VERSION ) {
		return;
	}

	flush_rewrite_rules( false );
	update_option( 'ik2_rewrite_version', REWRITE_VERSION );
}

/**
 * Rewrite the custom articles Query Loop pagination links to clean permalinks.
 *
 * The block core still renders query-string pagination for non-inherited
 * queries. We keep the loop as-is but swap the generated hrefs to the
 * matching pretty route, then let the rewrite rules above map those routes
 * back to the loop's page query var.
 *
 * @param string       $content Rendered block HTML.
 * @param array<mixed> $block   Parsed block data.
 * @return string
 */
function rewrite_query_loop_pagination_hrefs( string $content, array $block ): string {
	$query_id = isset( $block['attrs']['queryId'] ) ? (int) $block['attrs']['queryId'] : 0;

	if ( ARTICLES_QUERY_ID !== $query_id && ARCHIVE_QUERY_ID !== $query_id ) {
		return $content;
	}

	$page_key = 'query-' . $query_id . '-page';
	if ( ! str_contains( $content, $page_key ) ) {
		return $content;
	}

	$processor = new \WP_HTML_Tag_Processor( $content );

	while ( $processor->next_tag( array( 'tag_name' => 'a' ) ) ) {
		$href = $processor->get_attribute( 'href' );

		if ( ! is_string( $href ) || '' === $href ) {
			continue;
		}

		$page = ik2_extract_query_loop_page_from_href( $href, $page_key );

		if ( null === $page ) {
			continue;
		}

		$processor->set_attribute( 'href', ik2_build_archive_pagination_url( $page ) );
	}

	return $processor->get_updated_html();
}

/**
 * Keep the Articles page canonical aligned with the active format/paged route.
 *
 * @param string $url  Canonical URL computed by core.
 * @param mixed  $post Post being canonicalized (typically \WP_Post).
 * @return string
 */
function filter_articles_canonical_url( string $url, $post ): string {
	if ( ! $post instanceof \WP_Post || ARTICLES_PAGE_SLUG !== $post->post_name || ! is_page( ARTICLES_PAGE_SLUG ) ) {
		return $url;
	}

	$format = (string) get_query_var( 'format', '' );
	$page   = max( 1, (int) get_query_var( 'paged', 1 ) );

	if ( 1 === $page && ! ik2_is_valid_format( $format ) ) {
		return $url;
	}

	return ik2_build_archive_pagination_url( $page );
}

/**
 * Flush rewrite rules once when the theme is activated so the new
 * rules above register with the rewrite cache.
 */
function flush_rewrite_on_theme_switch(): void {
	flush_rewrite_rules();
	update_option( 'ik2_rewrite_version', REWRITE_VERSION );
}

/**
 * Check whether the format slug is one the archive UI supports.
 *
 * @param string $format Candidate format slug.
 * @return bool
 */
function ik2_is_valid_format( string $format ): bool {
	return '' !== $format && in_array( $format, FORMAT_SLUGS, true );
}

/**
 * AND the format category onto an existing tax query array.
 *
 * @param array<int|string,mixed> $tax_query Existing tax query clauses.
 * @param string                  $format    Valid format slug.
 * @return array<int|string,mixed>
 */
function ik2_append_format_tax_query( array $tax_query, string $format ): array {
	$tax_query[] = array(
		'taxonomy' => 'category',
		'field'    => 'slug',
		'terms'    => array( $format ),
	);

	if ( count( $tax_query ) > 1 && ! isset( $tax_query['relation'] ) ) {
		$tax_query['relation'] = 'AND';
	}

	return $tax_query;
}

/**
 * Build the clean archive URL for the current context and page number.
 *
 * @param int $page Target page number.
 * @return string
 */
function ik2_build_archive_pagination_url( int $page ): string {
	$context = ik2_get_archive_context();
	$parts   = array();

	if ( '' !== $context['category'] ) {
		$parts[] = 'category';
		$parts[] = rawurlencode( $context['category'] );
	} elseif ( '' !== $context['tag'] ) {
		$parts[] = 'tag';
		$parts[] = rawurlencode( $context['tag'] );
	} else {
		$parts[] = ARTICLES_PAGE_SLUG;
	}

	if ( '' !== $context['format'] && ik2_is_valid_format( $context['format'] ) ) {
		$parts[] = 'format';
		$parts[] = rawurlencode( $context['format'] );
	}

	if ( $page > 1 ) {
		$parts[] = 'page';
		$parts[] = (string) $page;
	}

	return home_url( '/' . implode( '/', $parts ) . '/' );
}

/**
 * Read the requested custom Query Loop page out of a generated href.
 *
 * @param string $href     Pagination href.
 * @param string $page_key Query-string key to extract.
 * @return ?int
 */
function ik2_extract_query_loop_page_from_href( string $href, string $page_key ): ?int {
	$query = wp_parse_url( $href, PHP_URL_QUERY );

	if ( ! is_string( $query ) || '' === $query ) {
		return null;
	}

	$args = array();
	wp_parse_str( $query, $args );

	if ( ! isset( $args[ $page_key ] ) ) {
		return array_key_exists( 'cst', $args ) ? 1 : null;
	}

	$page = (int) $args[ $page_key ];

	return $page > 0 ? $page : null;
}
