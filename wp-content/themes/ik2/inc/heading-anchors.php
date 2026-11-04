<?php
/**
 * Give post headings stable ids so readers, search results and AI answers can link to a section.
 *
 * PHPUnit loads this file directly, so there is no ABSPATH guard; only the hook
 * functions call WordPress.
 *
 * @package IK2
 */

declare(strict_types=1);

namespace IK2\Theme\HeadingAnchors;

const HEADING_PATTERN = '/<h([23])\b([^>]*)>(.*?)<\/h\1>/is';
const ID_PATTERN      = '/\sid\s*=\s*["\']?([^"\'\s>]+)/i';
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
	return is_singular( 'post' ) ? anchor_headings( $block_content ) : $block_content;
}

/**
 * Add an id to every h2 and h3 that lacks one, unique within the content.
 *
 * @param string $html Rendered post content.
 */
function anchor_headings( string $html ): string {
	if ( ! preg_match_all( HEADING_PATTERN, $html, $headings, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
		return $html;
	}

	preg_match_all( ID_PATTERN, $html, $ids );
	$taken  = array_fill_keys( $ids[1], true );
	$out    = '';
	$cursor = 0;

	foreach ( $headings as $heading ) {
		[ $match, $start ] = $heading[0];

		if ( preg_match( ID_PATTERN, $heading[2][0] ) ) {
			continue;
		}

		$slug = heading_slug( $heading[3][0] );

		if ( $slug === '' ) {
			continue;
		}

		$id = unique_id( $slug, $taken );

		$taken[ $id ] = true;
		$tag_end      = $start + 3;
		$out         .= substr( $html, $cursor, $tag_end - $cursor ) . ' id="' . $id . '"';
		$cursor       = $tag_end;
	}

	return $out . substr( $html, $cursor );
}

/**
 * Lowercase, hyphenated heading text, cut at a word boundary.
 *
 * @param string $inner_html Heading inner HTML.
 */
function heading_slug( string $inner_html ): string {
	$text = html_entity_decode( strip_tags( $inner_html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- loaded by PHPUnit without WordPress.
	$slug = trim( (string) preg_replace( '/[^\p{L}\p{N}]+/u', '-', mb_strtolower( $text ) ), '-' );

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
