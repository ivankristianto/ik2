<?php
/**
 * Setup step: activate the ik2 block theme.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Setup;

defined( 'ABSPATH' ) || exit;

/**
 * Ensures the ik2 theme is active. A fresh WordPress install activates
 * the bundled default theme, so every other step would otherwise
 * provision a site that renders the wrong theme.
 */
class Theme_Step implements Setup_Step {

	/**
	 * Target theme stylesheet (directory name).
	 */
	private const THEME = 'ik2';

	/**
	 * Section heading shown above this step's checks.
	 */
	public function label(): string {
		return 'Theme';
	}

	/**
	 * Activate the theme if it is installed and not already active.
	 *
	 * @param bool $force Unused; activation is an idempotent switch.
	 * @return array<int, Check_Result>
	 */
	public function run( bool $force ): array {
		if ( self::THEME === get_stylesheet() ) {
			return [ new Check_Result( self::THEME, true, 'already active' ) ];
		}

		if ( ! wp_get_theme( self::THEME )->exists() ) {
			return [ new Check_Result( self::THEME, false, 'not installed' ) ];
		}

		switch_theme( self::THEME );

		return [ new Check_Result( self::THEME, true, 'activated' ) ];
	}
}
