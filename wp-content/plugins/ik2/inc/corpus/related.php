<?php
/**
 * Read the related-post lists the embedder worker writes.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\Corpus;

defined( 'ABSPATH' ) || exit;

// A safety net, not a filter: the 2026-10-30 eval (docs/evals) showed higher floors trade weak picks for category fallbacks, which grade worse.
const RELATED_MIN_SCORE = 0.65;

const CACHE_GROUP = 'ik2-corpus';

const RELATED_OPTION = 'ik2_corpus_related';

/**
 * Whether Keep reading uses the Corpus picks. Off until `wp ik2 corpus related on`.
 */
function related_enabled(): bool {
	return 'on' === get_option( RELATED_OPTION, 'off' );
}

/**
 * Related post IDs for an Article, best first, with score >= RELATED_MIN_SCORE.
 *
 * @param int $post_id Article ID.
 * @param int $limit   Maximum number of IDs.
 * @return list<int>
 */
function get_related( int $post_id, int $limit ): array {
	// Off returns nothing, so the theme falls back to the newest posts in the category.
	if ( $post_id <= 0 || $limit <= 0 || ! related_enabled() ) {
		return [];
	}

	$run_id = last_run_id();

	// The worker commits the Run row and its lists together, so no Run means no lists.
	if ( 0 === $run_id ) {
		return [];
	}

	$ids = [];

	// The floor applies after the cache, so a new RELATED_MIN_SCORE takes effect on deploy, not after the next Run.
	foreach ( related_rows( $post_id, $run_id ) as [ $related_id, $score ] ) {
		if ( $score >= RELATED_MIN_SCORE && $related_id !== $post_id ) {
			$ids[] = $related_id;
		}
	}

	return array_slice( $ids, 0, $limit );
}

/**
 * Every stored pick for a post as [ related_id, score ], cached under the Run ID.
 *
 * @param int $post_id Article ID.
 * @param int $run_id  Latest Run ID, part of the cache key.
 * @return list<array{int, float}>
 */
function related_rows( int $post_id, int $run_id ): array {
	global $wpdb;

	$key   = "related:{$post_id}:{$run_id}";
	$found = false;
	$rows  = wp_cache_get( $key, CACHE_GROUP, false, $found );

	if ( $found && is_array( $rows ) ) {
		return $rows;
	}

	$results = $wpdb->get_results(
		$wpdb->prepare(
			'SELECT related_id, score FROM %i WHERE post_id = %d ORDER BY position ASC',
			table( 'related' ),
			$post_id
		),
		ARRAY_A
	);

	$rows = [];

	foreach ( (array) $results as $row ) {
		$rows[] = [ (int) $row['related_id'], (float) $row['score'] ];
	}

	// Keys change with every Run, so old ones only need to outlive a cached page.
	wp_cache_set( $key, $rows, CACHE_GROUP, DAY_IN_SECONDS );

	return $rows;
}

/**
 * ID of the newest Run, or 0 when the worker has never written one.
 */
function last_run_id(): int {
	global $wpdb;

	// Read once per request: a new Run changes every cache key without a flush.
	static $run_id = null;

	if ( null === $run_id ) {
		$run_id = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT MAX(id) FROM %i', table( 'runs' ) ) );
	}

	return $run_id;
}
