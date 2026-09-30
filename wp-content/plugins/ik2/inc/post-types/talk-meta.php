<?php
/**
 * Talk meta on posts: `venue` and `kind`, read by the theme's speaking-archive block.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\PostTypes\TalkMeta;

defined( 'ABSPATH' ) || exit;

/**
 * Register hooks owned by this module.
 */
function bootstrap(): void {
	add_action( 'init', __NAMESPACE__ . '\\register_meta_fields' );
}

/**
 * Register `venue` (e.g. "Kelas Tanya DomaiNesia · Online webinar") and `kind` (e.g. "Webinar") for REST.
 */
function register_meta_fields(): void {
	foreach ( [ 'venue', 'kind' ] as $key ) {
		register_post_meta(
			'post',
			$key,
			[
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => __NAMESPACE__ . '\\can_edit_posts',
			]
		);
	}
}

/**
 * Auth callback for talk meta. Restricted to users who can edit posts.
 */
function can_edit_posts(): bool {
	return current_user_can( 'edit_posts' );
}
