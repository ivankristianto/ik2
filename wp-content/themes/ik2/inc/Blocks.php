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
 * Apply ?topic= and ?format= query params to the Articles Query Loop.
 *
 * Filters narrow to posts in the matching category slug(s). When both filters
 * are active, posts must match both categories (AND).
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

		if ( ARTICLES_QUERY_ID !== $query_id ) {
			return $query;
		}

		$topic  = isset( $_GET['topic'] ) ? sanitize_key( wp_unslash( $_GET['topic'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$format = isset( $_GET['format'] ) ? sanitize_key( wp_unslash( $_GET['format'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$tax_query = array();

		if ( '' !== $topic && 'all' !== $topic ) {
			$tax_query[] = array(
				'taxonomy' => 'category',
				'field'    => 'slug',
				'terms'    => array( $topic ),
			);
		}

		if ( '' !== $format && 'all' !== $format ) {
			$tax_query[] = array(
				'taxonomy' => 'category',
				'field'    => 'slug',
				'terms'    => array( $format ),
			);
		}

		if ( count( $tax_query ) > 1 ) {
			$tax_query['relation'] = 'AND';
		}

		if ( $tax_query ) {
			$query['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		return $query;
	},
	10,
	2
);

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
