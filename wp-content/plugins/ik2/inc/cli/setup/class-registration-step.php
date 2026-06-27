<?php
/**
 * Setup step: registration policy.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Setup;

defined( 'ABSPATH' ) || exit;

/**
 * Disables open user registration.
 */
class Registration_Step implements Setup_Step {

	/**
	 * Section heading shown above this step's checks.
	 */
	public function label(): string {
		return 'Registration';
	}

	/**
	 * Turn off users_can_register if it is on.
	 *
	 * @param bool $force Unused; this step is an idempotent option write.
	 * @return array<int, Check_Result>
	 */
	public function run( bool $force ): array {
		if ( ! get_option( 'users_can_register' ) ) {
			return [ new Check_Result( 'users_can_register', true, 'already off' ) ];
		}

		update_option( 'users_can_register', 0 );

		return [ new Check_Result( 'users_can_register', true, 'turned off' ) ];
	}
}
