<?php
/**
 * Write Yoast's llms.txt where nginx can serve it.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\LlmsTxt;

defined( 'ABSPATH' ) || exit;

/**
 * Register hooks owned by this module.
 */
function bootstrap(): void {
	add_filter( 'wpseo_llmstxt_filesystem_path', __NAMESPACE__ . '\\uploads_path' );
}

/**
 * Point Yoast at the uploads volume, the only directory the app and nginx containers share.
 *
 * Yoast's default is the webroot, which in production lives inside the app image
 * and is invisible to the nginx image, so /llms.txt 404s. nginx aliases
 * /llms.txt to this file (docker/nginx/default.conf).
 *
 * @param string $path Directory Yoast would write to.
 */
function uploads_path( $path ): string {
	$uploads = wp_upload_dir( null, false );

	return empty( $uploads['basedir'] ) ? (string) $path : (string) $uploads['basedir'];
}
