<?php
/**
 * Tests for the queue-all cursor, result tally and next-Run time.
 *
 * @package IK2\Tests
 */

declare(strict_types=1);

namespace IK2\Tests;

use PHPUnit\Framework\TestCase;

use function IK2\Plugin\Corpus\Batch\next_cursor;
use function IK2\Plugin\Corpus\Batch\next_run_at;
use function IK2\Plugin\Corpus\Batch\tally;

final class CorpusBatchTest extends TestCase {

	public function test_a_full_batch_moves_the_cursor_to_its_last_id_and_is_not_done(): void {
		self::assertSame( [ 'after' => 90, 'done' => false ], next_cursor( [ 12, 40, 90 ], 0, 3 ) );
	}

	public function test_a_short_batch_is_the_last(): void {
		self::assertSame( [ 'after' => 120, 'done' => true ], next_cursor( [ 101, 120 ], 90, 3 ) );
	}

	public function test_an_empty_batch_keeps_the_cursor_and_is_done(): void {
		self::assertSame( [ 'after' => 120, 'done' => true ], next_cursor( [], 120, 3 ) );
	}

	public function test_tally_counts_each_result_and_ignores_unknown_ones(): void {
		self::assertSame(
			[
				'queued'    => 2,
				'unchanged' => 1,
				'removed'   => 0,
				'skipped'   => 1,
				'failed'    => 0,
			],
			tally( [ 'queued', 'unchanged', 'queued', 'skipped', 'bogus' ] )
		);
	}

	public function test_next_hourly_run_is_the_next_top_of_the_hour(): void {
		$now = gmmktime( 10, 23, 5, 10, 31, 2026 );

		self::assertSame( gmmktime( 11, 0, 0, 10, 31, 2026 ), next_run_at( $now, 60 ) );
	}

	public function test_a_run_exactly_on_the_hour_points_to_the_next_one(): void {
		$now = gmmktime( 11, 0, 0, 10, 31, 2026 );

		self::assertSame( gmmktime( 12, 0, 0, 10, 31, 2026 ), next_run_at( $now, 60 ) );
	}

	public function test_a_15_minute_schedule_points_to_the_next_quarter(): void {
		$now = gmmktime( 10, 23, 5, 10, 31, 2026 );

		self::assertSame( gmmktime( 10, 30, 0, 10, 31, 2026 ), next_run_at( $now, 15 ) );
	}
}
