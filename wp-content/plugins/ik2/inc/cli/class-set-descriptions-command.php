<?php
/**
 * `wp ik2 set-descriptions` — give old posts a reviewed meta description without touching their dates.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI;

use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Sets Yoast's meta description from a reviewed list. Saving each post in
 * wp-admin would stamp today's modified date on posts from 2009, so the meta
 * and Yoast's indexable row are written directly.
 */
class Set_Descriptions_Command {

	/**
	 * The reviewed descriptions, shipped with the plugin so prod can read it.
	 */
	private const FILE = __DIR__ . '/../../data/legacy-descriptions.tsv';

	/**
	 * Sets meta descriptions from a `slug<TAB>description` file.
	 *
	 * Posts that already have a description are left alone unless --overwrite.
	 *
	 * ## OPTIONS
	 *
	 * [--file=<file>]
	 * : Read this file instead of data/legacy-descriptions.tsv.
	 *
	 * [--overwrite]
	 * : Replace descriptions that are already set.
	 *
	 * [--dry-run]
	 * : Report what would change; write nothing.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp ik2 set-descriptions --dry-run
	 *     $ wp ik2 set-descriptions
	 *
	 * @param array<int, string>   $args       Positional arguments (unused).
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		global $wpdb;

		$dry_run   = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$overwrite = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'overwrite', false );
		$path      = (string) ( $assoc_args['file'] ?? self::FILE );

		if ( ! is_readable( $path ) ) {
			WP_CLI::error( "Can't read {$path}." );
		}

		$indexables = $wpdb->prefix . 'yoast_indexable';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- table probe.
		$has_yoast = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $indexables ) ) === $indexables;

		$rows    = [];
		$missing = [];

		foreach ( (array) file( $path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) as $line ) {
			$parts = explode( "\t", (string) $line, 2 );

			if ( str_starts_with( (string) $line, '#' ) || 2 !== count( $parts ) || '' === trim( $parts[1] ) ) {
				continue;
			}

			[ $slug, $description ] = array_map( 'trim', $parts );

			$post = get_page_by_path( sanitize_title( $slug ), OBJECT, 'post' );

			if ( ! $post instanceof \WP_Post || 'publish' !== $post->post_status ) {
				$missing[] = $slug;
				continue;
			}

			$current = (string) get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true );
			$status  = '' === $current ? 'set' : ( $overwrite ? 'replaced' : 'kept' );

			$rows[] = [
				'ID'        => $post->ID,
				'post_name' => $post->post_name,
				'status'    => $status,
				'chars'     => mb_strlen( $description ),
			];

			if ( $dry_run || 'kept' === $status ) {
				continue;
			}

			// update_post_meta unslashes; slash first so a backslash survives (Yoast still strips them on output).
			update_post_meta( $post->ID, '_yoast_wpseo_metadesc', wp_slash( $description ) );

			if ( $has_yoast ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- keep the head tags in step without a post save.
				$wpdb->update(
					$indexables,
					[ 'description' => $description ],
					[
						'object_id'   => $post->ID,
						'object_type' => 'post',
					]
				);
			}

			clean_post_cache( $post->ID );
		}

		WP_CLI\Utils\format_items( 'table', $rows, [ 'ID', 'post_name', 'status', 'chars' ] );

		if ( $missing ) {
			WP_CLI::warning( 'Not found or not published: ' . implode( ', ', $missing ) );
		}

		$changed = count( array_filter( $rows, [ self::class, 'is_change' ] ) );

		WP_CLI::success( sprintf( '%s %d post(s).', $dry_run ? 'Would change' : 'Changed', $changed ) );
	}

	/**
	 * Whether a report row sets or replaces a description.
	 *
	 * @param array<string, mixed> $row Report row.
	 */
	public static function is_change( array $row ): bool {
		return 'kept' !== $row['status'];
	}
}
