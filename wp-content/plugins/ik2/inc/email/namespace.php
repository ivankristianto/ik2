<?php
/**
 * Encode email addresses in post content, so Cloudflare's Email Address Obfuscation
 * injects no decode script: it carries no CSP nonce and is blocked under enforcement.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\Email;

defined( 'ABSPATH' ) || exit;

/**
 * Register hooks owned by this module.
 */
function bootstrap(): void {
	// After do_blocks (9) and the other core content filters, so dynamic blocks' output is covered too.
	add_filter( 'the_content', __NAMESPACE__ . '\\encode_content', 20 );
}

/**
 * Filter callback for the_content. antispambot() writes each address as a random mix of entities.
 *
 * @param string $content Rendered post content.
 */
function encode_content( $content ): string {
	return Rules\encode_addresses( (string) $content, 'antispambot' );
}
