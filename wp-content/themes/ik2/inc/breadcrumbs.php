<?php
/**
 * Single-post trail: the section and the post's primary category.
 *
 * @package IK2
 */

declare(strict_types=1);

namespace IK2\Theme\Breadcrumbs;

defined( 'ABSPATH' ) || exit;

/**
 * Register hooks owned by this module.
 */
function bootstrap(): void {
	add_filter( 'wpseo_breadcrumb_links', __NAMESPACE__ . '\\insert_parent_crumb' );
}

/**
 * The section a single post belongs to: Speaking for talks, Articles otherwise.
 *
 * @return array{text: string, url: string}
 */
function parent_crumb(): array {
	if ( has_category( 'talk', get_queried_object_id() ) ) {
		return [
			'text' => __( 'Speaking', 'ik2' ),
			'url'  => home_url( '/speaking/' ),
		];
	}

	return [
		'text' => __( 'Articles', 'ik2' ),
		'url'  => home_url( '/articles/' ),
	];
}

/**
 * The post's primary category: Yoast's pick when set, else the first one. Null when uncategorized.
 *
 * @param int $post_id Post id.
 */
function primary_category( int $post_id ): ?\WP_Term {
	$term_id = function_exists( 'yoast_get_primary_term_id' ) ? (int) yoast_get_primary_term_id( 'category', $post_id ) : 0;
	$term    = $term_id > 0 ? get_term( $term_id, 'category' ) : null;

	$default = (int) get_option( 'default_category' );

	if ( $term instanceof \WP_Term && $default !== $term->term_id ) {
		return $term;
	}

	foreach ( get_the_category( $post_id ) as $category ) {
		if ( $default !== $category->term_id ) {
			return $category;
		}
	}

	return null;
}

/**
 * Section, then primary category. Talks show Speaking alone, whatever their other categories.
 *
 * @return array<int, array{text: string, url: string}>
 */
function trail(): array {
	$post_id = get_queried_object_id();
	$crumbs  = [ parent_crumb() ];

	if ( has_category( 'talk', $post_id ) ) {
		return $crumbs;
	}

	$category = primary_category( $post_id );

	if ( ! $category instanceof \WP_Term ) {
		return $crumbs;
	}

	$url = get_term_link( $category );

	if ( is_string( $url ) ) {
		$crumbs[] = [
			'text' => $category->name,
			'url'  => $url,
		];
	}

	return $crumbs;
}

/**
 * Put the parent section between Home and the post title. Also feeds Yoast's BreadcrumbList schema.
 *
 * @param array<int, array<string, mixed>> $links Yoast breadcrumb links.
 * @return array<int, array<string, mixed>>
 */
function insert_parent_crumb( array $links ): array {
	if ( ! is_singular( 'post' ) || count( $links ) < 2 ) {
		return $links;
	}

	$parent = parent_crumb();

	foreach ( $links as $link ) {
		if ( isset( $link['url'] ) && untrailingslashit( (string) $link['url'] ) === untrailingslashit( $parent['url'] ) ) {
			return $links;
		}
	}

	array_splice( $links, 1, 0, [ $parent ] );

	return $links;
}
