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
 * @param string $nonce   Base64 nonce printed on every script tag of this response.
 * @param bool   $enforce False sends Report-Only.
 * @return array{name:string,value:string}
 * @throws \InvalidArgumentException When the nonce could break out of the header.
 */
function build_policy( string $nonce, bool $enforce ): array {
	if ( ! is_valid_nonce( $nonce ) ) {
		throw new \InvalidArgumentException( 'CSP nonce must be base64.' );
	}

	// https: and 'unsafe-inline' only serve browsers without 'strict-dynamic'; core's router lexer needs 'wasm-unsafe-eval'.
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
