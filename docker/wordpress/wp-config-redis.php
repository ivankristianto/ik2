<?php
/**
 * Builds the global $redis_server consumed by the wp-redis object-cache.php
 * drop-in from a single REDIS_SERVER env var in URL form:
 *
 *     REDIS_SERVER=redis://user:password@host:6379/0
 *                         └ scheme://[user[:pass]@]host[:port][/database]
 *
 * Required into wp-config.php at image build time (see Dockerfile). Runs
 * before WordPress loads, so no WP APIs are available here. When the env var
 * is unset or unparseable, $redis_server stays undefined and wp-redis falls
 * back to its own defaults / graceful degradation.
 *
 * @package IK2
 */

$ik2_redis_url = getenv( 'REDIS_SERVER' );

if ( ! empty( $ik2_redis_url ) ) {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- wp_parse_url() does not exist yet; this runs from wp-config.php, before WordPress loads.
	$ik2_redis_parts = parse_url( $ik2_redis_url );

	if ( false !== $ik2_redis_parts && ! empty( $ik2_redis_parts['host'] ) ) {
		$redis_server = array(
			'host'     => $ik2_redis_parts['host'],
			'port'     => isset( $ik2_redis_parts['port'] ) ? (int) $ik2_redis_parts['port'] : 6379,
			'database' => isset( $ik2_redis_parts['path'] ) ? (int) ltrim( $ik2_redis_parts['path'], '/' ) : 0,
		);

		if ( isset( $ik2_redis_parts['pass'] ) && '' !== $ik2_redis_parts['pass'] ) {
			$ik2_redis_pass = rawurldecode( $ik2_redis_parts['pass'] );

			// Redis ACL (username + password) uses the array form; classic
			// requirepass auth is just the password string.
			if ( isset( $ik2_redis_parts['user'] ) && '' !== $ik2_redis_parts['user'] ) {
				$redis_server['auth'] = array( rawurldecode( $ik2_redis_parts['user'] ), $ik2_redis_pass );
			} else {
				$redis_server['auth'] = $ik2_redis_pass;
			}
		}
	}
}

unset( $ik2_redis_url, $ik2_redis_parts, $ik2_redis_pass );
