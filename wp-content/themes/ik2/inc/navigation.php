<?php
/**
 * Header navigation: mark the current menu item and make the skip link land.
 *
 * Core's navigation-link block only sets aria-current when it can resolve the
 * link to a real post or page. The IK2 Primary menu uses custom links pointing
 * at virtual routes like `/articles`, so we mark current items ourselves.
 *
 * @package IK2
 */

declare(strict_types=1);

namespace IK2\Theme\Navigation;

defined( 'ABSPATH' ) || exit;

/**
 * Register hooks owned by this module.
 */
function bootstrap(): void {
	add_filter( 'render_block_core/navigation-link', __NAMESPACE__ . '\\mark_current_navigation_link', 10, 2 );
	add_filter( 'render_block_core/group', __NAMESPACE__ . '\\make_skip_link_target_focusable', 10, 2 );

	// The header part ships its own skip link; core's would be a second identical one.
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_block_template_skip_link' );
	remove_action( 'wp_footer', 'the_block_template_skip_link' );
}

/**
 * Add tabindex="-1" to `<main id="ik-main">` so the header skip link moves focus there.
 *
 * @param string       $block_content Rendered group HTML.
 * @param array<mixed> $block         Parsed block data.
 */
function make_skip_link_target_focusable( string $block_content, array $block ): string {
	// Set at render time: baking the attribute into the template markup would
	// fail core/group block validation in the Site Editor.
	if ( ( $block['attrs']['anchor'] ?? '' ) !== 'ik-main' ) {
		return $block_content;
	}

	$tags = new \WP_HTML_Tag_Processor( $block_content );

	if ( $tags->next_tag( [ 'tag_name' => 'main' ] ) && $tags->get_attribute( 'tabindex' ) === null ) {
		$tags->set_attribute( 'tabindex', '-1' );
	}

	return $tags->get_updated_html();
}

/**
 * Normalize a URL to a path with no host and no trailing slash, decoded once,
 * lower-cased. Used to compare nav link hrefs against the current request.
 *
 * @param string $url Absolute or root-relative URL to normalize.
 */
function ik2_normalize_path( string $url ): string {
	$path = (string) wp_parse_url( $url, PHP_URL_PATH );
	$path = rawurldecode( $path );
	$path = '/' . trim( $path, '/' );

	return strtolower( $path );
}

/**
 * Inject aria-current="page" and a `current-menu-item` class on the anchor
 * when a navigation-link block points at the current request path.
 *
 * @param string       $block_content Rendered navigation-link HTML.
 * @param array<mixed> $block         Parsed block data.
 */
function mark_current_navigation_link( string $block_content, array $block ): string {
	if ( is_admin() || $block_content === '' ) {
		return $block_content;
	}

	$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : [];
	$url   = isset( $attrs['url'] ) ? (string) $attrs['url'] : '';

	if ( $url === '' ) {
		return $block_content;
	}

	$link_path    = ik2_normalize_path( $url );
	$current_path = ik2_normalize_path( home_url( add_query_arg( [] ) ) );

	if ( $link_path !== $current_path ) {
		return $block_content;
	}

	$processor = new \WP_HTML_Tag_Processor( $block_content );

	if ( ! $processor->next_tag( [ 'tag_name' => 'a' ] ) ) {
		return $block_content;
	}

	$processor->set_attribute( 'aria-current', 'page' );

	$existing_class = (string) $processor->get_attribute( 'class' );

	if ( ! str_contains( $existing_class, 'current-menu-item' ) ) {
		$processor->set_attribute(
			'class',
			trim( $existing_class . ' current-menu-item' )
		);
	}

	return $processor->get_updated_html();
}

/**
 * Whether the Resume page is the current request. The pattern uses this to
 * mark the Resume CTA in the header as current.
 */
function ik2_is_resume_current(): bool {
	$current_path = ik2_normalize_path( home_url( add_query_arg( [] ) ) );

	return $current_path === '/resume';
}
