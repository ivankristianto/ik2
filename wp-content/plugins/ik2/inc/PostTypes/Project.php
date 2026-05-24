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

add_action( 'init', __NAMESPACE__ . '\\register' );
add_action( 'init', __NAMESPACE__ . '\\register_meta_fields' );

/**
 * Register the `project` post type.
 *
 * Public, archive-enabled, REST-exposed, with block-template support so
 * the active theme can drive single + archive views via block templates.
 */
function register(): void {
	register_post_type(
		POST_TYPE,
		array(
			'labels'              => array(
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
			),
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => true,
			'menu_position'       => 20,
			'menu_icon'           => 'dashicons-portfolio',
			'has_archive'         => false,
			'rewrite'             => array(
				'slug'       => 'project',
				'with_front' => false,
			),
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'exclude_from_search' => false,
			'supports'            => array(
				'title',
				'editor',
				'excerpt',
				'thumbnail',
				'revisions',
				'author',
				'custom-fields',
			),
			'template'            => array(
				array(
					'core/pattern',
					array(
						'slug' => 'ik2/project-post-structure',
					),
				),
			),
		)
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
		array(
			'type'              => 'string',
			'single'            => true,
			'default'           => 'Active',
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => __NAMESPACE__ . '\\can_edit_posts',
		)
	);

	register_post_meta(
		POST_TYPE,
		'tech',
		array(
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => __NAMESPACE__ . '\\can_edit_posts',
		)
	);

	register_post_meta(
		POST_TYPE,
		'links',
		array(
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => true,
			'sanitize_callback' => __NAMESPACE__ . '\\sanitize_string_trim',
			'auth_callback'     => __NAMESPACE__ . '\\can_edit_posts',
		)
	);

	register_post_meta(
		POST_TYPE,
		'learned',
		array(
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_textarea_field',
			'auth_callback'     => __NAMESPACE__ . '\\can_edit_posts',
		)
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
