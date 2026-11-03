<?php
/**
 * Reading time from Yoast: the "N min read" meta labels.
 *
 * @package IK2
 */

declare(strict_types=1);

namespace IK2\Theme\ReadingTime;

defined( 'ABSPATH' ) || exit;

const LABEL_CLASSES = [ 'ik-article__reading', 'ik-related__reading' ];

// Saved by Yoast's editor sidebar; its indexable copies this value.
const YOAST_META_KEY = '_yoast_wpseo_estimated-reading-time-minutes';

// Yoast's fallback rate when nothing was saved (Indexable_Presentation).
const YOAST_WORDS_PER_MINUTE = 200;

/**
 * Register hooks owned by this module.
 */
function bootstrap(): void {
	add_filter( 'render_block_core/post-time-to-read', __NAMESPACE__ . '\\label_reading_time', 10, 3 );
}

/**
 * Yoast's "Est. reading time" for a post; 0 for a short post, where Yoast shows none.
 *
 * @param int $post_id Post ID.
 */
function minutes( int $post_id ): int {
	// Not YoastSEO()->meta->for_post(): it caches contexts by indexable ID, so unsaved indexables collide.
	$saved = get_post_meta( $post_id, YOAST_META_KEY, true );

	if ( '' !== $saved ) {
		return max( 0, (int) $saved );
	}

	$words = str_word_count( wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) ) );

	return (int) round( $words / YOAST_WORDS_PER_MINUTE );
}

/**
 * Print the meta-line reading time as "N min read" with Yoast's minutes, so a post reads the same everywhere.
 *
 * @param string              $html     Rendered post-time-to-read block.
 * @param array<string,mixed> $block    Parsed block.
 * @param \WP_Block           $instance Block instance, carrying the postId context.
 */
function label_reading_time( string $html, array $block, \WP_Block $instance ): string {
	$classes = explode( ' ', (string) ( $block['attrs']['className'] ?? '' ) );
	$post_id = (int) ( $instance->context['postId'] ?? 0 );

	if ( ! array_intersect( LABEL_CLASSES, $classes ) || $post_id <= 0 ) {
		return $html;
	}

	$minutes = minutes( $post_id );

	if ( 0 === $minutes ) {
		return '';
	}

	/* translators: %d: minutes it takes to read the post. */
	$label = sprintf( __( '%d min read', 'ik2' ), $minutes );

	return (string) preg_replace( '/>[^<]*</', '>' . esc_html( $label ) . '<', $html, 1 );
}

