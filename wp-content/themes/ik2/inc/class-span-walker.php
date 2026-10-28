<?php
/**
 * Tag processor that reports where the current token sits in the source HTML.
 *
 * @package IK2
 */

declare(strict_types=1);

namespace IK2\Theme\Footnotes;

defined( 'ABSPATH' ) || exit;

/**
 * Walks tokens like WP_HTML_Tag_Processor and exposes each token's byte span, so callers can splice HTML in.
 */
class Span_Walker extends \WP_HTML_Tag_Processor {

	/**
	 * Byte offset and length of the current token.
	 *
	 * @return array{0: int, 1: int}
	 */
	public function span(): array {
		// Bookmarks are the only protected handle on token positions; reusing one name keeps the bookmark count at one.
		$this->set_bookmark( 'span' );
		$bookmark = $this->bookmarks['span'];

		return [ $bookmark->start, $bookmark->length ];
	}
}
