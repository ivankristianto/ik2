<?php
/**
 * `wp ik2 fix-image-ids` — point inline images back at their real attachments.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI;

use IK2\Plugin\CLI\Migrate\Content_Rewriter;
use WP_CLI;
use WP_HTML_Tag_Processor;

defined( 'ABSPATH' ) || exit;

/**
 * Rewrites the old site's attachment IDs the migration left on inline images,
 * which stop core adding width, height, srcset, and lazy loading.
 */
class Fix_Image_Ids_Command {

	/**
	 * A handled block's opener through to the next block delimiter, which
	 * covers its images: v1 galleries hold theirs, covers put theirs first.
	 */
	private const BLOCK_SEGMENT = '/(<!-- wp:(?:image|gallery|cover|media-text) \{.*?\} (?:\/-->|-->.*?(?=<!-- \/?wp:|$)))/s';

	/**
	 * The ID attributes of those blocks, as found in the opener's JSON.
	 */
	private const BLOCK_ATTRS = '/("(?:ids?|mediaId)":)(\[[\d,]*\]|\d+)/';

	/**
	 * The attachment ID on a caption shortcode.
	 */
	private const CAPTION_ATTR = '/(\[caption\b[^\]]*\bid=["\']attachment_)(\d+)/';

	/**
	 * Resolved attachment ID per src, shared across posts.
	 *
	 * @var array<string, int>
	 */
	private array $resolved = [];

	/**
	 * Old ID => new ID for the block or caption being repaired.
	 *
	 * @var array<int, int>
	 */
	private array $map = [];

	/**
	 * Whether $map needs two answers for one old ID.
	 *
	 * @var bool
	 */
	private bool $conflict = false;

	/**
	 * Report rows for the post being repaired.
	 *
	 * @var array<int, array{old: int, new: int, src: string, conflict: bool}>
	 */
	private array $images = [];

	/**
	 * Rewrites stale attachment IDs on inline images to match their src.
	 *
	 * Updates the `wp-image-N` class, gallery `data-id`, the `id`, `ids`, or
	 * `mediaId` attribute of image, gallery, cover, and media-text blocks, and
	 * `[caption]` IDs. Images whose src matches no attachment are reported
	 * and left alone. A block where one old ID covers images with different
	 * answers is reported as a conflict and left alone.
	 *
	 * Writes post_content directly, so post_modified and revisions are
	 * untouched and no kses filter runs over the content.
	 *
	 * ## OPTIONS
	 *
	 * [--post=<ids>]
	 * : Only these post IDs, comma-separated.
	 *
	 * [--post-type=<types>]
	 * : Post types to scan, comma-separated.
	 * ---
	 * default: post,page
	 * ---
	 *
	 * [--dry-run]
	 * : Report what would change; write nothing.
	 *
	 * [--format=<format>]
	 * : Format for the per-image report. Only `table` adds a summary line.
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
	 *     # Preview every change.
	 *     $ wp ik2 fix-image-ids --dry-run
	 *
	 *     # Fix one post.
	 *     $ wp ik2 fix-image-ids --post=503
	 *
	 *     # Fix everything.
	 *     $ wp ik2 fix-image-ids
	 *
	 * @param array<int, string>   $args       Positional arguments (unused).
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		$dry_run = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$format  = (string) WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );
		$types   = wp_parse_list( (string) WP_CLI\Utils\get_flag_value( $assoc_args, 'post-type', 'post,page' ) );
		$only    = wp_parse_id_list( (string) WP_CLI\Utils\get_flag_value( $assoc_args, 'post', '' ) );

		// An empty --post= would otherwise widen the run to every post.
		if ( isset( $assoc_args['post'] ) && [] === $only ) {
			WP_CLI::error( '--post needs one or more post IDs.' );
		}

		if ( [] === $types ) {
			WP_CLI::error( '--post-type needs one or more post types.' );
		}

		$post_ids = $this->candidate_posts( $types, $only );
		$rows     = [];
		$tally    = [
			'changed' => 0,
			'failed'  => 0,
		];

		foreach ( $post_ids as $post_id ) {
			$content = (string) get_post_field( 'post_content', $post_id, 'raw' );
			$repair  = $this->repair( $content );
			$status  = $dry_run ? 'would fix' : 'fixed';

			if ( $repair['content'] !== $content ) {
				if ( $dry_run || $this->save_content( $post_id, $repair['content'] ) ) {
					++$tally['changed'];
				} else {
					$status = 'failed';
					++$tally['failed'];
				}
			}

			foreach ( $repair['images'] as $image ) {
				$rows[] = [
					'post'   => $post_id,
					'old'    => $image['old'],
					'new'    => $image['new'],
					'src'    => $image['src'],
					'status' => $this->row_status( $image, $status ),
				];
			}
		}

		// Machine formats print only the report, so the output stays parseable.
		if ( 'table' !== $format ) {
			WP_CLI\Utils\format_items( $format, $rows, [ 'post', 'old', 'new', 'status', 'src' ] );

			if ( $tally['failed'] > 0 ) {
				WP_CLI::halt( 1 );
			}

			return;
		}

		if ( [] !== $rows ) {
			WP_CLI\Utils\format_items( $format, $rows, [ 'post', 'old', 'new', 'status', 'src' ] );
		}

		$statuses = array_count_values( array_column( $rows, 'status' ) );

		WP_CLI::log(
			sprintf(
				'Scanned %d post(s): %d stale image(s), %d unresolved, %d conflict(s); %d post(s) %s.',
				count( $post_ids ),
				count( $rows ),
				$statuses['unresolved'] ?? 0,
				$statuses['conflict'] ?? 0,
				$tally['changed'],
				$dry_run ? 'would change' : 'updated'
			)
		);

		if ( isset( $statuses['unresolved'] ) ) {
			WP_CLI::warning( 'Unresolved images point at files with no attachment. Import them, then re-run.' );
		}

		if ( isset( $statuses['conflict'] ) ) {
			WP_CLI::warning( 'Conflicts are blocks where one old ID covers images with different answers. Fix those by hand in the editor.' );
		}

		if ( $tally['failed'] > 0 ) {
			WP_CLI::error( sprintf( '%d post(s) failed to save.', $tally['failed'] ) );
		}

		if ( ! $dry_run && $tally['changed'] > 0 ) {
			WP_CLI::log( 'Delete the WP Super Cache page cache in wp-admin (or restart the app container), then purge the CDN.' );
		}

		WP_CLI::success( $dry_run ? 'Dry run complete.' : 'Done.' );
	}

	/**
	 * Rewrite stale IDs in one post's content.
	 *
	 * @param string $content Raw post_content.
	 * @return array{content: string, images: array<int, array{old: int, new: int, src: string, conflict: bool}>}
	 */
	public function repair( string $content ): array {
		$this->images = [];

		// Each block keeps its attributes in step with only the images inside it.
		$parts = (array) preg_split( self::BLOCK_SEGMENT, $content, -1, PREG_SPLIT_DELIM_CAPTURE );

		foreach ( $parts as $i => $part ) {
			$parts[ $i ] = 1 === $i % 2 ? $this->repair_segment( (string) $part, self::BLOCK_ATTRS, '-->' ) : $this->repair_classic( (string) $part );
		}

		return [
			'content' => implode( '', $parts ),
			'images'  => $this->images,
		];
	}

	/**
	 * Repair content outside the handled blocks: captions, then bare images.
	 *
	 * @param string $html Content between handled blocks.
	 */
	private function repair_classic( string $html ): string {
		$parts = (array) preg_split( '/(\[caption\b.*?\[\/caption\])/s', $html, -1, PREG_SPLIT_DELIM_CAPTURE );

		foreach ( $parts as $i => $part ) {
			$parts[ $i ] = 1 === $i % 2 ? $this->repair_segment( (string) $part, self::CAPTION_ATTR, ']' ) : $this->repair_images( (string) $part );
		}

		return implode( '', $parts );
	}

	/**
	 * Repair one block or caption, remapping its own ID attribute to match.
	 *
	 * @param string $segment Block opener through its images, or a whole caption.
	 * @param string $attrs   Pattern matching the ID attribute(s) to remap.
	 * @param string $close   Token that ends the opener, the only part $attrs may touch.
	 */
	private function repair_segment( string $segment, string $attrs, string $close ): string {
		$first = count( $this->images );
		$html  = $this->repair_images( $segment );

		// The attribute can't carry two answers for one old ID; leave the segment as it was.
		if ( $this->conflict ) {
			for ( $i = $first, $last = count( $this->images ); $i < $last; $i++ ) {
				$this->images[ $i ]['conflict'] = true;
			}

			return $segment;
		}

		if ( [] === $this->map ) {
			return $html;
		}

		$end = (int) strpos( $html, $close ) + strlen( $close );

		return preg_replace_callback( $attrs, [ $this, 'remap_id_list' ], substr( $html, 0, $end ) ) . substr( $html, $end );
	}

	/**
	 * Rewrite each image's `wp-image-N` class and gallery `data-id` to match its src.
	 *
	 * Leaves the old-to-new map and whether it conflicts in $map and $conflict.
	 *
	 * @param string $html Content to scan.
	 */
	private function repair_images( string $html ): string {
		$tags = new WP_HTML_Tag_Processor( $html );
		$keep = [];

		$this->map      = [];
		$this->conflict = false;

		while ( $tags->next_tag( [ 'tag_name' => 'img' ] ) ) {
			$class = $tags->get_attribute( 'class' );
			$src   = $tags->get_attribute( 'src' );

			if ( ! is_string( $class ) || ! is_string( $src ) || ! preg_match( '/\bwp-image-(\d+)\b/', $class, $found ) ) {
				continue;
			}

			$old = (int) $found[1];
			$new = $this->resolve( $src );

			if ( $new === $old || 0 === $new ) {
				$keep[ $old ] = true;
			}

			if ( $new === $old ) {
				continue;
			}

			$this->images[] = [
				'old'      => $old,
				'new'      => $new,
				'src'      => $src,
				'conflict' => false,
			];

			if ( 0 === $new ) {
				continue;
			}

			$this->conflict    = $this->conflict || ( $this->map[ $old ] ?? $new ) !== $new;
			$this->map[ $old ] = $new;

			$tags->set_attribute( 'class', (string) preg_replace( '/\bwp-image-' . $old . '\b/', 'wp-image-' . $new, $class ) );

			// The old gallery block rebuilds its inner image blocks from data-id.
			if ( (string) $old === $tags->get_attribute( 'data-id' ) ) {
				$tags->set_attribute( 'data-id', (string) $new );
			}
		}

		$this->conflict = $this->conflict || [] !== array_intersect_key( $this->map, $keep );

		return $tags->get_updated_html();
	}

	/**
	 * Report status for one image, given the post's write outcome.
	 *
	 * @param array{old: int, new: int, src: string, conflict: bool} $image       Image row from repair().
	 * @param string                                                 $post_status would fix, fixed, or failed.
	 */
	private function row_status( array $image, string $post_status ): string {
		if ( 0 === $image['new'] ) {
			return 'unresolved';
		}

		return $image['conflict'] ? 'conflict' : $post_status;
	}

	/**
	 * Find the attachment an image src belongs to, or 0.
	 *
	 * @param string $src Image src as written in the content.
	 */
	private function resolve( string $src ): int {
		if ( isset( $this->resolved[ $src ] ) ) {
			return $this->resolved[ $src ];
		}

		$url = strtok( $src, '?#' );
		$id  = attachment_url_to_postid( (string) $url );

		// A resized copy (photo-300x200.jpg) isn't the attached file; its original is.
		if ( 0 === $id ) {
			$original = ( new Content_Rewriter() )->strip_size_suffix( (string) $url );

			if ( $original !== $url ) {
				$id = attachment_url_to_postid( $original );

				// Big uploads are stored as photo-scaled.jpg.
				if ( 0 === $id ) {
					$id = attachment_url_to_postid( (string) preg_replace( '/(?=\.[A-Za-z0-9]+$)/', '-scaled', $original, 1 ) );
				}
			}
		}

		$this->resolved[ $src ] = $id;

		return $id;
	}

	/**
	 * Callback: remap every number in an ID attribute's value.
	 *
	 * @param array<int, string> $attr Regex match; [1] is the key, [2] the value.
	 */
	private function remap_id_list( array $attr ): string {
		return $attr[1] . preg_replace_callback( '/\d+/', [ $this, 'remap_id' ], $attr[2] );
	}

	/**
	 * Callback: the new ID for an old one, or the old one if it wasn't remapped.
	 *
	 * @param array<int, string> $id Regex match; [0] is the ID.
	 */
	private function remap_id( array $id ): string {
		return (string) ( $this->map[ (int) $id[0] ] ?? $id[0] );
	}

	/**
	 * IDs of posts whose content carries a `wp-image-` class.
	 *
	 * @param array<int, string> $types    Post types to scan.
	 * @param array<int, int>    $only_ids Restrict to these IDs; empty means all.
	 * @return array<int, int>
	 */
	private function candidate_posts( array $types, array $only_ids ): array {
		global $wpdb;

		$params = [ ...$types, '%wp-image-%' ];
		$sql    = $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type IN (" . implode( ',', array_fill( 0, count( $types ), '%s' ) ) . ") AND post_status NOT IN ('trash', 'auto-draft') AND post_content LIKE %s",
			$params
		);

		// wp_parse_id_list already cast these to positive ints.
		if ( [] !== $only_ids ) {
			$sql .= ' AND ID IN (' . implode( ',', $only_ids ) . ')';
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- one-off scan, prepared above.
		return array_map( 'intval', $wpdb->get_col( $sql . ' ORDER BY ID' ) );
	}

	/**
	 * Write post_content without wp_update_post; false when the write failed.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $content Repaired content.
	 */
	private function save_content( int $post_id, string $content ): bool {
		global $wpdb;

		// wp_update_post would bump post_modified and, with no CLI user, run kses over the body.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$written = $wpdb->update( $wpdb->posts, [ 'post_content' => $content ], [ 'ID' => $post_id ] );
		clean_post_cache( $post_id );

		return false !== $written;
	}
}
