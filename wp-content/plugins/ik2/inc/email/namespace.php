<?php
/**
 * Encode email addresses in post content with antispambot(), so Cloudflare's
 * Email Address Obfuscation finds nothing to rewrite and never injects its
 * decode script, which carries no CSP nonce and is blocked under enforcement.
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
 * Filter callback for the_content.
 *
 * @param string $content Rendered post content.
 */
function encode_content( $content ): string {
	return Rules\encode_addresses( (string) $content, __NAMESPACE__ . '\\encode_address' );
}

/**
 * One address as a random mix of decimal and hex entities. The browser shows it unchanged.
 *
 * @param string $address Plain email address.
 */
function encode_address( string $address ): string {
	return antispambot( $address );
}
