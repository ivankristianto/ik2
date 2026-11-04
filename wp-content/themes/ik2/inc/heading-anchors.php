<?php
/**
 * Give post headings stable ids so readers, search results and AI answers can link to a section.
 *
 * PHPUnit loads this file to test the pure slug helpers, so there is no ABSPATH guard.
 *
 * @package IK2
 */

declare(strict_types=1);

namespace IK2\Theme\HeadingAnchors;

const HEADING_TAGS = [ 'H2', 'H3' ];

// Cheap bail-out before parsing: an h2 or h3 whose attributes don't include an id.
const BARE_HEADING_PATTERN = '/<h[23](?![^>]*\sid\s*=)[\s>]/i';

const MAX_SLUG_LENGTH = 60;

/**
 * Register hooks owned by this module.
 */
function bootstrap(): void {
	add_filter( 'render_block_core/post-content', __NAMESPACE__ . '\\add_heading_ids' );
}

/**
 * Anchor headings on single posts only; feeds, excerpts and REST keep the stored markup.
 *
 * @param string $block_content Rendered post-content HTML.
 */
function add_heading_ids( string $block_content ): string {
	if ( ! is_singular( 'post' ) || ! preg_match( BARE_HEADING_PATTERN, $block_content ) ) {
		return $block_content;
	}

	return anchor_headings( $block_content );
}

/**
 * Add an id to every h2 and h3 that lacks one, unique within the content.
 *
 * @param string $html Rendered post content.
 */
function anchor_headings( string $html ): string {
	$taken = existing_ids( $html );
	$tags  = new \WP_HTML_Tag_Processor( $html );

	while ( $tags->next_tag() ) {
		if ( ! in_array( $tags->get_tag(), HEADING_TAGS, true ) || $tags->get_attribute( 'id' ) !== null ) {
			continue;
		}

		$tags->set_bookmark( 'heading' );
		$slug = heading_slug( text_until_closer( $tags, (string) $tags->get_tag() ) );
		$tags->seek( 'heading' );

		if ( $slug === '' ) {
			continue;
		}

		$id           = unique_id( $slug, $taken );
		$taken[ $id ] = true;
		$tags->set_attribute( 'id', $id );
	}

	$tags->release_bookmark( 'heading' );

	return $tags->get_updated_html();
}

/**
 * Every id already in the content, so a new one can't collide with an element further down.
 *
 * @param string $html Rendered post content.
 * @return array<string,true>
 */
function existing_ids( string $html ): array {
	$ids  = [];
	$tags = new \WP_HTML_Tag_Processor( $html );

	while ( $tags->next_tag() ) {
		$id = $tags->get_attribute( 'id' );

		if ( is_string( $id ) ) {
			$ids[ $id ] = true;
		}
	}

	return $ids;
}

/**
 * Decoded text from the current tag up to its closer, markup dropped.
 *
 * @param \WP_HTML_Tag_Processor $tags Processor paused on the opening tag.
 * @param string                 $tag  Tag name to stop at.
 */
function text_until_closer( \WP_HTML_Tag_Processor $tags, string $tag ): string {
	$text = '';

	while ( $tags->next_token() ) {
		if ( $tags->get_token_type() === '#tag' && $tags->is_tag_closer() && $tags->get_tag() === $tag ) {
			break;
		}

		if ( $tags->get_token_type() === '#text' ) {
			$text .= $tags->get_modifiable_text();
		}
	}

	return $text;
}

/**
 * Lowercase, hyphenated heading text, cut at a word boundary.
 *
 * Apostrophes go without a hyphen, as in the ids the drafting pipeline stores.
 *
 * @param string $text Decoded heading text.
 */
function heading_slug( string $text ): string {
	$text = str_replace( [ "'", "\u{2019}" ], '', mb_strtolower( $text ) );
	$slug = trim( (string) preg_replace( '/[^\p{L}\p{N}]+/u', '-', $text ), '-' );

	if ( strlen( $slug ) <= MAX_SLUG_LENGTH ) {
		return $slug;
	}

	$cut = substr( $slug, 0, MAX_SLUG_LENGTH + 1 );
	$end = strrpos( $cut, '-' );

	return rtrim( $end ? substr( $cut, 0, $end ) : mb_strcut( $slug, 0, MAX_SLUG_LENGTH ), '-' );
}

/**
 * First of slug, slug-2, slug-3 and so on that no other element uses.
 *
 * @param string             $slug  Heading slug.
 * @param array<string,true> $taken Ids already in the content.
 */
function unique_id( string $slug, array $taken ): string {
	$id = $slug;

	for ( $n = 2; isset( $taken[ $id ] ); $n++ ) {
		$id = $slug . '-' . $n;
	}

	return $id;
}
