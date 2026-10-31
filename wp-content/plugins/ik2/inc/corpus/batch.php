<?php
/**
 * Pure helpers for queueing in batches and reporting the Run schedule. No WordPress, unit-tested.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\Corpus\Batch;

/**
 * Where the next batch starts and whether this one was the last.
 *
 * @param int[] $ids   IDs in this batch, ascending.
 * @param int   $after The cursor this batch started from.
 * @param int   $size  Batch size asked for.
 * @return array{after:int, done:bool}
 */
function next_cursor( array $ids, int $after, int $size ): array {
	return [
		'after' => [] === $ids ? $after : (int) max( $ids ),
		'done'  => count( $ids ) < $size,
	];
}

/**
 * Count sync results, e.g. [ 'queued', 'unchanged', 'queued' ] gives queued 2, unchanged 1.
 *
 * @param string[] $results Results from sync_post().
 * @return array{queued:int, unchanged:int, removed:int, skipped:int, failed:int}
 */
function tally( array $results ): array {
	$counts = [
		'queued'    => 0,
		'unchanged' => 0,
		'removed'   => 0,
		'skipped'   => 0,
		'failed'    => 0,
	];

	foreach ( $results as $result ) {
		if ( isset( $counts[ $result ] ) ) {
			++$counts[ $result ];
		}
	}

	return $counts;
}

/**
 * When the next Run is expected, if the schedule fires every $interval minutes from the top of the hour.
 *
 * @param int $now      Unix time.
 * @param int $interval Minutes between Runs, a divisor of 60 or a multiple of it.
 */
function next_run_at( int $now, int $interval ): int {
	$step = max( 1, $interval ) * 60;

	return intdiv( $now, $step ) * $step + $step;
}
