<?php
/**
 * Links posts to the entries of another post type through a hidden taxonomy that mirrors it.
 *
 * Each published entry owns one term (slug = entry slug, term meta = entry id). Editors tick
 * entries in the post sidebar; nobody can create or edit terms by hand, so the list never drifts.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\PostTypes\EntryLinks;

use WP_Post;
use WP_Term;

defined( 'ABSPATH' ) || exit;

const MAX_POSTS_PER_ENTRY = 50;

/**
 * Register a hidden taxonomy on posts: a sidebar checklist with no URL of its own.
 *
 * @param string               $taxonomy Taxonomy name.
 * @param array<string,string> $labels   Taxonomy labels.
 */
function register_hidden_taxonomy( string $taxonomy, array $labels ): void {
	register_taxonomy(
		$taxonomy,
		'post',
		[
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'rewrite'            => false,
			'query_var'          => false,
			'hierarchical'       => true,
			'show_ui'            => true,
			'show_in_menu'       => false,
			'show_in_nav_menus'  => false,
			'show_tagcloud'      => false,
			'show_in_quick_edit' => true,
			'show_admin_column'  => true,
			'show_in_rest'       => true,
			// Terms come from entries only, so the sidebar offers no "Add new" and stray terms can't appear.
			'capabilities'       => [
				'manage_terms' => 'do_not_allow',
				'edit_terms'   => 'do_not_allow',
				'delete_terms' => 'do_not_allow',
				'assign_terms' => 'edit_posts',
			],
		]
	);
}

/**
 * Create or update the term that mirrors a published entry. Drafts and trashed entries keep theirs.
 *
 * @param WP_Post                                                                   $entry Entry.
 * @param array{post_type:string,taxonomy:string,term_meta:string,post_meta:string} $link  Link definition.
 */
function sync_term( WP_Post $entry, array $link ): void {
	if ( 'publish' !== $entry->post_status || wp_is_post_revision( $entry->ID ) || '' === $entry->post_name ) {
		return;
	}

	$args    = [
		'name' => $entry->post_title,
		'slug' => $entry->post_name,
	];
	$term_id = (int) get_post_meta( $entry->ID, $link['post_meta'], true );
	$term    = $term_id > 0 ? get_term( $term_id, $link['taxonomy'] ) : null;

	$result = $term instanceof WP_Term
		? wp_update_term( $term->term_id, $link['taxonomy'], $args )
		: wp_insert_term( $entry->post_title, $link['taxonomy'], [ 'slug' => $entry->post_name ] );

	// A term left over from a lost link still owns the name; adopt it, unless another entry owns it.
	$new_id = is_wp_error( $result ) ? (int) $result->get_error_data( 'term_exists' ) : (int) $result['term_id'];
	$owner  = $new_id > 0 ? (int) get_term_meta( $new_id, $link['term_meta'], true ) : 0;

	if ( $new_id <= 0 || ( $owner > 0 && $owner !== $entry->ID ) ) {
		return;
	}

	update_term_meta( $new_id, $link['term_meta'], $entry->ID );
	update_post_meta( $entry->ID, $link['post_meta'], $new_id );
}

/**
 * Remove an entry's term when the entry is deleted for good.
 *
 * @param WP_Post|null                                                              $entry Post being deleted.
 * @param array{post_type:string,taxonomy:string,term_meta:string,post_meta:string} $link  Link definition.
 */
function delete_term( ?WP_Post $entry, array $link ): void {
	if ( ! $entry instanceof WP_Post || $link['post_type'] !== $entry->post_type ) {
		return;
	}

	$term_id = (int) get_post_meta( $entry->ID, $link['post_meta'], true );

	if ( $term_id > 0 ) {
		wp_delete_term( $term_id, $link['taxonomy'] );
	}
}

/**
 * Give every published entry its term.
 *
 * @param array{post_type:string,taxonomy:string,term_meta:string,post_meta:string} $link Link definition.
 */
function sync_all_terms( array $link ): void {
	$entries = get_posts(
		[
			'post_type'      => $link['post_type'],
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		]
	);

	foreach ( $entries as $entry ) {
		sync_term( $entry, $link );
	}
}

/**
 * Published entries a post is tagged with.
 *
 * @param int                                                                       $post_id Post id.
 * @param array{post_type:string,taxonomy:string,term_meta:string,post_meta:string} $link    Link definition.
 * @return array<int, WP_Post>
 */
function entries_for_post( int $post_id, array $link ): array {
	$terms = get_the_terms( $post_id, $link['taxonomy'] );

	if ( ! is_array( $terms ) ) {
		return [];
	}

	$entries = [];

	foreach ( $terms as $term ) {
		$entry = get_post( (int) get_term_meta( $term->term_id, $link['term_meta'], true ) );

		if ( $entry instanceof WP_Post && $link['post_type'] === $entry->post_type && 'publish' === $entry->post_status ) {
			$entries[] = $entry;
		}
	}

	return $entries;
}

/**
 * Published posts tagged with an entry, newest first.
 *
 * @param int                                                                       $entry_id Entry id.
 * @param array{post_type:string,taxonomy:string,term_meta:string,post_meta:string} $link     Link definition.
 * @return array<int, WP_Post>
 */
function posts_for_entry( int $entry_id, array $link ): array {
	$term_id = (int) get_post_meta( $entry_id, $link['post_meta'], true );

	if ( $term_id <= 0 ) {
		return [];
	}

	return get_posts(
		[
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => MAX_POSTS_PER_ENTRY,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				[
					'taxonomy'         => $link['taxonomy'],
					'terms'            => $term_id,
					'include_children' => false,
				],
			],
		]
	);
}
