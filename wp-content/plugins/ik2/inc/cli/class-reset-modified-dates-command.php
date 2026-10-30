<?php
/**
 * `wp ik2 reset-modified-dates` — undo the bulk "modified" stamp the content sync left on old posts.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI;

use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * The 2026-06-27 REST sync set post_modified to that day on almost every
 * post, so sitemaps, JSON-LD and Open Graph claimed 480+ old posts were
 * updated that day. The legacy database is gone, so the honest value left
 * is the publish date: none of those posts had a content update that day.
 */
class Reset_Modified_Dates_Command {

	/**
	 * Sets post_modified back to post_date on posts last modified on the sync day.
	 *
	 * Only touches published posts published before that day, and also
	 * updates Yoast's indexables so sitemap lastmod follows.
	 *
	 * ## OPTIONS
	 *
	 * [--day=<Y-m-d>]
	 * : The bulk-stamp day, in GMT.
	 * ---
	 * default: 2026-06-27
	 * ---
	 *
	 * [--dry-run]
	 * : List the posts that would change; write nothing.
	 *
	 * ## EXAMPLES
	 *
	 *     # Preview.
	 *     $ wp ik2 reset-modified-dates --dry-run
	 *
	 *     # Apply.
	 *     $ wp ik2 reset-modified-dates
	 *
	 * @param array<int, string>   $args       Positional arguments (unused).
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		global $wpdb;

		$day     = (string) ( $assoc_args['day'] ?? '2026-06-27' );
		$dry_run = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $day ) ) {
			WP_CLI::error( '--day must be Y-m-d.' );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one-off maintenance read.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_name, post_date, post_date_gmt, post_modified_gmt FROM {$wpdb->posts}
				WHERE post_type = 'post' AND post_status = 'publish'
				AND DATE(post_modified_gmt) = %s AND post_date_gmt < %s",
				$day,
				$day . ' 00:00:00'
			)
		);

		WP_CLI::log( sprintf( '%d post(s) last modified on %s (GMT).', count( $rows ), $day ) );

		if ( $dry_run ) {
			WP_CLI\Utils\format_items( 'table', $rows, [ 'ID', 'post_name', 'post_date_gmt', 'post_modified_gmt' ] );
			return;
		}

		$indexables = $wpdb->prefix . 'yoast_indexable';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- table probe.
		$has_yoast = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $indexables ) ) === $indexables;

		foreach ( $rows as $row ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- direct write: wp_update_post would stamp "now" again.
			$wpdb->update(
				$wpdb->posts,
				[
					'post_modified'     => $row->post_date,
					'post_modified_gmt' => $row->post_date_gmt,
				],
				[ 'ID' => (int) $row->ID ]
			);

			if ( $has_yoast ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- keep Yoast's sitemap lastmod in step.
				$wpdb->update(
					$indexables,
					[ 'object_last_modified' => $row->post_date_gmt ],
					[
						'object_id'   => (int) $row->ID,
						'object_type' => 'post',
					]
				);
			}

			clean_post_cache( (int) $row->ID );
		}

		if ( class_exists( '\\WPSEO_Sitemaps_Cache' ) ) {
			\WPSEO_Sitemaps_Cache::clear();
		}

		WP_CLI::success( sprintf( 'Reset %d post(s). Purge the page cache so HTML picks up the new dates.', count( $rows ) ) );
	}
}
