<?php
/**
 * Sideloads old images from a local uploads copy into the media library.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Migrate;

use RuntimeException;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves an old upload URL to a file inside the local uploads copy and
 * imports it via media_handle_sideload, stamping each attachment with its
 * source URL so re-runs reuse instead of duplicating. --force re-imports.
 */
class Media_Sideloader {

	/**
	 * Local filesystem path to the uploads copy.
	 *
	 * @var string
	 */
	private string $uploads_path;

	/**
	 * Old site base URL (used to strip the domain from upload URLs).
	 *
	 * @var string
	 */
	private string $old_base_url;

	/**
	 * Filename helper (strip_size_suffix).
	 *
	 * @var Content_Rewriter
	 */
	private Content_Rewriter $rewriter;

	/**
	 * Constructor; loads the admin includes media_handle_sideload needs.
	 *
	 * @param Migration_Config $config   Resolved migration config.
	 * @param Content_Rewriter $rewriter Filename helper.
	 */
	public function __construct( Migration_Config $config, Content_Rewriter $rewriter ) {
		$this->uploads_path = $config->uploads_path;
		$this->old_base_url = $config->old_base_url;
		$this->rewriter     = $rewriter;

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}

	/**
	 * Find an already-imported attachment for this source filename.
	 *
	 * Looks up by the sanitized filename slug (the post_name of the attachment).
	 * Note: _ik2_legacy_src is stamped on each attachment at import time as an
	 * audit trail, but it is not used as a lookup key here.
	 *
	 * @param string $filename Original basename (size suffix stripped).
	 */
	public function find_existing( string $filename ): ?int {
		$slug = sanitize_title( pathinfo( $filename, PATHINFO_FILENAME ) );

		$by_slug = get_posts(
			[
				'post_type'        => 'attachment',
				'post_status'      => 'inherit',
				'name'             => $slug,
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => false,
			]
		);

		return $by_slug ? (int) $by_slug[0] : null;
	}

	/**
	 * Import one old URL into the media library (or reuse an existing one).
	 *
	 * @param string $old_url        Absolute old upload URL.
	 * @param int    $parent_post_id Attach to this post.
	 * @param bool   $force          Re-import even if already present.
	 * @return array{id:int,url:string,reused:bool}
	 * @throws RuntimeException If the file is missing locally or sideload fails.
	 */
	public function import( string $old_url, int $parent_post_id, bool $force ): array {
		$basename = basename( (string) wp_parse_url( $old_url, PHP_URL_PATH ) );
		$original = $this->rewriter->strip_size_suffix( $basename );

		if ( ! $force ) {
			$existing = $this->find_existing( $original );

			if ( null !== $existing ) {
				return [
					'id'     => $existing,
					'url'    => (string) wp_get_attachment_url( $existing ),
					'reused' => true,
				];
			}
		}

		$path = $this->resolve_local_path( $old_url );

		if ( null === $path ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new RuntimeException( sprintf( 'local file not found for %s', $old_url ) );
		}

		// Copy into a temp file media_handle_sideload can consume and delete.
		$tmp = wp_tempnam( $original );

		if ( ! $tmp || ! copy( $path, $tmp ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new RuntimeException( sprintf( 'could not stage temp file for %s', $old_url ) );
		}

		$file_array = [
			'name'     => $original,
			'tmp_name' => $tmp,
		];

		$attachment_id = media_handle_sideload( $file_array, $parent_post_id );

		if ( is_wp_error( $attachment_id ) ) {
			if ( file_exists( $tmp ) ) {
				wp_delete_file( $tmp );
			}
			$msg = sprintf( 'sideload failed for %s: %s', $old_url, $attachment_id->get_error_message() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new RuntimeException( $msg ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		update_post_meta( $attachment_id, '_ik2_legacy_src', $old_url );

		return [
			'id'     => (int) $attachment_id,
			'url'    => (string) wp_get_attachment_url( $attachment_id ),
			'reused' => false,
		];
	}

	/**
	 * Map an old upload URL to a path inside the local uploads copy.
	 *
	 * Tries the exact path first, then the size-stripped original (so a
	 * URL referencing a generated thumbnail resolves to the source file).
	 *
	 * @param string $old_url Absolute old upload URL.
	 */
	private function resolve_local_path( string $old_url ): ?string {
		$url_path  = (string) wp_parse_url( $old_url, PHP_URL_PATH );
		$marker    = '/wp-content/uploads/';
		$marker_at = strpos( $url_path, $marker );

		if ( false === $marker_at ) {
			return null;
		}

		$relative = substr( $url_path, $marker_at + strlen( $marker ) );
		$exact    = $this->uploads_path . '/' . $relative;

		if ( is_file( $exact ) ) {
			return $exact;
		}

		$dir      = dirname( $relative );
		$stripped = $this->rewriter->strip_size_suffix( basename( $relative ) );
		$fallback = $this->uploads_path . '/' . ( '.' === $dir ? '' : $dir . '/' ) . $stripped;

		return is_file( $fallback ) ? $fallback : null;
	}
}
