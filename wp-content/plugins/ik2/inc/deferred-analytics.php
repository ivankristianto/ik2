<?php
/**
 * Start gtag.js and the Cloudflare Web Analytics beacon after window load.
 *
 * Fetched during load, both third parties finish before the hero image paints, so
 * Lighthouse's simulated LCP counts them and lands near 1s instead of near 0.35s.
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
	// Site Kit enqueues gtag at priority 20.
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\defer_gtag', 21 );
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_beacon' );
}

/**
 * Take gtag.js off Site Kit's handle and inject it after load instead.
 */
function defer_gtag(): void {
	$script = wp_scripts()->query( GTAG_HANDLE );

	if ( ! $script instanceof \_WP_Dependency || ! is_string( $script->src ) || $script->src === '' ) {
		return;
	}

	$src = $script->src;

	// Core still prints a src-less handle's inline scripts, so Site Kit's gtag() queue stays in the head.
	$script->src = false;

	wp_add_inline_script( GTAG_HANDLE, after_load( 'var s=document.createElement("script");s.src=' . wp_json_encode( $src ) . ';s.async=true;document.head.appendChild(s);' ) );
}

/**
 * Load the beacon after load, unless Cloudflare's edge injection already added one.
 */
function enqueue_beacon(): void {
	$config = wp_json_encode( [ 'token' => BEACON_TOKEN ] );

	wp_register_script( BEACON_HANDLE, false, [], null, [ 'in_footer' => true ] ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- No file to version.
	wp_add_inline_script(
		BEACON_HANDLE,
		after_load( 'if(!document.querySelector(\'script[src*="static.cloudflareinsights.com/beacon"]\')){var s=document.createElement("script");s.src=' . wp_json_encode( BEACON_SRC ) . ';s.defer=true;s.dataset.cfBeacon=' . wp_json_encode( $config ) . ';document.body.appendChild(s);}' )
	);
	wp_enqueue_script( BEACON_HANDLE );
}

/**
 * Wrap a snippet to run once the browser is idle after load; load alone can fire before the LCP paint.
 *
 * @param string $js Statements to run.
 */
function after_load( string $js ): string {
	return 'addEventListener("load",function(){var run=function(){' . $js . '};"requestIdleCallback" in window?requestIdleCallback(run,{timeout:2000}):setTimeout(run,200);},{once:true});';
}
