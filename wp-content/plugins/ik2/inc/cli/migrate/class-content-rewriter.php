<?php
/**
 * Pure helpers for finding and rewriting old upload URLs in post content.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Migrate;

defined( 'ABSPATH' ) || exit;

/**
 * Stateless string operations: strip WordPress size suffixes, pull upload
 * URLs out of HTML, and apply an old-to-new URL map. No WordPress or DB
 * dependencies, so it is unit-testable in isolation.
 */
class Content_Rewriter {

	/**
	 * Remove a WordPress "-WIDTHxHEIGHT" size suffix from a filename.
	 *
	 * "photo-1024x768.jpg" becomes "photo.jpg". Filenames without a size
	 * suffix (including "-scaled") are returned unchanged.
	 *
	 * @param string $filename Basename, with extension.
	 */
	public function strip_size_suffix( string $filename ): string {
		return (string) preg_replace( '/-\d+x\d+(?=\.[A-Za-z0-9]+$)/', '', $filename );
	}

	/**
	 * Collect every unique absolute URL under $old_base found in $content.
	 *
	 * @param string $content  Post content (HTML).
	 * @param string $old_base Old uploads base URL, no trailing slash.
	 * @return array<int, string>
	 */
	public function extract_upload_urls( string $content, string $old_base ): array {
		$pattern = '#' . preg_quote( $old_base, '#' ) . '/[^\s"\'<>()]+#i';

		if ( ! preg_match_all( $pattern, $content, $matches ) ) {
			return [];
		}

		return array_values( array_unique( $matches[0] ) );
	}

	/**
	 * Replace each old URL with its new URL throughout the content.
	 *
	 * @param string                $content Post content (HTML).
	 * @param array<string, string> $url_map old_url => new_url.
	 */
	public function rewrite( string $content, array $url_map ): string {
		if ( [] === $url_map ) {
			return $content;
		}

		return strtr( $content, $url_map );
	}
}
