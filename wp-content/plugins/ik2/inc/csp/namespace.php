<?php
/**
 * Strict Content-Security-Policy for front-end HTML: a per-render nonce on every
 * script WordPress prints, and a header that blocks scripts without it.
 * Design: docs/specs/2026-11-05-strict-csp-design.md.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\Csp;

defined( 'ABSPATH' ) || exit;

// Report-Only for one release, then flip to true to enforce (spec, Rollout).
const ENFORCE = false;

/**
 * Register hooks owned by this module.
 */
function bootstrap(): void {
	// Priority 0 so the header goes out before any other handler can redirect or exit.
	add_action( 'template_redirect', __NAMESPACE__ . '\\send_header', 0 );
}

/**
 * Send the policy header and start stamping the nonce, for in-scope requests only.
 */
function send_header(): void {
	if ( ! is_in_scope() || headers_sent() ) {
		return;
	}

	$policy = Rules\build_policy( nonce(), ENFORCE );
	header( $policy['name'] . ': ' . $policy['value'] );

	// Hooked here, not in bootstrap(), so out-of-scope responses never carry a nonce the header doesn't list.
	add_filter( 'wp_script_attributes', __NAMESPACE__ . '\\add_nonce' );
	add_filter( 'wp_inline_script_attributes', __NAMESPACE__ . '\\add_nonce' );
}

/**
 * True for front-end HTML pages. wp-admin, wp-login.php and REST never reach
 * template_redirect, and Yoast's sitemaps exit earlier, in pre_get_posts.
 */
function is_in_scope(): bool {
	return ! ( is_robots() || is_favicon() || is_feed() || is_trackback() || is_customize_preview() );
}

/**
 * This request's nonce. Kept in memory only: caching it anywhere but APO's copy
 * of the whole response would hand the same nonce to other renders.
 */
function nonce(): string {
	static $nonce = null;

	$nonce ??= base64_encode( random_bytes( 16 ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- CSP nonces are base64 by spec.

	return $nonce;
}

/**
 * Filter callback for both script attribute filters.
 *
 * @param array<string,mixed> $attributes Attributes core is about to print.
 * @return array<string,mixed>
 */
function add_nonce( array $attributes ): array {
	return Rules\with_nonce( $attributes, nonce() );
}
