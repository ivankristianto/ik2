<?php
/**
 * Links posts to the projects they write about, through the hidden `ik2_project` taxonomy (see entry-links.php).
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\PostTypes\Project;

use IK2\Plugin\PostTypes\EntryLinks;
use WP_Post;

defined( 'ABSPATH' ) || exit;

const TAXONOMY = 'ik2_project';

const TERM_PROJECT_ID_META = 'project_id';

const PROJECT_TERM_ID_META = '_ik2_project_term';

const LINK = [
	'post_type' => POST_TYPE,
	'taxonomy'  => TAXONOMY,
	'term_meta' => TERM_PROJECT_ID_META,
	'post_meta' => PROJECT_TERM_ID_META,
];

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
 * Register the hidden `ik2_project` taxonomy on posts.
 */
function register_project_taxonomy(): void {
	EntryLinks\register_hidden_taxonomy(
		TAXONOMY,
		[
			'name'          => _x( 'Projects', 'taxonomy general name', 'ik2' ),
			'singular_name' => _x( 'Project', 'taxonomy singular name', 'ik2' ),
			'menu_name'     => __( 'Projects', 'ik2' ),
			'search_items'  => __( 'Search projects', 'ik2' ),
			'all_items'     => __( 'All projects', 'ik2' ),
			'not_found'     => __( 'No projects yet.', 'ik2' ),
		]
	);
}

/**
 * Create or update the term that mirrors a published project.
 *
 * @param int     $post_id Project id.
 * @param WP_Post $post    Project.
 */
function sync_term( int $post_id, WP_Post $post ): void {
	EntryLinks\sync_term( $post, LINK );
}

/**
 * Remove a project's term when the project is deleted for good.
 *
 * @param int          $post_id Post id.
 * @param WP_Post|null $post    Post being deleted.
 */
function delete_project_term( int $post_id, ?WP_Post $post = null ): void {
	EntryLinks\delete_term( $post, LINK );
}

/**
 * After a deploy that changes the schema: give every published project its term, let Yoast index projects, and flush rewrites.
 */
function maybe_upgrade(): void {
	if ( (int) get_option( 'ik2_project_schema_version', 0 ) >= SCHEMA_VERSION ) {
		return;
	}

	EntryLinks\sync_all_terms( LINK );

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
	return EntryLinks\entries_for_post( $post_id, LINK );
}

/**
 * Published posts tagged with a project, newest first.
 *
 * @param int $project_id Project id.
 * @return array<int, WP_Post>
 */
function posts_for_project( int $project_id ): array {
	return EntryLinks\posts_for_entry( $project_id, LINK );
}
