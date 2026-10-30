<?php
/**
 * `wp ik2 link-images` — give migrated body images their attachment ID and real alt text.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI;

use WP_CLI;
use WP_HTML_Tag_Processor;

defined( 'ABSPATH' ) || exit;

/**
 * The migration left most body images as image blocks with no `id` and no
 * `wp-image-N` class, so core can't add width, height, srcset, or sizes.
 * Linking them to their attachment lets core do that at render time.
 */
class Link_Images_Command {

	/**
	 * Alt text that says nothing about the image.
	 */
	private const GENERIC_ALT = '/^(?:image|img|photo|picture|screenshot|screen ?shot|untitled|wpid\S*)$/i';

	/**
	 * The reviewed alt text for migrated images, shipped with the plugin so prod can read it.
	 */
	private const ALT_FILE = __DIR__ . '/../../data/legacy-image-alts.tsv';

	/**
	 * Maps image srcs to attachment IDs.
	 *
	 * @var Attachment_Resolver
	 */
	private Attachment_Resolver $resolver;

	/**
	 * Alt text per file name, from the alt file.
	 *
	 * @var array<string, string>
	 */
	private array $alts = [];

	/**
	 * Counts for the post being processed.
	 *
	 * @var array{linked: int, alt: int}
	 */
	private array $tally = [
		'linked' => 0,
		'alt'    => 0,
	];

	/**
	 * Images in the post being processed that still need alt text.
	 *
	 * @var array<int, array{file: string, alt: string}>
	 */
	private array $missing = [];

	/**
	 * Set up the shared resolver.
	 */
	public function __construct() {
		$this->resolver = new Attachment_Resolver();
	}

	/**
	 * Links body images to their attachments and fills in missing alt text.
	 *
	 * Image blocks get their `id` attribute and the `wp-image-N` class core
	 * writes; images inside paragraphs, lists, and classic content get the
	 * class. Images with no attachment, or a gallery image whose data-id
	 * points elsewhere, are left alone. Images that already carry a
	 * `wp-image-N` class are `wp ik2 fix-image-ids` territory.
	 *
	 * With --alts, an image whose alt is empty, generic, or its file name gets
	 * the alt from data/legacy-image-alts.tsv, and so does its attachment when
	 * it has none, so featured images pick it up too.
	 *
	 * Writes post_content directly, so post_modified is untouched.
	 *
	 * ## OPTIONS
	 *
	 * [--post=<ids>]
	 * : Only these post IDs, comma-separated.
	 *
	 * [--alts]
	 * : Also apply the alt text in data/legacy-image-alts.tsv.
	 *
	 * [--alt-file=<file>]
	 * : Read alt text from this `file-name<TAB>alt text` file instead. Implies --alts.
	 *
	 * [--needs-alt]
	 * : List images, and featured images, in indexed posts that still need alt text. Writes nothing.
	 *
	 * [--dry-run]
	 * : Report what would change; write nothing.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp ik2 link-images --dry-run
	 *     $ wp ik2 link-images
	 *     $ wp ik2 link-images --needs-alt > needs-alt.tsv
	 *     $ wp ik2 link-images --alts --dry-run
	 *
	 * @param array<int, string>   $args       Positional arguments (unused).
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		$dry_run   = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$needs_alt = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'needs-alt', false );
		$only      = wp_parse_id_list( (string) WP_CLI\Utils\get_flag_value( $assoc_args, 'post', '' ) );

		// An empty --post= would otherwise widen the run to every post.
		if ( isset( $assoc_args['post'] ) && [] === $only ) {
			WP_CLI::error( '--post needs one or more post IDs.' );
		}

		if ( isset( $assoc_args['alt-file'] ) || WP_CLI\Utils\get_flag_value( $assoc_args, 'alts', false ) ) {
			$this->alts = $this->read_alts( (string) ( $assoc_args['alt-file'] ?? self::ALT_FILE ) );
		}

		if ( $needs_alt ) {
			$this->report_needs_alt( $only );
			return;
		}

		$rows    = [];
		$changed = 0;

		foreach ( $this->candidate_posts( $only ) as $post ) {
			$this->tally = [
				'linked' => 0,
				'alt'    => 0,
			];

			$content = serialize_blocks( $this->walk( parse_blocks( $post->post_content ) ) );

			if ( 0 === $this->tally['linked'] + $this->tally['alt'] ) {
				continue;
			}

			$rows[] = [
				'ID'        => $post->ID,
				'post_name' => $post->post_name,
				'linked'    => $this->tally['linked'],
				'alt'       => $this->tally['alt'],
			];
			++$changed;

			if ( ! $dry_run ) {
				$this->save_content( (int) $post->ID, $content );
			}
		}

		if ( ! $dry_run ) {
			$this->update_attachment_alts();
		}

		WP_CLI\Utils\format_items( 'table', $rows, [ 'ID', 'post_name', 'linked', 'alt' ] );
		if ( ! $dry_run && $changed > 0 ) {
			WP_CLI::log( 'Delete the WP Super Cache page cache in wp-admin (or restart the app container), then purge the CDN.' );
		}

		WP_CLI::success( sprintf( '%s %d post(s).', $dry_run ? 'Would change' : 'Changed', $changed ) );
	}

	/**
	 * Link and label the images in a list of blocks, recursing into inner blocks.
	 *
	 * @param array<int, array<string, mixed>> $blocks Parsed blocks.
	 * @return array<int, array<string, mixed>>
	 */
	private function walk( array $blocks ): array {
		foreach ( $blocks as $i => $block ) {
			if ( ! empty( $block['innerBlocks'] ) ) {
				$block['innerBlocks'] = $this->walk( $block['innerBlocks'] );
			}

			$is_image = 'core/image' === $block['blockName'];
			$linked   = 0;

			// Null entries mark where inner blocks go; leave them as they are.
			foreach ( $block['innerContent'] as $j => $chunk ) {
				if ( is_string( $chunk ) && str_contains( $chunk, '<img' ) ) {
					$block['innerContent'][ $j ] = $this->fix_images( $chunk, $is_image, $linked );
				}
			}

			$block['innerHTML'] = $this->fix_images( (string) $block['innerHTML'], $is_image, $linked, false );

			// Core's image block writes the class from the id attribute; keep the two in step.
			if ( $is_image && $linked > 0 && empty( $block['attrs']['id'] ) ) {
				$block['attrs']['id'] = $linked;
			}

			$blocks[ $i ] = $block;
		}

		return $blocks;
	}

	/**
	 * Add the `wp-image-N` class and missing alt text to the images in one chunk.
	 *
	 * @param string $html     Block markup.
	 * @param bool   $is_image Whether the chunk belongs to an image block.
	 * @param int    $linked   Attachment ID linked in an image block, by reference.
	 * @param bool   $count    Whether to count changes; innerHTML repeats innerContent.
	 */
	private function fix_images( string $html, bool $is_image, int &$linked, bool $count = true ): string {
		$tags = new WP_HTML_Tag_Processor( $html );

		while ( $tags->next_tag( [ 'tag_name' => 'img' ] ) ) {
			$src = $tags->get_attribute( 'src' );

			if ( ! is_string( $src ) ) {
				continue;
			}

			$class = (string) $tags->get_attribute( 'class' );
			$id    = $this->resolver->resolve( $src );

			if ( 0 === $id ) {
				continue;
			}

			$data_id = $tags->get_attribute( 'data-id' );

			// A v1 gallery rebuilds its images from data-id; a different answer is fix-image-ids' job.
			$can_link = ! preg_match( '/\bwp-image-\d+\b/', $class ) && ( ! is_string( $data_id ) || (int) $data_id === $id );

			if ( $can_link ) {
				$tags->add_class( 'wp-image-' . $id );
				$linked = $is_image ? $id : $linked;

				if ( $count ) {
					++$this->tally['linked'];
				}
			}

			$file = $this->file_name( $src );
			$alt  = (string) $tags->get_attribute( 'alt' );

			if ( isset( $this->alts[ $file ] ) && $this->alts[ $file ] !== $alt && $this->needs_alt( $alt, $file ) ) {
				$tags->set_attribute( 'alt', $this->alts[ $file ] );

				if ( $count ) {
					++$this->tally['alt'];
				}
			}
		}

		return $tags->get_updated_html();
	}

	/**
	 * Whether an alt says nothing: empty, generic, or the file name.
	 *
	 * @param string $alt  Current alt.
	 * @param string $file File name, size suffix stripped.
	 */
	private function needs_alt( string $alt, string $file ): bool {
		$alt = trim( $alt );

		if ( '' === $alt || preg_match( self::GENERIC_ALT, $alt ) ) {
			return true;
		}

		return self::normalise( $alt ) === self::normalise( (string) pathinfo( $file, PATHINFO_FILENAME ) );
	}

	/**
	 * Lowercase, with runs of spaces, dashes, underscores, and dots as one space.
	 *
	 * @param string $text Alt or file name.
	 */
	private static function normalise( string $text ): string {
		return strtolower( (string) preg_replace( '/[\s_\-.]+/', ' ', $text ) );
	}

	/**
	 * The upload's file name with the size suffix stripped, the key in the alt file.
	 *
	 * @param string $src Image src.
	 */
	private function file_name( string $src ): string {
		$name = basename( (string) wp_parse_url( $src, PHP_URL_PATH ) );

		return (string) preg_replace( '/-(?:\d+x\d+|scaled)(?=\.[A-Za-z0-9]+$)/', '', $name );
	}

	/**
	 * Print a TSV of images in indexed posts whose alt says nothing.
	 *
	 * @param array<int, int> $only Restrict to these post IDs; empty means all.
	 */
	private function report_needs_alt( array $only ): void {
		$seen = [];

		WP_CLI::line( "post\tkind\tfile\tsrc\tcurrent alt" );

		foreach ( $this->candidate_posts( $only, true ) as $post ) {
			if ( '1' === get_post_meta( (int) $post->ID, '_yoast_wpseo_meta-robots-noindex', true ) ) {
				continue;
			}

			$thumb = (int) get_post_thumbnail_id( (int) $post->ID );

			if ( $thumb > 0 && '' === trim( (string) get_post_meta( $thumb, '_wp_attachment_image_alt', true ) ) ) {
				$src  = (string) wp_get_attachment_url( $thumb );
				$file = $this->file_name( $src );

				if ( ! isset( $this->alts[ $file ] ) && ! isset( $seen[ $file ] ) ) {
					$seen[ $file ] = true;
					WP_CLI::line( implode( "\t", [ $post->post_name, 'featured', $file, $src, '' ] ) );
				}
			}

			$tags = new WP_HTML_Tag_Processor( $post->post_content );

			while ( $tags->next_tag( [ 'tag_name' => 'img' ] ) ) {
				$src = $tags->get_attribute( 'src' );

				if ( ! is_string( $src ) || 0 === $this->resolver->resolve( $src ) ) {
					continue;
				}

				$file = $this->file_name( $src );
				$alt  = (string) $tags->get_attribute( 'alt' );

				if ( isset( $this->alts[ $file ] ) || isset( $seen[ $file ] ) || ! $this->needs_alt( $alt, $file ) ) {
					continue;
				}

				$seen[ $file ] = true;
				WP_CLI::line( implode( "\t", [ $post->post_name, 'body', $file, $src, $alt ] ) );
			}
		}
	}

	/**
	 * Give attachments with no alt the alt from the file, so featured images carry it.
	 */
	private function update_attachment_alts(): void {
		$updated = 0;

		foreach ( $this->alts as $file => $alt ) {
			$id = $this->attachment_by_file( $file );

			if ( 0 === $id || '' !== trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ) ) {
				continue;
			}

			// update_post_meta unslashes; a Windows path in an alt would lose its backslashes.
			update_post_meta( $id, '_wp_attachment_image_alt', wp_slash( $alt ) );
			++$updated;
		}

		if ( $updated > 0 ) {
			WP_CLI::log( sprintf( 'Set alt text on %d attachment(s).', $updated ) );
		}
	}

	/**
	 * The attachment stored under this file name (or its -scaled copy), or 0.
	 *
	 * @param string $file File name, size suffix stripped.
	 */
	private function attachment_by_file( string $file ): int {
		global $wpdb;

		$scaled = (string) preg_replace( '/(?=\.[A-Za-z0-9]+$)/', '-scaled', $file, 1 );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- one indexed meta lookup per alt line.
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND ( meta_value LIKE %s OR meta_value LIKE %s ) ORDER BY post_id DESC LIMIT 1",
				'%/' . $wpdb->esc_like( $file ),
				'%/' . $wpdb->esc_like( $scaled )
			)
		);
	}

	/**
	 * Read the alt file: `file-name<TAB>alt text` per line, # comments allowed.
	 *
	 * @param string $path File path.
	 * @return array<string, string>
	 */
	private function read_alts( string $path ): array {
		if ( ! is_readable( $path ) ) {
			WP_CLI::error( "Can't read {$path}." );
		}

		$alts = [];

		foreach ( (array) file( $path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) as $line ) {
			$parts = explode( "\t", (string) $line );

			if ( str_starts_with( (string) $line, '#' ) || count( $parts ) < 2 || '' === trim( $parts[1] ) ) {
				continue;
			}

			$alts[ $this->file_name( trim( $parts[0] ) ) ] = trim( $parts[1] );
		}

		return $alts;
	}

	/**
	 * Published posts with an image in the body (or a featured image, for the report).
	 *
	 * @param array<int, int> $only          Restrict to these IDs; empty means all.
	 * @param bool            $with_featured Also include posts whose only image is the featured one.
	 * @return array<int, object>
	 */
	private function candidate_posts( array $only, bool $with_featured = false ): array {
		global $wpdb;

		$where = $with_featured
			? "(post_content LIKE '%<img%' OR ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id'))"
			: "post_content LIKE '%<img%'";

		$sql = "SELECT ID, post_name, post_content FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' AND {$where}";

		// wp_parse_id_list already cast these to positive ints.
		if ( [] !== $only ) {
			$sql .= ' AND ID IN (' . implode( ',', $only ) . ')';
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- one-off scan with no user input.
		return (array) $wpdb->get_results( $sql . ' ORDER BY ID' );
	}

	/**
	 * Write post_content without wp_update_post, which would bump post_modified.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $content New content.
	 */
	private function save_content( int $post_id, string $content ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update( $wpdb->posts, [ 'post_content' => $content ], [ 'ID' => $post_id ] );
		clean_post_cache( $post_id );
	}
}
