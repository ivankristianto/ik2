<?php
/**
 * Resolved configuration for `wp ik2 migrate-articles`.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Migrate;

use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Holds every input the migration needs: the legacy connection, the local
 * uploads path, the old site's upload base URL, the target author, and the
 * run flags. Built from CLI flags with sensible defaults from the current
 * site's DB credentials. Invalid input aborts the run via WP_CLI::error.
 */
class Migration_Config {

	/**
	 * Name of the legacy database.
	 *
	 * @var string
	 */
	public string $legacy_db;

	/**
	 * Legacy database host.
	 *
	 * @var string
	 */
	public string $legacy_host;

	/**
	 * Legacy database user.
	 *
	 * @var string
	 */
	public string $legacy_user;

	/**
	 * Legacy database password.
	 *
	 * @var string
	 */
	public string $legacy_pass;

	/**
	 * Legacy database table prefix.
	 *
	 * @var string
	 */
	public string $legacy_prefix;

	/**
	 * Local path to the old wp-content/uploads copy.
	 *
	 * @var string
	 */
	public string $uploads_path;

	/**
	 * Old site's uploads base URL.
	 *
	 * @var string
	 */
	public string $old_base_url;

	/**
	 * Target author ID for imported posts.
	 *
	 * @var int
	 */
	public int $author_id;

	/**
	 * Maximum posts to import; 0 means all.
	 *
	 * @var int
	 */
	public int $limit;

	/**
	 * Import only the single legacy post with this old ID; 0 means all.
	 *
	 * @var int
	 */
	public int $only_post;

	/**
	 * When true, report what would happen but write nothing.
	 *
	 * @var bool
	 */
	public bool $dry_run;

	/**
	 * When true, log one line per post.
	 *
	 * @var bool
	 */
	public bool $verbose;

	/**
	 * When true, overwrite existing posts and re-sideload media.
	 *
	 * @var bool
	 */
	public bool $force;

	/**
	 * Build and validate config from associative CLI args.
	 *
	 * Defaults: legacy host/user/pass reuse the current site's DB_* constants
	 * (same MariaDB server); prefix "wp_"; uploads path /legacy/uploads;
	 * author the lowest-ID administrator.
	 *
	 * @param array<string, mixed> $assoc_args Associative CLI arguments.
	 */
	public static function from_args( array $assoc_args ): self {
		$config = new self();

		$config->legacy_db     = (string) WP_CLI\Utils\get_flag_value( $assoc_args, 'legacy-db', '' );
		$config->legacy_host   = (string) WP_CLI\Utils\get_flag_value( $assoc_args, 'legacy-host', DB_HOST );
		$config->legacy_user   = (string) WP_CLI\Utils\get_flag_value( $assoc_args, 'legacy-user', DB_USER );
		$config->legacy_pass   = (string) WP_CLI\Utils\get_flag_value( $assoc_args, 'legacy-pass', DB_PASSWORD );
		$config->legacy_prefix = (string) WP_CLI\Utils\get_flag_value( $assoc_args, 'legacy-prefix', 'wp_' );
		$config->uploads_path  = rtrim( (string) WP_CLI\Utils\get_flag_value( $assoc_args, 'uploads-path', '/legacy/uploads' ), '/' );
		$config->old_base_url  = rtrim( (string) WP_CLI\Utils\get_flag_value( $assoc_args, 'old-base-url', 'https://www.ivankristianto.com/wp-content/uploads' ), '/' );
		$config->author_id     = (int) WP_CLI\Utils\get_flag_value( $assoc_args, 'author', self::default_author_id() );
		$config->limit         = (int) WP_CLI\Utils\get_flag_value( $assoc_args, 'limit', 0 );
		$config->only_post     = (int) WP_CLI\Utils\get_flag_value( $assoc_args, 'post', 0 );
		$config->dry_run       = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$config->verbose       = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'verbose', false );
		$config->force         = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'force', false );

		$config->validate();

		return $config;
	}

	/**
	 * Abort the run if any required input is missing or unusable.
	 */
	private function validate(): void {
		if ( '' === $this->legacy_db ) {
			WP_CLI::error( 'Missing --legacy-db=<name>. Import the old dump into a separate database first.' );
		}

		if ( ! is_dir( $this->uploads_path ) ) {
			WP_CLI::error( sprintf( 'Uploads path not found: %s. Mount the old wp-content/uploads copy there.', $this->uploads_path ) );
		}

		if ( ! get_user_by( 'id', $this->author_id ) ) {
			WP_CLI::error( sprintf( 'Author id %d does not exist on this site.', $this->author_id ) );
		}
	}

	/**
	 * The lowest-ID administrator, used as the default import author.
	 */
	private static function default_author_id(): int {
		$admins = get_users(
			[
				'role'    => 'administrator',
				'orderby' => 'ID',
				'order'   => 'ASC',
				'number'  => 1,
				'fields'  => 'ID',
			]
		);

		return $admins ? (int) $admins[0] : 1;
	}
}
