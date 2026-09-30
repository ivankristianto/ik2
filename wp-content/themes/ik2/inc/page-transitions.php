<?php
/**
 * Cross-page navigation: speculative prerendering and view transition render blocking.
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
	add_action( 'wp_head', __NAMESPACE__ . '\\expect_main_landmark', 1 );
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

/**
 * Hold first render until `<main id="ik-main">` is parsed, so a view transition
 * never cross-fades into a page that only has its header.
 */
function expect_main_landmark(): void {
	// Block templates render in full before the head is sent, so the wait is only
	// the body transfer. Browsers without cross-document transitions ignore it.
	echo '<link rel="expect" href="#ik-main" blocking="render">' . "\n";
}
