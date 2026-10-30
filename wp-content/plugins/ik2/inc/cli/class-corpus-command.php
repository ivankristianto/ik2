<?php
/**
 * `wp ik2 corpus`: queue Articles for the embedder and inspect the Corpus.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI;

use IK2\Plugin\Corpus;
use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Queues, reports on and previews the Corpus the embedder worker reads.
 */
class Corpus_Command {

	/**
	 * Queues Articles for the next embedder Run.
	 *
	 * Articles that no longer meet the inclusion rules are marked for removal.
	 *
	 * ## OPTIONS
	 *
	 * [<id>...]
	 * : Article IDs to queue.
	 *
	 * [--all]
	 * : Walk every published Article, and mark Documents whose Article left the Corpus for removal.
	 *
	 * [--force]
	 * : Queue even when the content hash is unchanged. This is how a parked Document is retried.
	 *
	 * ## EXAMPLES
	 *
	 *     # First backfill.
	 *     $ wp ik2 corpus enqueue --all
	 *
	 *     # Retry one parked Article.
	 *     $ wp ik2 corpus enqueue 503 --force
	 *
	 * @param array<int, string>         $args       Article IDs.
	 * @param array<string, string|bool> $assoc_args Associative arguments.
	 */
	public function enqueue( array $args, array $assoc_args ): void {
		$all   = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'all', false );
		$force = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'force', false );

		if ( $all === ( [] !== $args ) ) {
			WP_CLI::error( 'Pass Article IDs or --all, but not both.' );
		}

		$ids    = $all ? $this->published_article_ids() : array_map( 'intval', $args );
		$counts = [
			'queued'    => 0,
			'unchanged' => 0,
			'removed'   => 0,
			'skipped'   => 0,
			'failed'    => 0,
		];

		$progress = WP_CLI\Utils\make_progress_bar( 'Queueing Articles', count( $ids ) );

		foreach ( $ids as $i => $id ) {
			$result = Corpus\sync_post( $id, $force );
			++$counts[ $result ];

			if ( ! $all && 'skipped' === $result ) {
				WP_CLI::warning( "{$id} is not an Article in the Corpus." );
			}

			if ( 'failed' === $result ) {
				WP_CLI::warning( "{$id} could not be written: " . $GLOBALS['wpdb']->last_error );
			}

			// The runtime cache keeps every post, term and meta row loaded so far.
			if ( 0 === ( $i + 1 ) % 50 ) {
				wp_cache_flush_runtime();
			}

			$progress->tick();
		}

		$progress->finish();

		if ( $all ) {
			$counts['removed'] += $this->remove_missing( $ids );
		}

		$message = sprintf(
			'%d queued, %d unchanged, %d marked for removal, %d skipped, %d failed.',
			$counts['queued'],
			$counts['unchanged'],
			$counts['removed'],
			$counts['skipped'],
			$counts['failed']
		);

		if ( $counts['failed'] > 0 ) {
			WP_CLI::error( $message );
		}

		WP_CLI::success( $message );
	}

	/**
	 * Shows Documents by status, parked Documents, Chunk and vector counts, and the last Run.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp ik2 corpus status
	 */
	public function status(): void {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery -- custom Corpus tables, read by hand from the CLI.
		$statuses = $wpdb->get_results(
			$wpdb->prepare( 'SELECT status, COUNT(*) AS documents FROM %i GROUP BY status ORDER BY status', Corpus\table( 'documents' ) ),
			ARRAY_A
		);
		$parked   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT object_id AS post_id, title, attempts, last_error FROM %i WHERE status = 'parked' ORDER BY object_id",
				Corpus\table( 'documents' )
			),
			ARRAY_A
		);
		$counts   = [
			'chunks'           => (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', Corpus\table( 'chunks' ) ) ),
			'document vectors' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE level = 'document'", Corpus\table( 'vectors' ) ) ),
			'chunk vectors'    => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE level = 'chunk'", Corpus\table( 'vectors' ) ) ),
			'related rows'     => (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', Corpus\table( 'related' ) ) ),
		];
		$run      = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i ORDER BY id DESC LIMIT 1', Corpus\table( 'runs' ) ), ARRAY_A );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery

		WP_CLI::log( 'Documents' );
		if ( [] === $statuses ) {
			WP_CLI::log( '  None yet. Run `wp ik2 corpus enqueue --all`.' );
		} else {
			WP_CLI\Utils\format_items( 'table', $statuses, [ 'status', 'documents' ] );
		}

		if ( [] !== $parked ) {
			WP_CLI::log( '' );
			WP_CLI::log( 'Parked' );
			WP_CLI\Utils\format_items( 'table', $parked, [ 'post_id', 'title', 'attempts', 'last_error' ] );
		}

		WP_CLI::log( '' );
		WP_CLI\Utils\format_items( 'table', $this->rows( $counts ), [ 'metric', 'value' ] );

		WP_CLI::log( '' );
		WP_CLI::log( 'Last Run' );
		if ( ! is_array( $run ) ) {
			WP_CLI::log( '  None yet.' );
			return;
		}

		WP_CLI\Utils\format_items( 'table', $this->rows( $run ), [ 'metric', 'value' ] );
	}

	/**
	 * Prints the Document text and Chunks an Article would get, without writing anything.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : Article ID.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp ik2 corpus preview 503
	 *
	 * @param array<int, string> $args Article ID.
	 */
	public function preview( array $args ): void {
		$post = get_post( (int) $args[0] );

		if ( ! $post instanceof \WP_Post || Corpus\OBJECT_TYPE !== $post->post_type ) {
			WP_CLI::error( "{$args[0]} is not an Article." );
		}

		if ( ! Corpus\is_included( $post ) ) {
			WP_CLI::warning( 'This Article is outside the Corpus (not published, password, noindex or excluded).' );
		}

		$document = Corpus\build_document( $post );
		$tokens   = 0;

		WP_CLI::log( WP_CLI::colorize( '%Gembed_text%n' ) );
		WP_CLI::log( $document['embed_text'] );

		foreach ( $document['chunks'] as $chunk ) {
			$tokens += $chunk['token_estimate'];

			WP_CLI::log( '' );
			WP_CLI::log(
				WP_CLI::colorize(
					sprintf(
						'%%GChunk %d%%n  heading: %s  anchor: %s  ~%d tokens',
						$chunk['chunk_index'],
						'' === $chunk['heading'] ? '(opening)' : $chunk['heading'],
						'' === $chunk['anchor'] ? '(none)' : '#' . $chunk['anchor'],
						$chunk['token_estimate']
					)
				)
			);
			WP_CLI::log( $chunk['text'] );
		}

		WP_CLI::log( '' );
		WP_CLI::log( sprintf( '%d chunks, ~%d tokens, content hash %s', count( $document['chunks'] ), $tokens, $document['content_hash'] ) );
	}

	/**
	 * IDs of every published Article, oldest first.
	 *
	 * @return list<int>
	 */
	private function published_article_ids(): array {
		return array_map(
			'intval',
			get_posts(
				[
					'post_type'        => Corpus\OBJECT_TYPE,
					'post_status'      => 'publish',
					'posts_per_page'   => -1,
					'fields'           => 'ids',
					'orderby'          => 'ID',
					'order'            => 'ASC',
					'suppress_filters' => true,
				]
			)
		);
	}

	/**
	 * Mark Documents whose Article is no longer published, such as one deleted
	 * while the plugin was off.
	 *
	 * @param int[] $published Published Article IDs.
	 * @return int Documents marked for removal.
	 */
	private function remove_missing( array $published ): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- custom Corpus table.
		$stored = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT object_id FROM %i WHERE object_type = %s AND status <> 'remove'",
				Corpus\table( 'documents' ),
				Corpus\OBJECT_TYPE
			)
		);

		$removed = 0;

		foreach ( array_diff( array_map( 'intval', $stored ), $published ) as $post_id ) {
			$removed += (int) Corpus\mark_remove( $post_id );
		}

		return $removed;
	}

	/**
	 * Turn a key => value map into metric/value rows for format_items().
	 *
	 * @param array<string, mixed> $values Values by label.
	 * @return list<array{metric:string, value:mixed}>
	 */
	private function rows( array $values ): array {
		$rows = [];

		foreach ( $values as $metric => $value ) {
			$rows[] = [
				'metric' => (string) $metric,
				'value'  => $value,
			];
		}

		return $rows;
	}
}
