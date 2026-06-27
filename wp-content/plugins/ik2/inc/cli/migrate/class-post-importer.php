<?php
/**
 * Imports a single legacy post end-to-end into the current site.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Migrate;

use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Runs one legacy post through the full pipeline: match by slug, insert or
 * skip/overwrite, sideload featured + inline media, rewrite content URLs,
 * assign categories/tags + post format, copy Yoast meta, and stamp the
 * legacy id. Each call is isolated: any failure becomes a failed result.
 */
class Post_Importer {

	/**
	 * Yoast meta keys copied verbatim from legacy postmeta.
	 *
	 * @var array<int, string>
	 */
	private const YOAST_KEYS = [
		'_yoast_wpseo_title',
		'_yoast_wpseo_metadesc',
		'_yoast_wpseo_canonical',
		'_yoast_wpseo_focuskw',
		'_yoast_wpseo_opengraph-title',
		'_yoast_wpseo_opengraph-description',
		'_yoast_wpseo_twitter-title',
		'_yoast_wpseo_twitter-description',
		'_yoast_wpseo_meta-robots-noindex',
		'_yoast_wpseo_meta-robots-nofollow',
	];

	/**
	 * Resolved migration config.
	 *
	 * @var Migration_Config
	 */
	private Migration_Config $config;

	/**
	 * Legacy database reader.
	 *
	 * @var Legacy_DB
	 */
	private Legacy_DB $legacy;

	/**
	 * Media sideloader.
	 *
	 * @var Media_Sideloader
	 */
	private Media_Sideloader $media;

	/**
	 * Content URL rewriter.
	 *
	 * @var Content_Rewriter
	 */
	private Content_Rewriter $rewriter;

	/**
	 * Constructor.
	 *
	 * @param Migration_Config $config   Resolved config.
	 * @param Legacy_DB        $legacy   Legacy reader.
	 * @param Media_Sideloader $media    Media importer.
	 * @param Content_Rewriter $rewriter URL helper.
	 */
	public function __construct( Migration_Config $config, Legacy_DB $legacy, Media_Sideloader $media, Content_Rewriter $rewriter ) {
		$this->config   = $config;
		$this->legacy   = $legacy;
		$this->media    = $media;
		$this->rewriter = $rewriter;
	}

	/**
	 * Import one legacy post row.
	 *
	 * @param array<string, string> $legacy_post A row from Legacy_DB::published_posts().
	 */
	public function import_one( array $legacy_post ): Migration_Result {
		$slug      = (string) $legacy_post['post_name'];
		$legacy_id = (int) $legacy_post['ID'];

		try {
			$existing = $this->find_by_slug( $slug );

			if ( null !== $existing && ! $this->config->force ) {
				return new Migration_Result( 'skipped', $slug, 'slug already exists' );
			}

			if ( $this->config->dry_run ) {
				$status = null === $existing ? 'created' : 'overwritten';
				$inline = count( $this->rewriter->extract_upload_urls( (string) $legacy_post['post_content'], $this->config->old_base_url ) );
				return new Migration_Result( $status, $slug, sprintf( 'dry-run: %d inline image(s)', $inline ), 0 );
			}

			$media_added = 0;

			// 1. Insert or update the post (content rewritten in step 3).
			$post_id = $this->upsert_post( $legacy_post, $existing );

			// 2. Featured image.
			$thumb_url = $this->legacy->thumbnail_url( $legacy_id );

			if ( null !== $thumb_url ) {
				$thumb = $this->media->import( $thumb_url, $post_id, $this->config->force );
				set_post_thumbnail( $post_id, $thumb['id'] );
				$media_added += $thumb['reused'] ? 0 : 1;
			}

			// 3. Inline images + content rewrite.
			$content = (string) $legacy_post['post_content'];
			$urls    = $this->rewriter->extract_upload_urls( $content, $this->config->old_base_url );
			$map     = [];

			foreach ( $urls as $url ) {
				$imported     = $this->media->import( $url, $post_id, $this->config->force );
				$map[ $url ]  = $imported['url'];
				$media_added += $imported['reused'] ? 0 : 1;
			}

			$rewritten = $this->rewriter->rewrite( $content, $map );

			if ( $rewritten !== $content ) {
				wp_update_post(
					[
						'ID'           => $post_id,
						'post_content' => $rewritten,
					]
				);
			}

			// 4. Terms + post format.
			$this->assign_terms( $post_id, $legacy_id );
			$this->assign_format( $post_id, $legacy_id );

			// 5. Yoast meta.
			$this->copy_yoast_meta( $post_id, $legacy_id );

			// 6. Stamp legacy id for traceability.
			update_post_meta( $post_id, '_ik2_legacy_id', $legacy_id );

			$status = null === $existing ? 'created' : 'overwritten';

			return new Migration_Result( $status, $slug, 'ok', $media_added );
		} catch ( Throwable $e ) {
			return new Migration_Result( 'failed', $slug, $e->getMessage() );
		}
	}

	/**
	 * Find an existing published/draft post by exact slug.
	 *
	 * @param string $slug Post slug.
	 */
	private function find_by_slug( string $slug ): ?int {
		$found = get_posts(
			[
				'post_type'        => 'post',
				'post_status'      => [ 'publish', 'draft', 'pending', 'private', 'future' ],
				'name'             => $slug,
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => false,
			]
		);

		return $found ? (int) $found[0] : null;
	}

	/**
	 * Insert a new post or update the matched one. Content is set pre-rewrite.
	 *
	 * @param array<string, string> $legacy_post Legacy row.
	 * @param int|null              $existing    Existing post id or null.
	 * @throws \RuntimeException When wp_insert_post / wp_update_post returns a WP_Error.
	 */
	private function upsert_post( array $legacy_post, ?int $existing ): int {
		$data = [
			'post_title'        => (string) $legacy_post['post_title'],
			'post_name'         => (string) $legacy_post['post_name'],
			'post_content'      => (string) $legacy_post['post_content'],
			'post_excerpt'      => (string) $legacy_post['post_excerpt'],
			'post_status'       => 'publish',
			'post_type'         => 'post',
			'post_author'       => $this->config->author_id,
			'post_date'         => (string) $legacy_post['post_date'],
			'post_date_gmt'     => (string) $legacy_post['post_date_gmt'],
			'post_modified'     => (string) $legacy_post['post_modified'],
			'post_modified_gmt' => (string) $legacy_post['post_modified_gmt'],
		];

		if ( null !== $existing ) {
			$data['ID'] = $existing;
			$result     = wp_update_post( $data, true );
		} else {
			$result = wp_insert_post( $data, true );
		}

		if ( is_wp_error( $result ) ) {
			throw new \RuntimeException( 'post upsert failed: ' . $result->get_error_message() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		return (int) $result;
	}

	/**
	 * Recreate and assign categories and tags by slug.
	 *
	 * @param int $post_id   New post id.
	 * @param int $legacy_id Legacy post id.
	 */
	private function assign_terms( int $post_id, int $legacy_id ): void {
		$categories = [];
		$tags       = [];

		foreach ( $this->legacy->terms( $legacy_id ) as $term ) {
			$taxonomy = $term['taxonomy'];
			$existing = get_term_by( 'slug', $term['slug'], $taxonomy );

			if ( $existing instanceof \WP_Term ) {
				$term_id = $existing->term_id;
			} else {
				$created = wp_insert_term( $term['name'], $taxonomy, [ 'slug' => $term['slug'] ] );

				if ( is_wp_error( $created ) ) {
					continue;
				}

				$term_id = (int) $created['term_id'];
			}

			if ( 'category' === $taxonomy ) {
				$categories[] = $term_id;
			} else {
				$tags[] = $term_id;
			}
		}

		if ( [] !== $categories ) {
			wp_set_object_terms( $post_id, $categories, 'category', false );
		}

		if ( [] !== $tags ) {
			wp_set_object_terms( $post_id, $tags, 'post_tag', false );
		}
	}

	/**
	 * Apply the legacy post format, if any.
	 *
	 * @param int $post_id   New post id.
	 * @param int $legacy_id Legacy post id.
	 */
	private function assign_format( int $post_id, int $legacy_id ): void {
		$format = $this->legacy->post_format( $legacy_id );

		if ( null !== $format ) {
			set_post_format( $post_id, $format );
		}
	}

	/**
	 * Copy known Yoast meta keys from legacy postmeta to the new post.
	 *
	 * @param int $post_id   New post id.
	 * @param int $legacy_id Legacy post id.
	 */
	private function copy_yoast_meta( int $post_id, int $legacy_id ): void {
		$meta = $this->legacy->post_meta( $legacy_id );

		foreach ( self::YOAST_KEYS as $key ) {
			if ( isset( $meta[ $key ][0] ) && '' !== $meta[ $key ][0] ) {
				update_post_meta( $post_id, $key, $meta[ $key ][0] );
			}
		}
	}
}
