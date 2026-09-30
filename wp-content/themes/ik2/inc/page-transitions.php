<?php
/**
 * Cross-page navigation: speculative prerendering of same-origin links.
 *
 * @package IK2
 */

declare(strict_types=1);

namespace IK2\Theme\PageTransitions;

defined( 'ABSPATH' ) || exit;

/**
 * Register hooks owned by this module.
 */
function bootstrap(): void {
	add_filter( 'wp_speculation_rules_configuration', __NAMESPACE__ . '\\prerender_on_hover' );
	add_filter( 'wp_speculation_rules_href_exclude_paths', __NAMESPACE__ . '\\exclude_feed_paths' );
}

/**
 * Upgrade core's conservative prefetch rule to prerender on hover or pointerdown.
 *
 * @param array<string,string>|null $config Null when core disabled speculation (e.g. logged in).
 * @return array<string,string>|null
 */
function prerender_on_hover( ?array $config ): ?array {
	// Pages are mostly static and CDN-cached, so a speculative render is cheap,
	// and a prerendered page gives the view transition a painted destination.
	if ( $config === null ) {
		return null;
	}

	return [
		'mode'      => 'prerender',
		'eagerness' => 'moderate',
	];
}

/**
 * Keep RSS/Atom feeds out of the rule; nobody navigates to them in-tab.
 *
 * @param array<int,string> $paths URL patterns core already excludes.
 * @return array<int,string>
 */
function exclude_feed_paths( array $paths ): array {
	return array_merge( $paths, [ '/feed', '/feed/*', '/*/feed', '/*/feed/*' ] );
}

