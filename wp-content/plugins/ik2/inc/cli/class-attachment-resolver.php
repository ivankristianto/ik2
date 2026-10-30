<?php
/**
 * Find the media-library attachment an image URL belongs to.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI;

use IK2\Plugin\CLI\Migrate\Content_Rewriter;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves image srcs to attachment IDs, caching answers for the run.
 */
class Attachment_Resolver {

	/**
	 * Resolved attachment ID per src.
	 *
	 * @var array<string, int>
	 */
	private array $resolved = [];

	/**
	 * The attachment an image src belongs to, or 0.
	 *
	 * @param string $src Image src as written in the content.
	 */
	public function resolve( string $src ): int {
		if ( isset( $this->resolved[ $src ] ) ) {
			return $this->resolved[ $src ];
		}

		$url = (string) strtok( $src, '?#' );

		// Relative srcs from the old site resolve against this one.
		if ( str_starts_with( $url, '/' ) && ! str_starts_with( $url, '//' ) ) {
			$url = home_url( $url );
		}

		$id = attachment_url_to_postid( $url );

		// A resized copy (photo-300x200.jpg) isn't the attached file; its original is.
		if ( 0 === $id ) {
			$original = ( new Content_Rewriter() )->strip_size_suffix( $url );

			if ( $original !== $url ) {
				$id = attachment_url_to_postid( $original );

				// Big uploads are stored as photo-scaled.jpg.
				if ( 0 === $id ) {
					$id = attachment_url_to_postid( (string) preg_replace( '/(?=\.[A-Za-z0-9]+$)/', '-scaled', $original, 1 ) );
				}
			}
		}

		$this->resolved[ $src ] = $id;

		return $id;
	}
}
