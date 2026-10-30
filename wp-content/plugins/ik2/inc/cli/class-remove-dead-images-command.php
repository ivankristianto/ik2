<?php
/**
 * `wp ik2 remove-dead-images` — fix image blocks that point at hosts that no longer exist.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI;

use WP_CLI;

use function IK2\Plugin\LegacyRedirects\attachment_url_by_filename;

defined( 'ABSPATH' ) || exit;

/**
 * Old posts embed images from the old dev host and a CDN that no longer
 * resolves. Each such core/image block is repointed at its media-library
 * copy when one exists, and removed otherwise. Content is written directly,
 * so the post's modified date does not change for a broken-image cleanup.
 */
class Remove_Dead_Images_Command {

	const DEAD_HOSTS = [ 'www.ivankristianto.local', 'cdn.ivankristianto.com' ];

	/**
	 * Repoints or removes image blocks served from dead hosts.
	 *
	 * ## OPTIONS
	 *
	 * [--exclude=<slugs>]
	 * : Comma-separated post slugs to leave alone.
	 *
	 * [--dry-run]
	 * : Report what would change; write nothing.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp ik2 remove-dead-images --exclude=git-introduction --dry-run
	 *
	 * @param array<int, string>   $args       Positional arguments (unused).
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		global $wpdb;

		$dry_run = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$exclude = array_filter( array_map( 'trim', explode( ',', (string) ( $assoc_args['exclude'] ?? '' ) ) ) );

		$like = [];
		foreach ( self::DEAD_HOSTS as $host ) {
			$like[] = $wpdb->prepare( 'post_content LIKE %s', '%' . $wpdb->esc_like( $host ) . '%' );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- one-off maintenance read; the LIKE clauses are prepared above.
		$posts = $wpdb->get_results( "SELECT ID, post_name, post_content FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' AND (" . implode( ' OR ', $like ) . ')' );

		$rows = [];

		foreach ( $posts as $post ) {
			if ( in_array( $post->post_name, $exclude, true ) ) {
				continue;
			}

			$tally  = [
				'repointed' => 0,
				'removed'   => 0,
			];
			$blocks = $this->fix_blocks( parse_blocks( $post->post_content ), $tally );

			if ( 0 === $tally['repointed'] + $tally['removed'] ) {
				continue;
			}

			$rows[] = [
				'ID'        => $post->ID,
				'post_name' => $post->post_name,
				'repointed' => $tally['repointed'],
				'removed'   => $tally['removed'],
			];

			if ( $dry_run ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- direct write keeps post_modified as is.
			$wpdb->update( $wpdb->posts, [ 'post_content' => serialize_blocks( $blocks ) ], [ 'ID' => (int) $post->ID ] );
			clean_post_cache( (int) $post->ID );
		}

		WP_CLI\Utils\format_items( 'table', $rows, [ 'ID', 'post_name', 'repointed', 'removed' ] );
		WP_CLI::success( sprintf( '%s %d post(s).', $dry_run ? 'Would change' : 'Changed', count( $rows ) ) );
	}

	/**
	 * Walk blocks, repointing or dropping dead-host core/image blocks.
	 *
	 * @param array<int, array<string, mixed>> $blocks Parsed blocks.
	 * @param array<string, int>               $tally  Running counts, by reference.
	 * @return array<int, array<string, mixed>>
	 */
	private function fix_blocks( array $blocks, array &$tally ): array {
		$out = [];

		foreach ( $blocks as $block ) {
			if ( ! empty( $block['innerBlocks'] ) ) {
				$block['innerBlocks'] = $this->fix_blocks( $block['innerBlocks'], $tally );
			}

			if ( 'core/paragraph' === $block['blockName'] ) {
				$block = $this->strip_inline_images( $block, $tally );

				if ( null !== $block ) {
					$out[] = $block;
				}
				continue;
			}

			$src = 'core/image' === $block['blockName'] ? $this->dead_src( (string) $block['innerHTML'] ) : null;

			if ( null === $src ) {
				$out[] = $block;
				continue;
			}

			$copy = attachment_url_by_filename( basename( (string) wp_parse_url( $src, PHP_URL_PATH ) ) );

			if ( null === $copy ) {
				++$tally['removed'];
				continue;
			}

			$block['innerHTML'] = str_replace( $src, $copy, (string) $block['innerHTML'] );

			// Null entries mark where inner blocks go; leave them as they are.
			foreach ( $block['innerContent'] as $i => $chunk ) {
				if ( is_string( $chunk ) ) {
					$block['innerContent'][ $i ] = str_replace( $src, $copy, $chunk );
				}
			}

			++$tally['repointed'];
			$out[] = $block;
		}

		return $out;
	}

	/**
	 * Drop dead-host <img> tags (and a link wrapping only that image) from a paragraph.
	 *
	 * @param array<string, mixed> $block Paragraph block.
	 * @param array<string, int>   $tally Running counts, by reference.
	 * @return array<string, mixed>|null Null when nothing but whitespace is left.
	 */
	private function strip_inline_images( array $block, array &$tally ): ?array {
		$hosts = implode( '|', array_map( 'preg_quote', self::DEAD_HOSTS ) );
		$img   = '<img\\b[^>]*\\bsrc="https?://(?:' . $hosts . ')/[^"]*"[^>]*>';
		$regex = '#(?:<a\\b[^>]*>\\s*' . $img . '\\s*</a>|' . $img . ')#i';
		$html  = (string) $block['innerHTML'];
		$fixed = (string) preg_replace( $regex, '', $html, -1, $count );

		if ( 0 === $count ) {
			return $block;
		}

		$tally['removed'] += $count;

		if ( '' === trim( wp_strip_all_tags( str_ireplace( [ '<br>', '<br/>', '<br />' ], '', $fixed ) ) ) ) {
			return null;
		}

		$block['innerHTML']    = $fixed;
		$block['innerContent'] = [ $fixed ];

		return $block;
	}

	/**
	 * The img src in this markup when it points at a dead host, else null.
	 *
	 * @param string $html Block markup.
	 */
	private function dead_src( string $html ): ?string {
		if ( ! preg_match( '/<img[^>]+src="([^"]+)"/i', $html, $m ) ) {
			return null;
		}

		return in_array( (string) wp_parse_url( $m[1], PHP_URL_HOST ), self::DEAD_HOSTS, true ) ? $m[1] : null;
	}
}
