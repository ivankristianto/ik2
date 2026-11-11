<?php
/**
 * Lab custom post type: a page per tool or experiment. The tools themselves run on a separate instance.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\PostTypes\Lab;

use function IK2\Plugin\CodeHighlight\register_built_script;

defined( 'ABSPATH' ) || exit;

const POST_TYPE = 'lab';

const TOOL_URL_META   = 'lab_tool_url';
const SOURCE_URL_META = 'lab_source_url';
const MIN_HEIGHT_META = 'lab_min_height';

const VIEW_HANDLE = 'ik2-lab-tool';

/**
 * Register hooks owned by this module.
 */
function bootstrap(): void {
	add_action( 'init', __NAMESPACE__ . '\\register' );
	add_action( 'init', __NAMESPACE__ . '\\register_meta_fields' );
	add_action( 'init', __NAMESPACE__ . '\\register_view_script' );
	bootstrap_links();
}

/**
 * Register the `lab` post type, public at /lab/<slug>/.
 */
function register(): void {
	register_post_type(
		POST_TYPE,
		[
			'labels'              => [
				'name'                  => _x( 'Lab', 'post type general name', 'ik2' ),
				'singular_name'         => _x( 'Lab entry', 'post type singular name', 'ik2' ),
				'menu_name'             => _x( 'Lab', 'admin menu', 'ik2' ),
				'name_admin_bar'        => _x( 'Lab entry', 'add new on admin bar', 'ik2' ),
				'add_new'               => __( 'Add New', 'ik2' ),
				'add_new_item'          => __( 'Add New Lab Entry', 'ik2' ),
				'new_item'              => __( 'New Lab Entry', 'ik2' ),
				'edit_item'             => __( 'Edit Lab Entry', 'ik2' ),
				'view_item'             => __( 'View Lab Entry', 'ik2' ),
				'view_items'            => __( 'View Lab', 'ik2' ),
				'all_items'             => __( 'All Lab Entries', 'ik2' ),
				'search_items'          => __( 'Search Lab', 'ik2' ),
				'not_found'             => __( 'No lab entries found.', 'ik2' ),
				'not_found_in_trash'    => __( 'No lab entries found in Trash.', 'ik2' ),
				'archives'              => __( 'Lab Archives', 'ik2' ),
				'filter_items_list'     => __( 'Filter lab entries list', 'ik2' ),
				'items_list_navigation' => __( 'Lab entries list navigation', 'ik2' ),
				'items_list'            => __( 'Lab entries list', 'ik2' ),
			],
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => true,
			'menu_position'       => 21,
			'menu_icon'           => 'dashicons-hammer',
			// The index is the `lab` page (page-lab.html), the same arrangement as /projects/.
			'has_archive'         => false,
			'rewrite'             => [
				'slug'       => 'lab',
				'with_front' => false,
			],
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'exclude_from_search' => true,
			'show_in_nav_menus'   => false,
			'supports'            => [
				'title',
				'editor',
				'excerpt',
				'thumbnail',
				'revisions',
				'author',
				'custom-fields',
			],
		]
	);
}

/**
 * Register the entry's meta: the tool URL on the lab origin, a link to its source, and the frame's starting height.
 */
function register_meta_fields(): void {
	foreach ( [ TOOL_URL_META, SOURCE_URL_META ] as $key ) {
		register_post_meta(
			POST_TYPE,
			$key,
			[
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => 'esc_url_raw',
				'auth_callback'     => __NAMESPACE__ . '\\can_edit_posts',
			]
		);
	}

	register_post_meta(
		POST_TYPE,
		MIN_HEIGHT_META,
		[
			'type'              => 'integer',
			'single'            => true,
			'default'           => 0,
			'show_in_rest'      => true,
			'sanitize_callback' => 'absint',
			'auth_callback'     => __NAMESPACE__ . '\\can_edit_posts',
		]
	);
}

/**
 * Register the resize listener; the lab-tool block enqueues it only when it prints a frame.
 */
function register_view_script(): void {
	register_built_script( VIEW_HANDLE, 'lab-tool', [ 'strategy' => 'defer' ] );
}

/**
 * Auth callback for lab meta fields.
 */
function can_edit_posts(): bool {
	return current_user_can( 'edit_posts' );
}

/**
 * The origin tools are framed from. `IK2_LAB_ORIGIN` (constant or env var) overrides it for local work.
 */
function lab_origin(): string {
	if ( defined( 'IK2_LAB_ORIGIN' ) && is_string( IK2_LAB_ORIGIN ) ) {
		return IK2_LAB_ORIGIN;
	}

	$from_env = getenv( 'IK2_LAB_ORIGIN' );

	return is_string( $from_env ) && '' !== $from_env ? $from_env : Rules\DEFAULT_ORIGIN;
}

/**
 * What the entry page needs to show a tool. `url` is empty when unset or not on the lab origin.
 *
 * @param int $lab_id Lab entry id.
 * @return array{url:string, source:string, min_height:int}
 */
function get_tool_data( int $lab_id ): array {
	$url = (string) get_post_meta( $lab_id, TOOL_URL_META, true );

	return [
		'url'        => Rules\is_lab_url( $url, lab_origin() ) ? $url : '',
		'source'     => (string) get_post_meta( $lab_id, SOURCE_URL_META, true ),
		'min_height' => Rules\clamp_height( (int) get_post_meta( $lab_id, MIN_HEIGHT_META, true ) ),
	];
}
