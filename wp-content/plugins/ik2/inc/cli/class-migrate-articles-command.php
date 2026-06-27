<?php
/**
 * `wp ik2 migrate-articles` — import published articles + images from the
 * old ivankristianto.com site into the current site.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI;

use IK2\Plugin\CLI\Migrate\Migration_Config;
use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Reads a separate legacy database (read-only) and a local copy of the old
 * uploads folder, then imports each published post: body, slug, dates,
 * featured + inline images, categories, tags, post format, and Yoast meta.
 *
 * Idempotent by slug: a post whose slug already exists is skipped, and media
 * whose filename already exists are reused. --force re-forces the copy.
 */
class Migrate_Articles_Command {

	/**
	 * Imports published articles and their images from the old site.
	 *
	 * ## OPTIONS
	 *
	 * [--legacy-db=<name>]
	 * : Name of the database the old dump was imported into. Required.
	 *
	 * [--legacy-prefix=<prefix>]
	 * : Old site's table prefix. Default: wp_.
	 *
	 * [--legacy-host=<host>]
	 * : Legacy DB host. Default: the current site's DB_HOST.
	 *
	 * [--legacy-user=<user>]
	 * : Legacy DB user. Default: the current site's DB_USER.
	 *
	 * [--legacy-pass=<pass>]
	 * : Legacy DB password. Default: the current site's DB_PASSWORD.
	 *
	 * [--uploads-path=<path>]
	 * : Local directory holding the old wp-content/uploads copy.
	 * Default: /legacy/uploads.
	 *
	 * [--old-base-url=<url>]
	 * : The old site's uploads base URL, used to find image URLs in content.
	 * Default: https://www.ivankristianto.com/wp-content/uploads.
	 *
	 * [--author=<id>]
	 * : Target author id for imported posts. Default: lowest-ID administrator.
	 *
	 * [--limit=<n>]
	 * : Import at most N posts (for incremental testing). Default: 0 (all).
	 *
	 * [--post=<old_id>]
	 * : Import only the single legacy post with this old ID.
	 *
	 * [--dry-run]
	 * : Report what would happen; write nothing.
	 *
	 * [--verbose]
	 * : Log one line per post (status, media added, errors).
	 *
	 * [--force]
	 * : Re-force the copy: overwrite existing posts and re-sideload media
	 * even when their slugs already exist.
	 *
	 * ## EXAMPLES
	 *
	 *     # Preview the full migration without writing.
	 *     $ wp ik2 migrate-articles --legacy-db=legacy --dry-run
	 *
	 *     # Import one post end-to-end with per-step logging.
	 *     $ wp ik2 migrate-articles --legacy-db=legacy --post=1234 --verbose
	 *
	 *     # Run the full migration; re-run safely until failures hit zero.
	 *     $ wp ik2 migrate-articles --legacy-db=legacy
	 *
	 *     # Re-import everything, overwriting prior imports.
	 *     $ wp ik2 migrate-articles --legacy-db=legacy --force
	 *
	 * @param array<int, string>   $args       Positional arguments (unused).
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		$config = Migration_Config::from_args( $assoc_args );

		WP_CLI::log( sprintf( 'Legacy DB: %s (prefix %s) on %s', $config->legacy_db, $config->legacy_prefix, $config->legacy_host ) );
		WP_CLI::log( sprintf( 'Uploads:   %s', $config->uploads_path ) );
		WP_CLI::log( sprintf( 'Author:    %d', $config->author_id ) );
		WP_CLI::log( sprintf( 'Flags:     dry-run=%s verbose=%s force=%s limit=%d post=%d', $config->dry_run ? 'yes' : 'no', $config->verbose ? 'yes' : 'no', $config->force ? 'yes' : 'no', $config->limit, $config->only_post ) );

		WP_CLI::success( 'Config resolved. Pipeline wired in later tasks.' );
	}
}
