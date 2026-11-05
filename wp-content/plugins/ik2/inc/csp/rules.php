<?php
/**
 * Pure helpers for the strict Content-Security-Policy. No WordPress, unit-tested.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\Csp\Rules;

const HEADER_ENFORCE     = 'Content-Security-Policy';
const HEADER_REPORT_ONLY = 'Content-Security-Policy-Report-Only';

/**
 * The CSP header name and value for one response.
 *
 * `https:` and `'unsafe-inline'` are fallbacks: browsers that understand
 * `'strict-dynamic'` ignore both, and nonce-aware ones ignore `'unsafe-inline'`.
 * `'wasm-unsafe-eval'` lets core's Interactivity router compile its module lexer; it allows no JS eval.
 *
 * @param string $nonce   Base64 nonce printed on every script tag of this response.
 * @param bool   $enforce False sends Report-Only.
 * @return array{name:string,value:string}
 * @throws \InvalidArgumentException When the nonce is not plain base64, which would let it break out of the header.
 */
function build_policy( string $nonce, bool $enforce ): array {
	if ( ! is_valid_nonce( $nonce ) ) {
		throw new \InvalidArgumentException( 'CSP nonce must be base64.' );
	}

	$directives = [
		"script-src 'nonce-{$nonce}' 'strict-dynamic' 'wasm-unsafe-eval' https: 'unsafe-inline'",
		"object-src 'none'",
		"base-uri 'none'",
		"frame-ancestors 'self'",
	];

	return [
		'name'  => $enforce ? HEADER_ENFORCE : HEADER_REPORT_ONLY,
		'value' => implode( '; ', $directives ),
	];
}

/**
 * True for a non-empty base64 string, the only shape CSP's nonce-source allows.
 *
 * @param string $nonce Candidate nonce.
 */
function is_valid_nonce( string $nonce ): bool {
	return 1 === preg_match( '#^[A-Za-z0-9+/]+={0,2}$#', $nonce );
}

/**
 * Script tag attributes with the nonce added.
 *
 * @param array<string,mixed> $attributes Attributes core is about to print.
 * @param string              $nonce      This response's nonce.
 * @return array<string,mixed>
 */
function with_nonce( array $attributes, string $nonce ): array {
	$attributes['nonce'] = $nonce;

	return $attributes;
}
