<?php
/**
 * Pure helpers for encoding email addresses in rendered HTML. No WordPress, unit-tested.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\Email\Rules;

const ADDRESS = '/[A-Za-z0-9._%+-]+@[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]+)*\.[A-Za-z]{2,}/';

// Encoding inside these would change what the code or styles see, not just what scrapers see.
const RAW_TEXT = '#(<(script|style|textarea)\b[^>]*>.*?</\2\s*>)#is';

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

	$parts = preg_split( RAW_TEXT, $html, -1, PREG_SPLIT_DELIM_CAPTURE );
	if ( false === $parts ) {
		return $html;
	}

	$out = '';
	foreach ( $parts as $i => $part ) {
		// preg_split with two capture groups yields text, whole raw element, tag name, text, ...
		$position = $i % 3;
		if ( 2 === $position ) {
			continue;
		}
		$out .= 1 === $position ? $part : (string) preg_replace_callback( ADDRESS, static fn( array $m ): string => $encode( $m[0] ), $part );
	}

	return $out;
}
