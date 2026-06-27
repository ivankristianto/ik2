<?php
/**
 * `wp ik2 stats` — site health snapshot.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI;

use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Reports content counts, object cache status, active plugins, upcoming cron
 * events, and total database size.
 */
class Stats_Command {

	/**
	 * Shows a snapshot of site statistics.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Show site stats as a table.
	 *     $ wp ik2 stats
	 *
	 *     # Show site stats as JSON.
	 *     $ wp ik2 stats --format=json
	 *
	 * @param array<int, string>    $args       Positional arguments (unused).
	 * @param array<string, string> $assoc_args Associative arguments.
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		$items = [
			[
				'metric' => 'Posts (published)',
				'value'  => (int) wp_count_posts( 'post' )->publish,
			],
			[
				'metric' => 'Pages (published)',
				'value'  => (int) wp_count_posts( 'page' )->publish,
			],
			[
				'metric' => 'Tags',
				'value'  => $this->count_terms( 'post_tag' ),
			],
			[
				'metric' => 'Categories',
				'value'  => $this->count_terms( 'category' ),
			],
			[
				'metric' => 'Redis object cache',
				'value'  => $this->redis_cache_status(),
			],
			[
				'metric' => 'Active plugins',
				'value'  => count( (array) get_option( 'active_plugins', [] ) ),
			],
			[
				'metric' => 'Upcoming cron events',
				'value'  => $this->count_upcoming_cron_events(),
			],
			[
				'metric' => 'Database size',
				'value'  => $this->database_size(),
			],
		];

		$format = WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );

		WP_CLI\Utils\format_items( $format, $items, [ 'metric', 'value' ] );
	}

	/**
	 * Count all terms in a taxonomy, including unused ones.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 */
	private function count_terms( string $taxonomy ): int {
		$count = wp_count_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			]
		);

		return is_wp_error( $count ) ? 0 : (int) $count;
	}

	/**
	 * Describe whether a Redis-backed persistent object cache is active.
	 */
	private function redis_cache_status(): string {
		if ( ! wp_using_ext_object_cache() ) {
			return 'No (no persistent object cache)';
		}

		$cache = $GLOBALS['wp_object_cache'] ?? null;

		// The Redis Object Cache drop-in exposes redis_status().
		if ( is_object( $cache ) && method_exists( $cache, 'redis_status' ) ) {
			return $cache->redis_status() ? 'Yes (connected)' : 'No (Redis drop-in present, not connected)';
		}

		return 'No (non-Redis object cache in use)';
	}

	/**
	 * Count every scheduled cron event across all hooks.
	 */
	private function count_upcoming_cron_events(): int {
		$count = 0;

		foreach ( _get_cron_array() as $hooks ) {
			foreach ( (array) $hooks as $events ) {
				$count += count( (array) $events );
			}
		}

		return $count;
	}

	/**
	 * Total database size, human-formatted, via `wp db size`.
	 */
	private function database_size(): string {
		$bytes = WP_CLI::runcommand(
			'db size --size_format=b',
			[
				'return'     => true,
				'exit_error' => false,
			]
		);

		$bytes = (int) trim( (string) $bytes );

		return $bytes > 0 ? (string) size_format( $bytes, 2 ) : 'Unknown';
	}
}
