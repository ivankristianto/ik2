<?php
/**
 * Corpus module: table upgrades and the hooks that keep Articles queued.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\Corpus;

defined( 'ABSPATH' ) || exit;

/**
 * Register hooks owned by this module.
 */
function bootstrap(): void {
	add_action( 'plugins_loaded', __NAMESPACE__ . '\\maybe_upgrade' );
	add_action( 'wp_after_insert_post', __NAMESPACE__ . '\\on_after_insert_post', 10, 2 );
	add_action( 'transition_post_status', __NAMESPACE__ . '\\on_transition_post_status', 10, 3 );
	add_action( 'deleted_post', __NAMESPACE__ . '\\on_deleted_post', 10, 2 );
	add_action( 'added_post_meta', __NAMESPACE__ . '\\on_post_meta_change', 10, 3 );
	add_action( 'updated_post_meta', __NAMESPACE__ . '\\on_post_meta_change', 10, 3 );
	add_action( 'deleted_post_meta', __NAMESPACE__ . '\\on_post_meta_change', 10, 3 );
}

/**
 * Sync an Article once per save, after its terms and meta are stored.
 *
 * @param int      $post_id Post ID.
 * @param \WP_Post $post    Saved post.
 */
function on_after_insert_post( int $post_id, \WP_Post $post ): void {
	if ( OBJECT_TYPE !== $post->post_type || wp_is_post_autosave( $post ) || wp_is_post_revision( $post ) ) {
		return;
	}

	sync( $post_id );
}

/**
 * Mark an Article for removal as soon as it leaves publish.
 *
 * @param string   $new_status New status.
 * @param string   $old_status Previous status.
 * @param \WP_Post $post       Post.
 */
function on_transition_post_status( string $new_status, string $old_status, \WP_Post $post ): void {
	if ( 'publish' === $old_status && 'publish' !== $new_status && OBJECT_TYPE === $post->post_type ) {
		mark_remove( $post->ID );
	}
}

/**
 * Mark a deleted post for removal by ID: the object cache can still hold it
 * as published at this point, so sync() would queue it again.
 *
 * @param int      $post_id Deleted post ID.
 * @param \WP_Post $post    Deleted post, passed so revisions and attachments skip the query.
 */
function on_deleted_post( int $post_id, \WP_Post $post ): void {
	if ( OBJECT_TYPE !== $post->post_type ) {
		return;
	}

	mark_remove( $post_id );
}

/**
 * Re-check inclusion when the noindex or exclude flag changes.
 *
 * @param int|int[] $meta_id   Meta ID, or IDs for deleted_post_meta.
 * @param int       $object_id Post ID.
 * @param string    $meta_key  Meta key.
 */
function on_post_meta_change( $meta_id, int $object_id, string $meta_key ): void {
	if ( NOINDEX_META !== $meta_key && EXCLUDE_META !== $meta_key ) {
		return;
	}

	if ( OBJECT_TYPE !== get_post_type( $object_id ) ) {
		return;
	}

	sync( $object_id );
}
