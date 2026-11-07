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

const PINS_META = 'ik2_related_pins';

/**
 * Register the hand-picked follow-ups meta, editable over REST.
 */
function register_pins_meta(): void {
	register_post_meta(
		OBJECT_TYPE,
		PINS_META,
		[
			'type'              => 'array',
			'single'            => true,
			'default'           => [],
			'show_in_rest'      => [
				'schema' => [
					'type'  => 'array',
					'items' => [ 'type' => 'integer' ],
				],
			],
			'sanitize_callback' => __NAMESPACE__ . '\\sanitize_pins',
			'auth_callback'     => __NAMESPACE__ . '\\can_edit_pins',
		]
	);
}

/**
 * Keep positive, unique post IDs in the order given.
 *
 * @param mixed $value Raw meta value.
 * @return list<int>
 */
function sanitize_pins( $value ): array {
	$ids = array_filter( array_map( 'absint', (array) $value ) );

	return array_values( array_unique( $ids ) );
}

/**
 * Only someone who can edit the Article may change its pins.
 *
 * @param bool   $allowed  Default from WordPress, ignored.
 * @param string $meta_key Meta key.
 * @param int    $post_id  Article ID.
 */
function can_edit_pins( bool $allowed, string $meta_key, int $post_id ): bool {
	return current_user_can( 'edit_post', $post_id );
}

/**
 * Published posts pinned as an Article's follow-ups, in the order they were set.
 *
 * @param int $post_id Article ID.
 * @return list<int>
 */
function pinned_ids( int $post_id ): array {
	$ids = [];

	foreach ( sanitize_pins( get_post_meta( $post_id, PINS_META, true ) ) as $id ) {
		if ( $id !== $post_id && 'publish' === get_post_status( $id ) ) {
			$ids[] = $id;
		}
	}

	return $ids;
}

/**
 * Whether Keep reading uses the Corpus picks. Off until `wp ik2 corpus related on`.
 */
function related_enabled(): bool {
	return 'on' === get_option( RELATED_OPTION, 'off' );
}

/**
 * Related post IDs for an Article, best first: hand-picked pins, then Corpus picks with score >= RELATED_MIN_SCORE.
 *
 * @param int $post_id Article ID.
 * @param int $limit   Maximum number of IDs.
 * @return list<int>
 */
function get_related( int $post_id, int $limit ): array {
	if ( $post_id <= 0 || $limit <= 0 ) {
		return [];
	}

	$ids = pinned_ids( $post_id );

	// With the Corpus off or never run, only the pins come back and the theme tops up from the category.
	$run_id = related_enabled() ? last_run_id() : 0;

	if ( 0 !== $run_id ) {
		// The floor applies after the cache, so a new RELATED_MIN_SCORE takes effect on deploy, not after the next Run.
		foreach ( related_rows( $post_id, $run_id ) as [ $related_id, $score ] ) {
			if ( $score >= RELATED_MIN_SCORE && $related_id !== $post_id && ! in_array( $related_id, $ids, true ) ) {
				$ids[] = $related_id;
			}
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
