<?php
/**
 * Read-only queries over the Corpus tables, shared by `wp ik2 corpus status` and Tools → Corpus.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\Corpus;

defined( 'ABSPATH' ) || exit;

/**
 * Document counts by status.
 *
 * @return list<array{status:string, documents:string}>
 */
function status_counts(): array {
	global $wpdb;

	// Missing tables give null, so a failed dbDelta shows as empty instead of fatal.
	return (array) $wpdb->get_results(
		$wpdb->prepare( 'SELECT status, COUNT(*) AS documents FROM %i GROUP BY status ORDER BY status', table( 'documents' ) ),
		ARRAY_A
	);
}

/**
 * Parked Documents with their last error.
 *
 * @return list<array{post_id:string, title:string, attempts:string, last_error:?string}>
 */
function parked_documents(): array {
	global $wpdb;

	return (array) $wpdb->get_results(
		$wpdb->prepare(
			'SELECT object_id AS post_id, title, attempts, last_error FROM %i WHERE status = %s ORDER BY object_id',
			table( 'documents' ),
			STATUS_PARKED
		),
		ARRAY_A
	);
}

/**
 * Row counts for Chunks, both vector levels and related lists.
 *
 * @return array<string, int>
 */
function table_counts(): array {
	global $wpdb;

	return [
		'chunks'           => (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', table( 'chunks' ) ) ),
		'document vectors' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE level = 'document'", table( 'vectors' ) ) ),
		'chunk vectors'    => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE level = 'chunk'", table( 'vectors' ) ) ),
		'related rows'     => (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', table( 'related' ) ) ),
	];
}

/**
 * The newest Runs, newest first. Times are UTC.
 *
 * @param int $limit How many.
 * @return list<array<string, string>>
 */
function recent_runs( int $limit ): array {
	global $wpdb;

	return (array) $wpdb->get_results(
		$wpdb->prepare( 'SELECT * FROM %i ORDER BY id DESC LIMIT %d', table( 'runs' ), $limit ),
		ARRAY_A
	);
}
