<?php
/**
 * Single-post breadcrumbs: Yoast's trail when available, theme markup otherwise.
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
	add_filter( 'wpseo_breadcrumb_separator', __NAMESPACE__ . '\\separator' );
	add_filter( 'wpseo_breadcrumb_single_link', __NAMESPACE__ . '\\add_crumb_classes' );
}

/**
 * Whether Yoast is active, its block registered, and breadcrumbs enabled in its settings.
 */
function use_yoast(): bool {
	if ( ! class_exists( 'WPSEO_Options' ) ) {
		return false;
	}

	if ( ! \WP_Block_Type_Registry::get_instance()->is_registered( 'yoast-seo/breadcrumbs' ) ) {
		return false;
	}

	return (bool) \WPSEO_Options::get( 'breadcrumbs-enable' );
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

/**
 * Match the fallback trail's separator instead of Yoast's configured one.
 */
function separator(): string {
	return '<span class="ik-crumbs__sep" aria-hidden="true">/</span>';
}

/**
 * Give Yoast's crumbs the theme's link and current-item classes.
 *
 * @param string $link_output Rendered crumb HTML.
 */
function add_crumb_classes( string $link_output ): string {
	$processor = new \WP_HTML_Tag_Processor( $link_output );

	if ( $processor->next_tag( [ 'tag_name' => 'a' ] ) ) {
		$processor->add_class( 'ik-crumbs__link' );
		return $processor->get_updated_html();
	}

	$processor = new \WP_HTML_Tag_Processor( $link_output );

	if ( $processor->next_tag( [ 'class_name' => 'breadcrumb_last' ] ) ) {
		$processor->add_class( 'ik-crumbs__current' );
	}

	return $processor->get_updated_html();
}
