<?php
/**
 * Enables WP Super Cache's page cache at the wp-config level.
 *
 * The plugin normally writes these two constants into wp-config.php on
 * activation, but this image bakes wp-config.php and the page-cache drop-ins at
 * build time (see Dockerfile), so define them here instead:
 *
 *   - WP_CACHE     — WordPress core only loads wp-content/advanced-cache.php
 *                    when this is true.
 *   - WPCACHEHOME  — advanced-cache.php resolves wp-cache-phase1.php from here,
 *                    and it must be defined BEFORE advanced-cache.php runs
 *                    (i.e. in wp-config.php, not in wp-cache-config.php).
 *
 * Required into wp-config.php at image build time. __DIR__ is the webroot
 * (/var/www/app), where this file is copied alongside wp-config.php, so the
 * plugin path resolves without depending on WP_CONTENT_DIR being defined yet.
 * Skipped gracefully if wp-super-cache is not installed — a true WP_CACHE with
 * no advanced-cache.php present is a harmless no-op in WordPress core.
 *
 * @package IK2
 */

if ( ! defined( 'WP_CACHE' ) ) {
	define( 'WP_CACHE', true );
}

if ( ! defined( 'WPCACHEHOME' ) ) {
	define( 'WPCACHEHOME', __DIR__ . '/wp-content/plugins/wp-super-cache/' );
}
