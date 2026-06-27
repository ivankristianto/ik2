<?php
/**
 * Read-only access to the old site's database via a second wpdb connection.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Migrate;

use WP_CLI;
use wpdb;

defined( 'ABSPATH' ) || exit;

/**
 * Wraps a second wpdb instance pointed at the imported legacy database. All
 * methods read; nothing here writes. Table names are built from the
 * config-supplied prefix (not user input), so prefix interpolation is safe.
 */
class Legacy_DB {

	/**
	 * The legacy connection.
	 *
	 * @var wpdb
	 */
	private wpdb $db;

	/**
	 * Old site's table prefix, e.g. "wp_".
	 *
	 * @var string
	 */
	private string $prefix;

	/**
	 * Old site's uploads base URL, used to absolutize attachment paths.
	 *
	 * @var string
	 */
	private string $old_base_url;

	/**
	 * Open the legacy connection or abort the run.
	 *
	 * @param Migration_Config $config Resolved migration config.
	 */
	public function __construct( Migration_Config $config ) {
		$this->prefix       = $config->legacy_prefix;
		$this->old_base_url = $config->old_base_url;

		$this->db = new wpdb( $config->legacy_user, $config->legacy_pass, $config->legacy_db, $config->legacy_host );

		if ( ! empty( $this->db->last_error ) ) {
			WP_CLI::error( sprintf( 'Cannot connect to legacy DB "%s": %s', $config->legacy_db, (string) $this->db->last_error ) );
		}

		// Probe the posts table so a bad prefix fails fast with a clear message.
		$table = $this->prefix . 'posts';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- prefix is config, not user input.
		$exists = $this->db->get_var( "SHOW TABLES LIKE '{$table}'" );

		if ( null === $exists ) {
			WP_CLI::error( sprintf( 'Legacy table %s not found. Check --legacy-prefix.', $table ) );
		}
	}

	/**
	 * Published posts (post_type=post), oldest first.
	 *
	 * @param int $limit     Max rows, or 0 for no limit.
	 * @param int $only_post A single legacy post ID, or 0 for all.
	 * @return array<int, array<string, string>>
	 */
	public function published_posts( int $limit, int $only_post ): array {
		$table = $this->prefix . 'posts';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- prefix is config, not user input.
		$sql = "SELECT * FROM {$table} WHERE post_type = 'post' AND post_status = 'publish'";

		if ( $only_post > 0 ) {
			$sql .= $this->db->prepare( ' AND ID = %d', $only_post );
		}

		$sql .= ' ORDER BY post_date DESC, ID DESC';

		if ( $limit > 0 ) {
			$sql .= $this->db->prepare( ' LIMIT %d', $limit );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- assembled with prepare() above.
		$rows = $this->db->get_results( $sql, ARRAY_A );

		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * All postmeta for a post as meta_key => list of values.
	 *
	 * @param int $post_id Legacy post ID.
	 * @return array<string, array<int, string>>
	 */
	public function post_meta( int $post_id ): array {
		$table = $this->prefix . 'postmeta';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- prefix is config, not user input.
		$rows = $this->db->get_results(
			$this->db->prepare( "SELECT meta_key, meta_value FROM {$table} WHERE post_id = %d", $post_id ),
			ARRAY_A
		);

		$meta = [];

		foreach ( (array) $rows as $row ) {
			$meta[ $row['meta_key'] ][] = (string) $row['meta_value'];
		}

		return $meta;
	}

	/**
	 * Category and tag terms attached to a post.
	 *
	 * @param int $post_id Legacy post ID.
	 * @return array<int, array<string, string>>
	 */
	public function terms( int $post_id ): array {
		$tr = $this->prefix . 'term_relationships';
		$tt = $this->prefix . 'term_taxonomy';
		$t  = $this->prefix . 'terms';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- prefixes are config, not user input.
		$sql = $this->db->prepare(
			"SELECT tt.taxonomy AS taxonomy, t.name AS name, t.slug AS slug
			 FROM {$tr} tr
			 INNER JOIN {$tt} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
			 INNER JOIN {$t} t ON t.term_id = tt.term_id
			 WHERE tr.object_id = %d AND tt.taxonomy IN ( 'category', 'post_tag' )",
			$post_id
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- prepared above.
		$rows = $this->db->get_results( $sql, ARRAY_A );

		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * The post format slug (e.g. "aside"), or null for standard.
	 *
	 * @param int $post_id Legacy post ID.
	 */
	public function post_format( int $post_id ): ?string {
		$tr = $this->prefix . 'term_relationships';
		$tt = $this->prefix . 'term_taxonomy';
		$t  = $this->prefix . 'terms';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- prefixes are config, not user input.
		$sql = $this->db->prepare(
			"SELECT t.slug AS slug
			 FROM {$tr} tr
			 INNER JOIN {$tt} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
			 INNER JOIN {$t} t ON t.term_id = tt.term_id
			 WHERE tr.object_id = %d AND tt.taxonomy = 'post_format'
			 LIMIT 1",
			$post_id
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- prepared above.
		$slug = $this->db->get_var( $sql );

		if ( null === $slug ) {
			return null;
		}

		// Stored as "post-format-aside"; WordPress wants "aside".
		return (string) preg_replace( '/^post-format-/', '', (string) $slug );
	}

	/**
	 * Absolute old URL of the featured image, or null if none.
	 *
	 * @param int $post_id Legacy post ID.
	 */
	public function thumbnail_url( int $post_id ): ?string {
		$meta          = $this->post_meta( $post_id );
		$thumbnail_ids = $meta['_thumbnail_id'] ?? [];

		if ( [] === $thumbnail_ids ) {
			return null;
		}

		$attachment_id = (int) $thumbnail_ids[0];
		$attached      = $this->post_meta( $attachment_id );
		$relative      = $attached['_wp_attached_file'][0] ?? '';

		if ( '' === $relative ) {
			return null;
		}

		return $this->old_base_url . '/' . ltrim( $relative, '/' );
	}
}
