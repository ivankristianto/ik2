<?php
/**
 * Links posts to the projects they write about, through a hidden taxonomy that mirrors the Project CPT.
 *
 * Each published project owns one term (slug = project slug, term meta `project_id`). Editors tick
 * projects in the post sidebar; nobody can create or edit terms by hand, so the list never drifts.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\PostTypes\Project;

use WP_Post;
use WP_Term;

defined( 'ABSPATH' ) || exit;

const TAXONOMY = 'ik2_project';

const TERM_PROJECT_ID_META = 'project_id';

const PROJECT_TERM_ID_META = '_ik2_project_term';

const MAX_POSTS_PER_PROJECT = 50;

// Bump to rerun maybe_upgrade() on the next request after a deploy.
const SCHEMA_VERSION = 2;

/**
 * Register hooks owned by the post-to-project links.
 */
function bootstrap_links(): void {
	add_action( 'init', __NAMESPACE__ . '\\register_project_taxonomy', 11 );
	add_action( 'init', __NAMESPACE__ . '\\maybe_upgrade', 20 );
	add_action( 'save_post_' . POST_TYPE, __NAMESPACE__ . '\\sync_term', 10, 2 );
	add_action( 'before_delete_post', __NAMESPACE__ . '\\delete_project_term', 10, 2 );
}

/**
 * Register the hidden `ik2_project` taxonomy on posts: a sidebar checklist with no URL of its own.
 */
function register_project_taxonomy(): void {
	register_taxonomy(
		TAXONOMY,
		'post',
		[
			'labels'             => [
				'name'          => _x( 'Projects', 'taxonomy general name', 'ik2' ),
				'singular_name' => _x( 'Project', 'taxonomy singular name', 'ik2' ),
				'menu_name'     => __( 'Projects', 'ik2' ),
				'search_items'  => __( 'Search projects', 'ik2' ),
				'all_items'     => __( 'All projects', 'ik2' ),
				'not_found'     => __( 'No projects yet.', 'ik2' ),
			],
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
			// Terms come from projects only, so the sidebar offers no "Add new" and stray terms can't appear.
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
 * Create or update the term that mirrors a published project. Drafts and trashed projects keep theirs.
 *
 * @param int     $post_id Project id.
 * @param WP_Post $post    Project.
 */
function sync_term( int $post_id, WP_Post $post ): void {
	if ( 'publish' !== $post->post_status || wp_is_post_revision( $post_id ) || '' === $post->post_name ) {
		return;
	}

	$args    = [
		'name' => $post->post_title,
		'slug' => $post->post_name,
	];
	$term_id = (int) get_post_meta( $post_id, PROJECT_TERM_ID_META, true );
	$term    = $term_id > 0 ? get_term( $term_id, TAXONOMY ) : null;

	$result = $term instanceof WP_Term
		? wp_update_term( $term->term_id, TAXONOMY, $args )
		: wp_insert_term( $post->post_title, TAXONOMY, [ 'slug' => $post->post_name ] );

	// A term left over from a lost link still owns the name; adopt it, unless another project owns it.
	$new_id = is_wp_error( $result ) ? (int) $result->get_error_data( 'term_exists' ) : (int) $result['term_id'];
	$owner  = $new_id > 0 ? (int) get_term_meta( $new_id, TERM_PROJECT_ID_META, true ) : 0;

	if ( $new_id <= 0 || ( $owner > 0 && $owner !== $post_id ) ) {
		return;
	}

	update_term_meta( $new_id, TERM_PROJECT_ID_META, $post_id );
	update_post_meta( $post_id, PROJECT_TERM_ID_META, $new_id );
}

/**
 * Remove a project's term when the project is deleted for good.
 *
 * @param int          $post_id Post id.
 * @param WP_Post|null $post    Post being deleted.
 */
function delete_project_term( int $post_id, ?WP_Post $post = null ): void {
	if ( ! $post instanceof WP_Post || POST_TYPE !== $post->post_type ) {
		return;
	}

	$term_id = (int) get_post_meta( $post_id, PROJECT_TERM_ID_META, true );

	if ( $term_id > 0 ) {
		wp_delete_term( $term_id, TAXONOMY );
	}
}

/**
 * After a deploy that changes the schema: give every published project its term, let Yoast index projects, and flush rewrites.
 */
function maybe_upgrade(): void {
	if ( (int) get_option( 'ik2_project_schema_version', 0 ) >= SCHEMA_VERSION ) {
		return;
	}

	$projects = get_posts(
		[
			'post_type'      => POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		]
	);

	foreach ( $projects as $project ) {
		sync_term( $project->ID, $project );
	}

	// Yoast kept "hide from search" for projects from when they had no pages; ADR 0004 indexes them.
	if ( class_exists( 'WPSEO_Options' ) && \WPSEO_Options::get( 'noindex-' . POST_TYPE, false ) ) {
		\WPSEO_Options::set( 'noindex-' . POST_TYPE, false );
	}

	flush_rewrite_rules( false );
	update_option( 'ik2_project_schema_version', SCHEMA_VERSION );
}

/**
 * Published projects a post is tagged with.
 *
 * @param int $post_id Post id.
 * @return array<int, WP_Post>
 */
function projects_for_post( int $post_id ): array {
	$terms = get_the_terms( $post_id, TAXONOMY );

	if ( ! is_array( $terms ) ) {
		return [];
	}

	$projects = [];

	foreach ( $terms as $term ) {
		$project = get_post( (int) get_term_meta( $term->term_id, TERM_PROJECT_ID_META, true ) );

		if ( $project instanceof WP_Post && POST_TYPE === $project->post_type && 'publish' === $project->post_status ) {
			$projects[] = $project;
		}
	}

	return $projects;
}

/**
 * Published posts tagged with a project, newest first.
 *
 * @param int $project_id Project id.
 * @return array<int, WP_Post>
 */
function posts_for_project( int $project_id ): array {
	$term_id = (int) get_post_meta( $project_id, PROJECT_TERM_ID_META, true );

	if ( $term_id <= 0 ) {
		return [];
	}

	return get_posts(
		[
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => MAX_POSTS_PER_PROJECT,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				[
					'taxonomy'         => TAXONOMY,
					'terms'            => $term_id,
					'include_children' => false,
				],
			],
		]
	);
}
