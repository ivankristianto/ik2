<?php
/**
 * Setup step: site timezone.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Setup;

defined( 'ABSPATH' ) || exit;

/**
 * Sets the site timezone to Asia/Jakarta.
 */
class Timezone_Step implements Setup_Step {

	/**
	 * Target timezone identifier.
	 */
	private const TIMEZONE = 'Asia/Jakarta';

	/**
	 * Section heading shown above this step's checks.
	 */
	public function label(): string {
		return 'Timezone';
	}

	/**
	 * Apply the timezone if it differs; clear any manual GMT offset so
	 * the named timezone takes precedence.
	 *
	 * @param bool $force Unused; this step is an idempotent option write.
	 * @return array<int, Check_Result>
	 */
	public function run( bool $force ): array {
		if ( self::TIMEZONE === get_option( 'timezone_string' ) ) {
			return array( new Check_Result( self::TIMEZONE, true, 'already set' ) );
		}

		update_option( 'timezone_string', self::TIMEZONE );
		update_option( 'gmt_offset', '' );

		return array( new Check_Result( self::TIMEZONE, true, 'set' ) );
	}
}
