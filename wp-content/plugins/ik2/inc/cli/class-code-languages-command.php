<?php
/**
 * `wp ik2 code-languages` — one-time backfill of the core/code `language` attribute.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI;

use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Sets `language` on attribute-less code blocks whose language can be detected.
 * Only the `<!-- wp:code -->` delimiter is rewritten, straight to the database,
 * so the rest of each post and its modified date stay as they were.
 */
class Code_Languages_Command {

	private const BLOCK_PATTERN = '/<!-- wp:code -->(.*?)<!-- \/wp:code -->/s';

	/**
	 * Language guesser shared by every block in the run.
	 *
	 * @var Code_Language_Detector
	 */
	private Code_Language_Detector $detector;

	/**
	 * Blocks seen per detected language (`none` for undetected).
	 *
	 * @var array<string,int>
	 */
	private array $counts = [];

	/**
	 * Language whose blocks get printed for review, from --show.
	 *
	 * @var string|null
	 */
	private ?string $show = null;

	/**
	 * Post being scanned, for --show output.
	 *
	 * @var int
	 */
	private int $post_id = 0;

	/**
	 * Detects and stores the language of legacy code blocks.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Report what would change without saving.
	 *
	 * [--post_type=<types>]
	 * : Comma-separated post types to scan.
	 * ---
	 * default: post,page,project
	 * ---
	 *
	 * [--show=<language>]
	 * : Print the first line of every block detected as this language (`none` for undetected) to spot-check the heuristics.
	 *
	 * ## EXAMPLES
	 *
	 *     # Preview the detection, then spot-check what was left plain.
	 *     $ wp ik2 code-languages --dry-run
	 *     $ wp ik2 code-languages --dry-run --show=none
	 *
	 * @param array<int, string>    $args       Positional arguments (unused).
	 * @param array<string, string> $assoc_args Associative arguments.
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		$dry_run        = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$types          = array_filter( array_map( 'trim', explode( ',', $assoc_args['post_type'] ?? 'post,page,project' ) ) );
		$this->show     = $assoc_args['show'] ?? null;
		$this->detector = new Code_Language_Detector();
		$changed        = 0;

		// Search for `wp:code` alone: WP search reads a leading `-` (as in `-->`) as an exclusion.
		$post_ids = get_posts(
			[
				'post_type'        => $types,
				'post_status'      => [ 'publish', 'draft', 'pending', 'private', 'future' ],
				'posts_per_page'   => -1,
				'fields'           => 'ids',
				's'                => 'wp:code',
				'search_columns'   => [ 'post_content' ],
				'suppress_filters' => false,
			]
		);

		foreach ( $post_ids as $post_id ) {
			$this->post_id = (int) $post_id;
			$content       = (string) get_post_field( 'post_content', $this->post_id, 'raw' );
			$updated       = preg_replace_callback( self::BLOCK_PATTERN, [ $this, 'tag_block' ], $content );

			if ( null === $updated || $updated === $content ) {
				continue;
			}

			if ( ! $dry_run && ! $this->save_content( $this->post_id, $updated ) ) {
				WP_CLI::warning( sprintf( 'Could not write post %d.', $this->post_id ) );
				continue;
			}

			++$changed;
		}

		arsort( $this->counts );

		$rows = [];
		foreach ( $this->counts as $language => $blocks ) {
			$rows[] = [
				'language' => $language,
				'blocks'   => $blocks,
			];
		}
		WP_CLI\Utils\format_items( 'table', $rows, [ 'language', 'blocks' ] );

		$verb = $dry_run ? 'Would update' : 'Updated';
		WP_CLI::success( sprintf( '%s %d of %d posts with code blocks.', $verb, $changed, count( $post_ids ) ) );
	}

	/**
	 * Rewrite one matched code block's delimiter with its detected language.
	 *
	 * @param array<int,string> $block Full match and inner HTML.
	 */
	public function tag_block( array $block ): string {
		$text     = html_entity_decode( wp_strip_all_tags( $block[1] ), ENT_QUOTES | ENT_HTML5 );
		$language = $this->detector->detect( $text );
		$key      = $language ?? 'none';

		$this->counts[ $key ] = ( $this->counts[ $key ] ?? 0 ) + 1;

		if ( $this->show === $key ) {
			WP_CLI::log( sprintf( '#%d  %s', $this->post_id, mb_substr( (string) strtok( trim( $text ), "\n" ), 0, 100 ) ) );
		}

		if ( null === $language ) {
			return $block[0];
		}

		return '<!-- wp:code {"language":"' . $language . '"} -->' . $block[1] . '<!-- /wp:code -->';
	}

	/**
	 * Write post_content without wp_update_post; false when the write failed.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $content Content with tagged code blocks.
	 */
	private function save_content( int $post_id, string $content ): bool {
		global $wpdb;

		// wp_update_post would bump post_modified on old posts (sitemap lastmod) and add a revision each.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$written = $wpdb->update( $wpdb->posts, [ 'post_content' => $content ], [ 'ID' => $post_id ] );
		clean_post_cache( $post_id );

		return false !== $written;
	}
}
