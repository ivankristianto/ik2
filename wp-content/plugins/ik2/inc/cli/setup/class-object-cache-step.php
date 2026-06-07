<?php
/**
 * Setup step: verify the Redis object cache.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Setup;

defined( 'ABSPATH' ) || exit;

/**
 * Verify-only step: the object-cache.php drop-in is baked into the
 * Docker image, so this never installs anything. It checks the drop-in
 * is present, an external cache is in use, wp-redis reports a live
 * Redis connection (not its internal fallback), and a set/get
 * roundtrip works. A broken cache fails silently in production; a
 * failed check here makes `wp ik2 setup` the smoke test.
 */
class Object_Cache_Step implements Setup_Step {

	/**
	 * Section heading shown above this step's checks.
	 */
	public function label(): string {
		return 'Object cache';
	}

	/**
	 * Run the verification checks, stopping early if a prerequisite fails.
	 *
	 * @param bool $force Unused; this step only verifies.
	 * @return array<int, Check_Result>
	 */
	public function run( bool $force ): array {
		if ( ! file_exists( WP_CONTENT_DIR . '/object-cache.php' ) ) {
			return array( new Check_Result( 'drop-in', false, 'wp-content/object-cache.php missing' ) );
		}

		if ( ! wp_using_ext_object_cache() ) {
			return array(
				new Check_Result( 'drop-in', true, 'present' ),
				new Check_Result( 'external cache', false, 'not in use' ),
			);
		}

		return array(
			new Check_Result( 'drop-in', true, 'present' ),
			new Check_Result( 'external cache', true, 'in use' ),
			$this->check_redis_connection(),
			$this->check_roundtrip(),
		);
	}

	/**
	 * Confirm the drop-in is wp-redis and its Redis connection is live.
	 */
	private function check_redis_connection(): Check_Result {
		$cache = $GLOBALS['wp_object_cache'] ?? null;

		if ( ! is_object( $cache ) || ! property_exists( $cache, 'is_redis_connected' ) ) {
			return new Check_Result( 'redis', false, 'drop-in is not wp-redis' );
		}

		if ( ! $cache->is_redis_connected ) {
			return new Check_Result( 'redis', false, 'not connected (internal fallback in use)' );
		}

		return new Check_Result( 'redis', true, 'connected' );
	}

	/**
	 * Write a probe value through the cache and read it back.
	 */
	private function check_roundtrip(): Check_Result {
		$value = (string) wp_rand();

		wp_cache_set( 'ik2_setup_probe', $value, 'ik2', 30 );
		$read = wp_cache_get( 'ik2_setup_probe', 'ik2' );
		wp_cache_delete( 'ik2_setup_probe', 'ik2' );

		if ( $read !== $value ) {
			return new Check_Result( 'roundtrip', false, 'set/get mismatch' );
		}

		return new Check_Result( 'roundtrip', true, 'set/get ok' );
	}
}
