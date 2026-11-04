<?php
/**
 * Expose Yoast's SEO title, description, keyphrase and social image on pages over REST.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\YoastPageMeta;

defined( 'ABSPATH' ) || exit;

// Yoast exposes the first three on posts only, and the social image on nothing.
const KEYS = [
	'_yoast_wpseo_focuskw',
	'_yoast_wpseo_title',
	'_yoast_wpseo_metadesc',
	'_yoast_wpseo_opengraph-image',
	'_yoast_wpseo_opengraph-image-id',
];

/**
 * Register hooks owned by this module.
 */
function bootstrap(): void {
	add_action( 'init', __NAMESPACE__ . '\\register_meta_fields', 20 );
}

/**
 * Register the keys for the page subtype only; Yoast's own registration keeps covering posts.
 */
function register_meta_fields(): void {
	if ( ! class_exists( \WPSEO_Meta::class ) ) {
		return;
	}

	foreach ( KEYS as $key ) {
		register_post_meta(
			'page',
			$key,
			[
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => [ \WPSEO_Meta::class, 'sanitize_post_meta' ],
				'auth_callback'     => __NAMESPACE__ . '\\can_edit_page',
			]
		);
	}
}

/**
 * Allow the write only to users who can edit that page.
 *
 * @param bool   $allowed   Whether the user can add the meta.
 * @param string $meta_key  Meta key.
 * @param int    $object_id Page ID.
 */
function can_edit_page( $allowed, $meta_key, $object_id ): bool {
	return current_user_can( 'edit_post', (int) $object_id );
}
