<?php
/**
 * `wp ik2 noindex-posts` — take retired posts out of search without touching their dates.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI;

use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Sets Yoast's per-post noindex for the given slugs. Saving each post in
 * wp-admin would stamp today's modified date on every one, so the meta and
 * Yoast's indexable row are written directly. The posts stay published and
 * linkable; they only leave the index and the sitemap.
 */
class Noindex_Posts_Command {

	/**
	 * Marks published posts noindex by slug.
	 *
	 * ## OPTIONS
	 *
	 * <slug>...
	 * : One or more post slugs.
	 *
	 * [--undo]
	 * : Remove the noindex instead.
	 *
	 * [--dry-run]
	 * : Report what would change; write nothing.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp ik2 noindex-posts holiday-is-coming wordpress-4-1-3 --dry-run
	 *
	 * @param array<int, string>   $args       Post slugs.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		global $wpdb;

		$dry_run = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$undo    = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'undo', false );

		$indexables = $wpdb->prefix . 'yoast_indexable';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- table probe.
		$has_yoast = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $indexables ) ) === $indexables;

		$rows    = [];
		$missing = [];

		foreach ( array_unique( $args ) as $slug ) {
			$post = get_page_by_path( sanitize_title( $slug ), OBJECT, 'post' );

			if ( ! $post instanceof \WP_Post || 'publish' !== $post->post_status ) {
				$missing[] = $slug;
				continue;
			}

			$rows[] = [
				'ID'        => $post->ID,
				'post_name' => $post->post_name,
				'noindex'   => $undo ? 'removed' : 'set',
			];

			if ( $dry_run ) {
				continue;
			}

			if ( $undo ) {
				delete_post_meta( $post->ID, '_yoast_wpseo_meta-robots-noindex' );
			} else {
				update_post_meta( $post->ID, '_yoast_wpseo_meta-robots-noindex', '1' );
			}

			if ( $has_yoast ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- keep the head tags and sitemap in step without a post save.
				$wpdb->update(
					$indexables,
					[ 'is_robots_noindex' => $undo ? null : 1 ],
					[
						'object_id'   => $post->ID,
						'object_type' => 'post',
					]
				);
			}

			clean_post_cache( $post->ID );
		}

		if ( ! $dry_run && class_exists( '\\WPSEO_Sitemaps_Cache' ) ) {
			\WPSEO_Sitemaps_Cache::clear();
		}

		WP_CLI\Utils\format_items( 'table', $rows, [ 'ID', 'post_name', 'noindex' ] );

		if ( $missing ) {
			WP_CLI::warning( 'Not found or not published: ' . implode( ', ', $missing ) );
		}

		WP_CLI::success( sprintf( '%s %d post(s).', $dry_run ? 'Would change' : 'Changed', count( $rows ) ) );
	}
}
