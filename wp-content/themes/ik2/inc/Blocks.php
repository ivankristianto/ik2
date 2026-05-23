<?php
/**
 * Theme block registration and Query Loop filter behaviour.
 *
 * @package IK2
 */

declare(strict_types=1);

namespace IK2\Theme;

defined( 'ABSPATH' ) || exit;

const ARTICLES_QUERY_ID = 42;
const ARCHIVE_QUERY_ID  = 43;

add_action(
	'init',
	static function (): void {
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
);

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
 * @param \WP_Block           $block Block instance.
 * @return array<string,mixed>
 */
add_filter(
	'query_loop_block_query_vars',
	static function ( array $query, $block ): array {
		$context  = is_object( $block ) && isset( $block->context ) ? $block->context : array();
		$query_id = isset( $context['queryId'] ) ? (int) $context['queryId'] : 0;

		if ( ARTICLES_QUERY_ID !== $query_id && ARCHIVE_QUERY_ID !== $query_id ) {
			return $query;
		}

		$allowed_formats = array( 'guide', 'note', 'experiment' );
		$format          = (string) get_query_var( 'format', '' );

		if ( '' === $format || ! in_array( $format, $allowed_formats, true ) ) {
			return $query;
		}

		$existing_tax_query = isset( $query['tax_query'] ) && is_array( $query['tax_query'] )
			? $query['tax_query']
			: array();

		$existing_tax_query[] = array(
			'taxonomy' => 'category',
			'field'    => 'slug',
			'terms'    => array( $format ),
		);

		if ( count( $existing_tax_query ) > 1 && ! isset( $existing_tax_query['relation'] ) ) {
			$existing_tax_query['relation'] = 'AND';
		}

		$query['tax_query'] = $existing_tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query

		return $query;
	},
	10,
	2
);

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
add_action(
	'pre_get_posts',
	static function ( $wp_query ): void {
		if ( is_admin() || ! $wp_query->is_main_query() ) {
			return;
		}

		if ( ! $wp_query->is_category() && ! $wp_query->is_tag() ) {
			return;
		}

		$allowed_formats = array( 'guide', 'note', 'experiment' );
		$format          = (string) $wp_query->get( 'format', '' );

		if ( '' === $format || ! in_array( $format, $allowed_formats, true ) ) {
			return;
		}

		$existing_tax_query = $wp_query->get( 'tax_query' );
		if ( ! is_array( $existing_tax_query ) ) {
			$existing_tax_query = array();
		}

		$existing_tax_query[] = array(
			'taxonomy' => 'category',
			'field'    => 'slug',
			'terms'    => array( $format ),
		);

		if ( count( $existing_tax_query ) > 1 && ! isset( $existing_tax_query['relation'] ) ) {
			$existing_tax_query['relation'] = 'AND';
		}

		$wp_query->set( 'tax_query', $existing_tax_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
	}
);

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
add_action(
	'parse_request',
	static function ( $wp ): void {
		$GLOBALS['ik2_archive_context'] = array(
			'category' => isset( $wp->query_vars['category_name'] ) ? (string) $wp->query_vars['category_name'] : '',
			'tag'      => isset( $wp->query_vars['tag'] ) ? (string) $wp->query_vars['tag'] : '',
			'format'   => isset( $wp->query_vars['format'] ) ? (string) $wp->query_vars['format'] : '',
		);
	}
);

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
add_filter(
	'query_vars',
	static function ( array $vars ): array {
		$vars[] = 'format';
		return $vars;
	}
);

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
add_action(
	'init',
	static function (): void {
		add_rewrite_rule(
			'^articles/format/([^/]+)/?$',
			'index.php?pagename=articles&format=$matches[1]',
			'top'
		);
		add_rewrite_rule(
			'^category/([^/]+)/format/([^/]+)/?$',
			'index.php?category_name=$matches[1]&format=$matches[2]',
			'top'
		);
		add_rewrite_rule(
			'^tag/([^/]+)/format/([^/]+)/?$',
			'index.php?tag=$matches[1]&format=$matches[2]',
			'top'
		);
	}
);

/**
 * Flush rewrite rules once when the theme is activated so the new
 * rules above register with the rewrite cache.
 */
add_action(
	'after_switch_theme',
	static function (): void {
		flush_rewrite_rules();
	}
);
