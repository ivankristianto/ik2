<?php
/**
 * Pure helpers for encoding email addresses in rendered HTML. No WordPress, unit-tested.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\Email\Rules;

const ADDRESS = '[A-Za-z0-9._%+-]+@[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]+)*\.[A-Za-z]{2,}';

// Matched whole, in this order: a script, style or textarea element (kept as is, since encoding would change what scripts and styles read), a mailto link, or a bare address.
const PATTERN = '#(?<raw><(script|style|textarea)\b[^>]*>.*?</\2\s*>)|(?<mailto><a\b[^>]*\bhref\s*=\s*["\']?\s*mailto:[^>]*>.*?</a\s*>)|' . ADDRESS . '#is';

// Cloudflare decodes entities in an a tag's href and still rewrites mailto links, so they need its documented opt-out.
const OFF = '<!--email_off-->';
const ON  = '<!--/email_off-->';

/**
 * Run every email address in the HTML through $encode, and wrap mailto links in Cloudflare's email_off comments.
 *
 * @param string                  $html   Rendered HTML.
 * @param callable(string):string $encode Turns one address into its encoded form.
 */
function encode_addresses( string $html, callable $encode ): string {
	if ( ! str_contains( $html, '@' ) ) {
		return $html;
	}

	$out = preg_replace_callback(
		PATTERN,
		static fn( array $m ): string => replace_match( $m, $encode ),
		$html
	);

	// On a PCRE failure, unencoded content beats losing the content.
	return $out ?? $html;
}

/**
 * Replacement for one PATTERN match.
 *
 * @param array<int|string,string> $m      Match groups.
 * @param callable(string):string  $encode Turns one address into its encoded form.
 */
function replace_match( array $m, callable $encode ): string {
	if ( '' !== ( $m['raw'] ?? '' ) ) {
		return $m[0];
	}

	if ( '' === ( $m['mailto'] ?? '' ) ) {
		return $encode( $m[0] );
	}

	$link = preg_replace_callback( '#' . ADDRESS . '#', static fn( array $a ): string => $encode( $a[0] ), $m[0] );

	return OFF . ( $link ?? $m[0] ) . ON;
}
