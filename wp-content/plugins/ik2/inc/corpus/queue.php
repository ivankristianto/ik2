<?php
/**
 * Decide which Articles belong in the Corpus and queue them for the worker.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\Corpus;

defined( 'ABSPATH' ) || exit;

const OBJECT_TYPE = 'post';

const NOINDEX_META = '_yoast_wpseo_meta-robots-noindex';

const EXCLUDE_META = '_ik2_corpus_exclude';

// Document statuses, shared with services/embedder/src/staleness.mjs.
const STATUS_QUEUED  = 'queued';
const STATUS_INDEXED = 'indexed';
const STATUS_REMOVE  = 'remove';
const STATUS_PARKED  = 'parked';

/**
 * Whether a post meets the Corpus inclusion rules.
 *
 * @param \WP_Post $post Post to check.
 */
function is_included( \WP_Post $post ): bool {
	return OBJECT_TYPE === $post->post_type
		&& 'publish' === $post->post_status
		&& '' === $post->post_password
		&& '1' !== (string) get_post_meta( $post->ID, NOINDEX_META, true )
		&& empty( get_post_meta( $post->ID, EXCLUDE_META, true ) );
}

/**
 * Queue the Article or mark it for removal, whichever the inclusion rules say.
 *
 * @param int $post_id Post ID.
 */
function sync( int $post_id ): void {
	sync_post( $post_id );
}

/**
 * Build the Article's Document and Chunks and queue them when they changed.
 *
 * @param int  $post_id Post ID.
 * @param bool $force   Queue even when the content hash is unchanged.
 */
function enqueue( int $post_id, bool $force = false ): void {
	queue_post( $post_id, $force );
}

/**
 * The sync() decision, reporting what happened for WP-CLI.
 *
 * @param int  $post_id Post ID.
 * @param bool $force   Passed to queue_post().
 * @return string queued, unchanged, removed or skipped.
 */
function sync_post( int $post_id, bool $force = false ): string {
	$post = get_post( $post_id );

	if ( ! $post instanceof \WP_Post || OBJECT_TYPE !== $post->post_type ) {
		return 'skipped';
	}

	if ( ! is_included( $post ) ) {
		return mark_remove( $post_id ) ? 'removed' : 'skipped';
	}

	return queue_post( $post_id, $force );
}

/**
 * The enqueue() work, reporting what happened for WP-CLI.
 *
 * @param int  $post_id Post ID.
 * @param bool $force   Queue even when the content hash is unchanged.
 * @return string queued, unchanged, skipped or failed.
 */
function queue_post( int $post_id, bool $force = false ): string {
	global $wpdb;

	$post = get_post( $post_id );

	if ( ! $post instanceof \WP_Post || ! is_included( $post ) ) {
		return 'skipped';
	}

	$document = build_document( $post );
	$existing = find_document( $post_id );

	if (
		! $force
		&& null !== $existing
		&& $existing['content_hash'] === $document['content_hash']
		&& in_array( $existing['status'], [ STATUS_QUEUED, STATUS_INDEXED ], true )
	) {
		return 'unchanged';
	}

	// A Run must never read the new Document hash beside half-written Chunks.
	$wpdb->query( 'START TRANSACTION' );

	$ok = false !== $wpdb->query(
		$wpdb->prepare(
			'INSERT INTO %i (object_type, object_id, title, url, embed_text, embed_hash, content_hash, status, attempts, queued_at)
			VALUES (%s, %d, %s, %s, %s, %s, %s, %s, 0, %s)
			ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), title = VALUES(title), url = VALUES(url),
				embed_text = VALUES(embed_text), embed_hash = VALUES(embed_hash), content_hash = VALUES(content_hash),
				status = VALUES(status), attempts = 0, queued_at = VALUES(queued_at)',
			table( 'documents' ),
			OBJECT_TYPE,
			$post_id,
			$document['title'],
			$document['url'],
			$document['embed_text'],
			$document['embed_hash'],
			$document['content_hash'],
			STATUS_QUEUED,
			current_time( 'mysql', true )
		)
	);

	$document_id = (int) $wpdb->insert_id;

	// Upserting keeps Chunk IDs stable, so the worker re-embeds only Chunks whose text changed.
	foreach ( $document['chunks'] as $chunk ) {
		$ok = $ok && false !== $wpdb->query(
			$wpdb->prepare(
				'INSERT INTO %i (document_id, chunk_index, heading, anchor, text, token_estimate, content_hash)
				VALUES (%d, %d, %s, %s, %s, %d, %s)
				ON DUPLICATE KEY UPDATE heading = VALUES(heading), anchor = VALUES(anchor), text = VALUES(text),
					token_estimate = VALUES(token_estimate), content_hash = VALUES(content_hash)',
				table( 'chunks' ),
				$document_id,
				$chunk['chunk_index'],
				$chunk['heading'],
				$chunk['anchor'],
				$chunk['text'],
				min( $chunk['token_estimate'], 65535 ),
				hash( 'sha256', $chunk['text'] )
			)
		);
	}

	$ok = $ok && false !== $wpdb->query(
		$wpdb->prepare(
			'DELETE FROM %i WHERE document_id = %d AND chunk_index >= %d',
			table( 'chunks' ),
			$document_id,
			count( $document['chunks'] )
		)
	);

	if ( ! $ok ) {
		// ROLLBACK clears last_error, which the CLI prints for a failed Article.
		$error = $wpdb->last_error;
		$wpdb->query( 'ROLLBACK' );
		$wpdb->last_error = $error;
		return 'failed';
	}

	$wpdb->query( 'COMMIT' );

	return 'queued';
}

/**
 * Mark an Article's Document for removal by the next Run.
 *
 * @param int $post_id Post ID.
 * @return bool Whether a Document row changed.
 */
function mark_remove( int $post_id ): bool {
	global $wpdb;

	// Attempts go back to 0: the worker skips rows at 3, which would keep a parked Document forever.
	$changed = $wpdb->query(
		$wpdb->prepare(
			'UPDATE %i SET status = %s, attempts = 0, queued_at = %s
			WHERE object_type = %s AND object_id = %d AND status <> %s',
			table( 'documents' ),
			STATUS_REMOVE,
			current_time( 'mysql', true ),
			OBJECT_TYPE,
			$post_id,
			STATUS_REMOVE
		)
	);

	return (int) $changed > 0;
}

/**
 * The stored Document row for a post, or null.
 *
 * @param int $post_id Post ID.
 * @return array{id:string, content_hash:string, status:string}|null
 */
function find_document( int $post_id ): ?array {
	global $wpdb;

	$row = $wpdb->get_row(
		$wpdb->prepare(
			'SELECT id, content_hash, status FROM %i WHERE object_type = %s AND object_id = %d',
			table( 'documents' ),
			OBJECT_TYPE,
			$post_id
		),
		ARRAY_A
	);

	return is_array( $row ) ? $row : null;
}

/**
 * Clean, chunk and hash an Article without writing anything.
 *
 * @param \WP_Post $post Article.
 * @return array{title:string, url:string, embed_text:string, embed_hash:string, content_hash:string, chunks:list<array{chunk_index:int, heading:string, anchor:string, text:string, token_estimate:int}>}
 */
function build_document( \WP_Post $post ): array {
	$title    = plain_text( $post->post_title );
	$sections = Text\clean_blocks( parse_blocks( strip_shortcodes( $post->post_content ) ), 'render_block' );
	$chunks   = Text\chunk_sections( $title, $sections );
	$text     = Text\build_embed_text( $title, term_names( $post, 'category' ), term_names( $post, 'post_tag' ), $sections );
	$url      = (string) get_permalink( $post );

	return [
		'title'        => $title,
		'url'          => $url,
		'embed_text'   => $text,
		'embed_hash'   => hash( 'sha256', $text ),
		// Covers every stored field, title, URL and anchors too, so a change to any of them re-queues.
		'content_hash' => hash( 'sha256', (string) wp_json_encode( [ $title, $url, $text, $chunks ] ) ),
		'chunks'       => $chunks,
	];
}

/**
 * Names of the post's terms in a taxonomy, decoded to plain text.
 *
 * @param \WP_Post $post     Article.
 * @param string   $taxonomy Taxonomy slug.
 * @return list<string>
 */
function term_names( \WP_Post $post, string $taxonomy ): array {
	$terms = get_the_terms( $post, $taxonomy );

	if ( ! is_array( $terms ) ) {
		return [];
	}

	return array_values( array_map( __NAMESPACE__ . '\\term_name', $terms ) );
}

/**
 * A term's name as plain text.
 *
 * @param \WP_Term $term Term.
 */
function term_name( \WP_Term $term ): string {
	return plain_text( $term->name );
}

/**
 * Strip tags and decode entities from a stored title or term name.
 *
 * @param string $text Raw text.
 */
function plain_text( string $text ): string {
	return trim( html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
}

/**
 * IDs of every published Article, oldest first.
 *
 * @return list<int>
 */
function published_article_ids(): array {
	return array_map(
		'intval',
		get_posts(
			[
				'post_type'        => OBJECT_TYPE,
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
 * Mark Documents whose Article is no longer published for removal, such as one deleted while the plugin was off.
 *
 * @param int[] $published Published Article IDs.
 * @return int Documents marked for removal.
 */
function remove_missing( array $published ): int {
	global $wpdb;

	$stored = (array) $wpdb->get_col(
		$wpdb->prepare(
			'SELECT object_id FROM %i WHERE object_type = %s AND status <> %s',
			table( 'documents' ),
			OBJECT_TYPE,
			STATUS_REMOVE
		)
	);

	$removed = 0;

	foreach ( array_diff( array_map( 'intval', $stored ), $published ) as $post_id ) {
		$removed += (int) mark_remove( $post_id );
	}

	return $removed;
}

/**
 * Sync the next $size published Articles after the ID cursor; the last batch also runs remove_missing().
 *
 * @param int $after Last Article ID already synced, 0 to start.
 * @param int $size  Articles per batch.
 * @return array{counts:array<string,int>, processed:int, after:int, done:bool, total:int}
 */
function queue_batch( int $after, int $size ): array {
	global $wpdb;

	// An ID cursor, not an offset: an Article published mid-way can't shift the batches.
	$ids = array_map(
		'intval',
		(array) $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish' AND ID > %d ORDER BY ID ASC LIMIT %d",
				OBJECT_TYPE,
				$after,
				$size
			)
		)
	);

	$results = [];

	foreach ( $ids as $post_id ) {
		$results[] = sync_post( $post_id );
	}

	$counts = Batch\tally( $results );
	$cursor = Batch\next_cursor( $ids, $after, $size );

	if ( $cursor['done'] ) {
		$counts['removed'] += remove_missing( published_article_ids() );
	}

	return [
		'counts'    => $counts,
		'processed' => count( $ids ),
		'after'     => $cursor['after'],
		'done'      => $cursor['done'],
		'total'     => (int) wp_count_posts( OBJECT_TYPE )->publish,
	];
}
