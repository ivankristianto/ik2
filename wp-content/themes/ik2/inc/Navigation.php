<?php
/**
 * Header navigation: mark the current menu item.
 *
 * Core's navigation-link block only sets aria-current when it can resolve the
 * link to a real post or page. The IK2 Primary menu uses custom links pointing
 * at virtual routes like `/articles`, so we mark current items ourselves.
 *
 * @package IK2
 */

declare(strict_types=1);

namespace IK2\Theme;

defined( 'ABSPATH' ) || exit;

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
 */
add_filter(
	'render_block_core/navigation-link',
	static function ( string $block_content, array $block ): string {
		if ( is_admin() || '' === $block_content ) {
			return $block_content;
		}

		$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
		$url   = isset( $attrs['url'] ) ? (string) $attrs['url'] : '';

		if ( '' === $url ) {
			return $block_content;
		}

		$link_path    = ik2_normalize_path( $url );
		$current_path = ik2_normalize_path( home_url( add_query_arg( array() ) ) );

		if ( $link_path !== $current_path ) {
			return $block_content;
		}

		$processor = new \WP_HTML_Tag_Processor( $block_content );

		if ( ! $processor->next_tag( array( 'tag_name' => 'a' ) ) ) {
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
	},
	10,
	2
);

/**
 * Whether the Resume page is the current request. The pattern uses this to
 * mark the Resume CTA in the header as current.
 */
function ik2_is_resume_current(): bool {
	$current_path = ik2_normalize_path( home_url( add_query_arg( array() ) ) );

	return '/resume' === $current_path;
}
