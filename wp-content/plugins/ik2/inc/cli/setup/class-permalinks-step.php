<?php
/**
 * Setup step: pretty permalinks.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Setup;

defined( 'ABSPATH' ) || exit;

/**
 * Sets the permalink structure to /%postname%/ and flushes rewrite rules.
 */
class Permalinks_Step implements Setup_Step {

	/**
	 * Target permalink structure.
	 */
	private const STRUCTURE = '/%postname%/';

	/**
	 * Section heading shown above this step's checks.
	 */
	public function label(): string {
		return 'Permalinks';
	}

	/**
	 * Apply the permalink structure if it differs.
	 *
	 * @param bool $force Unused; this step is an idempotent option write.
	 * @return array<int, Check_Result>
	 */
	public function run( bool $force ): array {
		$current = (string) get_option( 'permalink_structure', '' );

		if ( self::STRUCTURE === $current ) {
			return array( new Check_Result( self::STRUCTURE, true, 'already set' ) );
		}

		global $wp_rewrite;

		$wp_rewrite->set_permalink_structure( self::STRUCTURE );
		flush_rewrite_rules();

		return array( new Check_Result( self::STRUCTURE, true, 'set' ) );
	}
}
