<?php
/**
 * Tag lines: lowercase mono tags split by a middle dot, on article cards and under a post.
 *
 * Core's post-terms block prints its separator as a span, which a flex row
 * spaces like a tag. These lines are rebuilt from the terms instead.
 *
 * @package IK2
 */

declare(strict_types=1);

namespace IK2\Theme\PostTags;

defined( 'ABSPATH' ) || exit;

const CARD_CLASS = 'ik-article-card__tags';
const POST_CLASS = 'ik-article__tagged';
const CARD_LIMIT = 3;

/**
 * Register hooks owned by this module.
 */
function bootstrap(): void {
	add_filter( 'render_block_core/post-terms', __NAMESPACE__ . '\\render_tag_line', 10, 3 );
}

/**
 * Replace a card or post tag list with the tag line markup.
 *
 * @param string       $block_content Rendered post-terms HTML.
 * @param array<mixed> $block         Parsed block data.
 * @param \WP_Block    $instance      Block instance, for the post ID in context.
 */
function render_tag_line( string $block_content, array $block, \WP_Block $instance ): string {
	if ( $block_content === '' || ( $block['attrs']['term'] ?? '' ) !== 'post_tag' ) {
		return $block_content;
	}

	$classes = explode( ' ', (string) ( $block['attrs']['className'] ?? '' ) );
	$is_card = in_array( CARD_CLASS, $classes, true );

	if ( ! $is_card && ! in_array( POST_CLASS, $classes, true ) ) {
		return $block_content;
	}

	$post_id = (int) ( $instance->context['postId'] ?? 0 );
	$tags    = get_the_terms( $post_id, 'post_tag' );

	if ( ! is_array( $tags ) ) {
		return $block_content;
	}

	// The card cover already names the category, so a tag that repeats it is noise there.
	if ( $is_card ) {
		$tags = drop_category_echoes( $tags, $post_id );
	}

	if ( ! $tags ) {
		return '';
	}

	$shown  = $is_card ? array_slice( $tags, 0, CARD_LIMIT ) : $tags;
	$hidden = count( $tags ) - count( $shown );
	$links  = [];

	foreach ( $shown as $tag ) {
		$links[] = sprintf(
			'<a href="%s" rel="tag">%s</a>',
			esc_url( (string) get_term_link( $tag ) ),
			esc_html( mb_strtolower( $tag->name ) )
		);
	}

	$html = implode( '<span class="ik-tag-line__dot" aria-hidden="true">·</span>', $links );

	if ( $hidden > 0 ) {
		$html .= sprintf(
			'<a class="ik-tag-line__more" href="%s" aria-label="%s">+%d</a>',
			esc_url( get_permalink( $post_id ) . '#tags' ),
			/* translators: %d: number of tags not shown on the card */
			esc_attr( sprintf( _n( '%d more tag', '%d more tags', $hidden, 'ik2' ), $hidden ) ),
			$hidden
		);
	}

	$prefix = trim( (string) ( $block['attrs']['prefix'] ?? '' ) );

	if ( $prefix !== '' ) {
		$html = '<span class="ik-tag-line__label">' . esc_html( $prefix ) . '</span>' . $html;
	}

	$wrapper = new \WP_HTML_Tag_Processor( $block_content );

	if ( ! $wrapper->next_tag() ) {
		return $block_content;
	}

	$wrapper->add_class( 'ik-tag-line' );

	if ( ! $is_card ) {
		$wrapper->set_attribute( 'id', 'tags' );
	}

	$opening = $wrapper->get_updated_html();

	return substr( $opening, 0, (int) strpos( $opening, '>' ) + 1 ) . $html . '</div>';
}

/**
 * Drop tags whose slug or name matches one of the post's categories.
 *
 * @param array<\WP_Term> $tags    The post's tags.
 * @param int             $post_id Post ID.
 * @return array<\WP_Term>
 */
function drop_category_echoes( array $tags, int $post_id ): array {
	$category_keys = [];

	foreach ( get_the_category( $post_id ) as $category ) {
		$category_keys[] = $category->slug;
		$category_keys[] = mb_strtolower( $category->name );
	}

	$kept = [];

	foreach ( $tags as $tag ) {
		if ( ! in_array( $tag->slug, $category_keys, true ) && ! in_array( mb_strtolower( $tag->name ), $category_keys, true ) ) {
			$kept[] = $tag;
		}
	}

	return $kept;
}
