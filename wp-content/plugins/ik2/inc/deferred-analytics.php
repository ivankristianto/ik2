<?php
/**
 * Start gtag.js and the Cloudflare Web Analytics beacon after window load.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\DeferredAnalytics;

defined( 'ABSPATH' ) || exit;

const GTAG_HANDLE   = 'google_gtagjs';
const BEACON_HANDLE = 'ik2-cf-beacon';
const BEACON_SRC    = 'https://static.cloudflareinsights.com/beacon.min.js';

// Public site tag: Cloudflare prints it in every page it serves.
const BEACON_TOKEN = '202bcc91edcd41edba677ce8b2842dfa';

/**
 * Register hooks owned by this module.
 */
function bootstrap(): void {
	// Lighthouse's simulated LCP counts every request that finishes before the paint; in the head these put it near 1s.
	// Site Kit enqueues gtag at priority 20.
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\defer_gtag', 21 );
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_beacon' );
}

/**
 * Take gtag.js off Site Kit's handle and inject it after load instead.
 */
function defer_gtag(): void {
	$script = wp_scripts()->query( GTAG_HANDLE );

	// With Site Kit's Google tag gateway on, the handle has no src and gtag loads through its deps undeferred.
	if ( ! $script instanceof \_WP_Dependency || ! is_string( $script->src ) || $script->src === '' ) {
		return;
	}

	$src = $script->src;

	// Core still prints a src-less handle's inline scripts, so Site Kit's gtag() queue stays in the head.
	$script->src = false;

	wp_add_inline_script( GTAG_HANDLE, after_load( inject_script( $src, 'document.head' ) ) );
}

/**
 * Load the beacon after load, unless Cloudflare's edge injection already added one.
 */
function enqueue_beacon(): void {
	$attributes = [
		'type'           => 'module',
		'data-cf-beacon' => wp_json_encode( [ 'token' => BEACON_TOKEN ] ),
	];

	wp_register_script( BEACON_HANDLE, false, [], null, [ 'in_footer' => true ] ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- No file to version.
	wp_add_inline_script(
		BEACON_HANDLE,
		after_load( 'if(!document.querySelector(\'script[src*="static.cloudflareinsights.com/beacon"]\')){' . inject_script( BEACON_SRC, 'document.body', $attributes ) . '}' )
	);
	wp_enqueue_script( BEACON_HANDLE );
}

/**
 * JS that appends a script element to `$container`.
 *
 * @param string               $src        Script URL.
 * @param string               $container  JS expression for the parent node.
 * @param array<string,string> $attributes Extra attributes for the element.
 */
function inject_script( string $src, string $container, array $attributes = [] ): string {
	$js = 'var s=document.createElement("script");s.src=' . wp_json_encode( $src ) . ';';

	foreach ( $attributes as $name => $value ) {
		$js .= 's.setAttribute(' . wp_json_encode( $name ) . ',' . wp_json_encode( $value ) . ');';
	}

	return $js . $container . '.appendChild(s);';
}

/**
 * Wrap a snippet to run once the browser is idle after load; load alone can fire before the LCP paint.
 *
 * @param string $js Statements to run.
 */
function after_load( string $js ): string {
	return 'addEventListener("load",function(){var run=function(){' . $js . '};"requestIdleCallback" in window?requestIdleCallback(run,{timeout:2000}):setTimeout(run,200);},{once:true});';
}
