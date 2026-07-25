<?php
/**
 * Project custom post type.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\PostTypes\Project;

defined( 'ABSPATH' ) || exit;

const POST_TYPE = 'project';

/**
 * Register hooks owned by this module.
 */
function bootstrap(): void {
	add_action( 'init', __NAMESPACE__ . '\\register' );
	add_action( 'init', __NAMESPACE__ . '\\register_meta_fields' );
}

/**
 * Register the `project` post type.
 *
 * Projects have no front-end view of their own: they are surfaced entirely
 * through `ik2/project-card` on the Projects page and the home preview. The
 * type is therefore registered non-public with no rewrite — there is no
 * `/project/{slug}/` permalink to 404 on or to leak into sitemaps and search.
 * Admin UI and REST stay on so the type is still editable in the block editor.
 */
function register(): void {
	register_post_type(
		POST_TYPE,
		[
			'labels'              => [
				'name'                  => _x( 'Projects', 'post type general name', 'ik2' ),
				'singular_name'         => _x( 'Project', 'post type singular name', 'ik2' ),
				'menu_name'             => _x( 'Projects', 'admin menu', 'ik2' ),
				'name_admin_bar'        => _x( 'Project', 'add new on admin bar', 'ik2' ),
				'add_new'               => __( 'Add New', 'ik2' ),
				'add_new_item'          => __( 'Add New Project', 'ik2' ),
				'new_item'              => __( 'New Project', 'ik2' ),
				'edit_item'             => __( 'Edit Project', 'ik2' ),
				'view_item'             => __( 'View Project', 'ik2' ),
				'view_items'            => __( 'View Projects', 'ik2' ),
				'all_items'             => __( 'All Projects', 'ik2' ),
				'search_items'          => __( 'Search Projects', 'ik2' ),
				'not_found'             => __( 'No projects found.', 'ik2' ),
				'not_found_in_trash'    => __( 'No projects found in Trash.', 'ik2' ),
				'archives'              => __( 'Project Archives', 'ik2' ),
				'filter_items_list'     => __( 'Filter projects list', 'ik2' ),
				'items_list_navigation' => __( 'Projects list navigation', 'ik2' ),
				'items_list'            => __( 'Projects list', 'ik2' ),
			],
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => true,
			'menu_position'       => 20,
			'menu_icon'           => 'dashicons-portfolio',
			'has_archive'         => false,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'exclude_from_search' => true,
			'supports'            => [
				'title',
				'editor',
				'excerpt',
				'thumbnail',
				'revisions',
				'author',
				'custom-fields',
			],
			'template'            => [
				[
					'core/pattern',
					[
						'slug' => 'ik2/project-post-structure',
					],
				],
			],
		]
	);
}

/**
 * Register custom meta fields for the Project CPT.
 *
 * - `status`  : one of "Active" | "Experiment" | "Archived".
 * - `tech`    : pipe-separated list of tech tags, e.g. "Node.js|Cloudflare API".
 * - `links`   : pipe-separated list of "Label::URL" pairs, e.g. "GitHub::https://…|Write-up::…".
 * - `learned` : short paragraph summarising what the project taught.
 */
function register_meta_fields(): void {
	register_post_meta(
		POST_TYPE,
		'status',
		[
			'type'              => 'string',
			'single'            => true,
			'default'           => 'Active',
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => __NAMESPACE__ . '\\can_edit_posts',
		]
	);

	register_post_meta(
		POST_TYPE,
		'tech',
		[
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => __NAMESPACE__ . '\\can_edit_posts',
		]
	);

	register_post_meta(
		POST_TYPE,
		'links',
		[
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => true,
			'sanitize_callback' => __NAMESPACE__ . '\\sanitize_string_trim',
			'auth_callback'     => __NAMESPACE__ . '\\can_edit_posts',
		]
	);

	register_post_meta(
		POST_TYPE,
		'learned',
		[
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_textarea_field',
			'auth_callback'     => __NAMESPACE__ . '\\can_edit_posts',
		]
	);
}

/**
 * Auth callback for Project meta fields. Restricted to users who can edit posts.
 */
function can_edit_posts(): bool {
	return current_user_can( 'edit_posts' );
}

/**
 * Sanitize a meta value to a trimmed string, or empty string if not a string.
 *
 * @param mixed $value Raw meta value.
 */
function sanitize_string_trim( $value ): string {
	return is_string( $value ) ? trim( $value ) : '';
}
