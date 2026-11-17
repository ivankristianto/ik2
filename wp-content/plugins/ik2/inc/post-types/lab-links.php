<?php
/**
 * Links posts to the lab entries they write about, through the hidden `ik2_lab` taxonomy (see entry-links.php).
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\PostTypes\Lab;

use IK2\Plugin\PostTypes\EntryLinks;
use WP_Post;

defined( 'ABSPATH' ) || exit;

const TAXONOMY = 'ik2_lab';

const LINK = [
	'post_type' => POST_TYPE,
	'taxonomy'  => TAXONOMY,
	'term_meta' => 'lab_id',
	'post_meta' => '_ik2_lab_term',
];

// Bump to rerun maybe_upgrade() on the next request after a deploy.
const SCHEMA_VERSION = 1;

/**
 * Register hooks owned by the post-to-lab links.
 */
function bootstrap_links(): void {
	add_action( 'init', __NAMESPACE__ . '\\register_lab_taxonomy', 11 );
	add_action( 'init', __NAMESPACE__ . '\\maybe_upgrade', 20 );
	add_action( 'save_post_' . POST_TYPE, __NAMESPACE__ . '\\sync_term', 10, 2 );
	add_action( 'before_delete_post', __NAMESPACE__ . '\\delete_lab_term', 10, 2 );
}

/**
 * Register the hidden `ik2_lab` taxonomy on posts.
 */
function register_lab_taxonomy(): void {
	EntryLinks\register_hidden_taxonomy(
		TAXONOMY,
		[
			'name'          => _x( 'Lab', 'taxonomy general name', 'ik2' ),
			'singular_name' => _x( 'Lab entry', 'taxonomy singular name', 'ik2' ),
			'menu_name'     => __( 'Lab', 'ik2' ),
			'search_items'  => __( 'Search lab entries', 'ik2' ),
			'all_items'     => __( 'All lab entries', 'ik2' ),
			'not_found'     => __( 'No lab entries yet.', 'ik2' ),
		]
	);
}

/**
 * Create or update the term that mirrors a published lab entry.
 *
 * @param int     $post_id Lab entry id.
 * @param WP_Post $post    Lab entry.
 */
function sync_term( int $post_id, WP_Post $post ): void {
	EntryLinks\sync_term( $post, LINK );
}

/**
 * Remove a lab entry's term when the entry is deleted for good.
 *
 * @param int          $post_id Post id.
 * @param WP_Post|null $post    Post being deleted.
 */
function delete_lab_term( int $post_id, ?WP_Post $post = null ): void {
	EntryLinks\delete_term( $post, LINK );
}

/**
 * First request after the deploy that adds the post type: make /lab/<slug>/ resolve and keep Yoast from hiding it.
 */
function maybe_upgrade(): void {
	if ( (int) get_option( 'ik2_lab_schema_version', 0 ) >= SCHEMA_VERSION ) {
		return;
	}

	EntryLinks\sync_all_terms( LINK );

	if ( class_exists( 'WPSEO_Options' ) && \WPSEO_Options::get( 'noindex-' . POST_TYPE, false ) ) {
		\WPSEO_Options::set( 'noindex-' . POST_TYPE, false );
	}

	flush_rewrite_rules( false );
	update_option( 'ik2_lab_schema_version', SCHEMA_VERSION );
}

/**
 * Published lab entries a post is tagged with.
 *
 * @param int $post_id Post id.
 * @return array<int, WP_Post>
 */
function labs_for_post( int $post_id ): array {
	return EntryLinks\entries_for_post( $post_id, LINK );
}

/**
 * Published posts tagged with a lab entry, newest first.
 *
 * @param int $lab_id Lab entry id.
 * @return array<int, WP_Post>
 */
function posts_for_lab( int $lab_id ): array {
	return EntryLinks\posts_for_entry( $lab_id, LINK );
}
