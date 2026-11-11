<?php
/**
 * Pure helpers for lab entries: which URLs may be framed, and how tall the frame may be. No WordPress, unit-tested.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\PostTypes\Lab\Rules;

const DEFAULT_ORIGIN = 'https://lab.ivankristianto.com';

const DEFAULT_HEIGHT = 480;
const MIN_HEIGHT     = 160;
const MAX_HEIGHT     = 4000;

/**
 * The origin of an http(s) URL as `scheme://host[:port]`, or '' when the URL is not one we would frame.
 *
 * @param string $url Absolute URL.
 */
function url_origin( string $url ): string {
	// Strict on purpose: userinfo, backslashes and whitespace are how a URL shows one host and loads another.
	if ( 1 !== preg_match( '#^(https?)://([a-z0-9.-]+)(?::(\d{1,5}))?(?:/[^\s\\\\]*)?$#iD', $url, $parts ) ) {
		return '';
	}

	$scheme  = strtolower( $parts[1] );
	$port    = $parts[3] ?? '';
	$default = 'https' === $scheme ? '443' : '80';

	return $scheme . '://' . strtolower( $parts[2] ) . ( '' === $port || $default === $port ? '' : ':' . $port );
}

/**
 * Whether a tool URL sits on the configured lab origin.
 *
 * @param string $url    Tool URL from the entry.
 * @param string $origin Lab origin, e.g. https://lab.ivankristianto.com.
 */
function is_lab_url( string $url, string $origin ): bool {
	$expected = url_origin( $origin );

	return '' !== $expected && url_origin( $url ) === $expected;
}

/**
 * A frame height in px inside the allowed range. Zero or less means "not set" and gives the default.
 *
 * @param int $px Requested height.
 */
function clamp_height( int $px ): int {
	if ( $px <= 0 ) {
		return DEFAULT_HEIGHT;
	}

	return max( MIN_HEIGHT, min( MAX_HEIGHT, $px ) );
}
