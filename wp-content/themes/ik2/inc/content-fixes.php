<?php
/**
 * Render-time accessibility fixes for pattern markup already saved in page content.
 *
 * Pattern-backed pages store their blocks in post_content, so a pattern fix only
 * reaches new pages. These filters apply the same fixes to the stored markup
 * without a re-save.
 *
 * @package IK2
 */

declare(strict_types=1);

namespace IK2\Theme\ContentFixes;

defined( 'ABSPATH' ) || exit;

const DEMOTED_ASIDES = [ 'ik-hero__portrait', 'ik-resume__meta' ];

/**
 * Register hooks owned by this module.
 */
function bootstrap(): void {
	add_filter( 'render_block_core/group', __NAMESPACE__ . '\\demote_nested_aside', 10, 2 );
	add_filter( 'render_block_core/paragraph', __NAMESPACE__ . '\\hide_resume_separators', 10, 2 );
	add_filter( 'render_block_core/image', __NAMESPACE__ . '\\name_hero_portrait', 10, 2 );
}

/**
 * Render the hero portrait and resume meta groups as `<div>` rather than `<aside>`.
 *
 * Both sit inside `<main>`, where a complementary landmark must be top level.
 *
 * @param string       $block_content Rendered group HTML.
 * @param array<mixed> $block         Parsed block data.
 */
function demote_nested_aside( string $block_content, array $block ): string {
	$class_name = (string) ( $block['attrs']['className'] ?? '' );

	if ( ( $block['attrs']['tagName'] ?? '' ) !== 'aside' || ! array_intersect( DEMOTED_ASIDES, explode( ' ', $class_name ) ) ) {
		return $block_content;
	}

	$open  = strpos( $block_content, '<aside' );
	$close = strrpos( $block_content, '</aside>' );

	if ( $open === false || $close === false ) {
		return $block_content;
	}

	$block_content = substr_replace( $block_content, '</div>', $close, strlen( '</aside>' ) );

	return substr_replace( $block_content, '<div', $open, strlen( '<aside' ) );
}

/**
 * Hide the decorative middle dots in the resume eyebrow from screen readers.
 *
 * @param string       $block_content Rendered paragraph HTML.
 * @param array<mixed> $block         Parsed block data.
 */
function hide_resume_separators( string $block_content, array $block ): string {
	if ( ! str_contains( (string) ( $block['attrs']['className'] ?? '' ), 'ik-resume__eyebrow' ) ) {
		return $block_content;
	}

	$tags = new \WP_HTML_Tag_Processor( $block_content );

	while ( $tags->next_tag( [ 'class_name' => 'ik-resume__eyebrow-sep' ] ) ) {
		$tags->set_attribute( 'aria-hidden', 'true' );
	}

	return $tags->get_updated_html();
}

/**
 * Give the home hero portrait the full name as its alt text.
 *
 * @param string       $block_content Rendered image HTML.
 * @param array<mixed> $block         Parsed block data.
 */
function name_hero_portrait( string $block_content, array $block ): string {
	if ( ! str_contains( (string) ( $block['attrs']['className'] ?? '' ), 'ik-hero__portrait-frame' ) ) {
		return $block_content;
	}

	$tags = new \WP_HTML_Tag_Processor( $block_content );

	if ( $tags->next_tag( [ 'tag_name' => 'img' ] ) && $tags->get_attribute( 'alt' ) === 'Ivan' ) {
		$tags->set_attribute( 'alt', 'Ivan Kristianto' );
	}

	return $tags->get_updated_html();
}
