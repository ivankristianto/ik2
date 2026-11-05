<?php
/**
 * Pure helpers for encoding email addresses in rendered HTML. No WordPress, unit-tested.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\Email\Rules;

// A script, style or textarea element is matched whole and kept as is: encoding inside it would change what scripts and styles read.
const PATTERN = '#(?<raw><(script|style|textarea)\b[^>]*>.*?</\2\s*>)|[A-Za-z0-9._%+-]+@[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]+)*\.[A-Za-z]{2,}#is';

/**
 * Run every email address in the HTML through $encode, leaving script, style and textarea contents alone.
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
		static fn( array $m ): string => '' !== ( $m['raw'] ?? '' ) ? $m[0] : $encode( $m[0] ),
		$html
	);

	// On a PCRE failure, unencoded content beats losing the content.
	return $out ?? $html;
}
