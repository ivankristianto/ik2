<?php
/**
 * Setup step: site title and tagline.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Setup;

defined( 'ABSPATH' ) || exit;

/**
 * Sets the site title and clears the default tagline. Install-time
 * defaults are overridden; an operator-chosen title is treated as
 * deliberate and only overridden with --force.
 */
class Site_Identity_Step implements Setup_Step {

	/**
	 * Target site title.
	 */
	private const NAME = 'Ivan Kristianto';

	/**
	 * Tagline WordPress seeds on a fresh install.
	 */
	private const DEFAULT_TAGLINE = 'Just another WordPress site';

	/**
	 * Section heading shown above this step's checks.
	 */
	public function label(): string {
		return 'Site identity';
	}

	/**
	 * Converge the site title and tagline.
	 *
	 * @param bool $force Override an operator-chosen title too.
	 * @return array<int, Check_Result>
	 */
	public function run( bool $force ): array {
		return [
			$this->ensure_title( $force ),
			$this->ensure_tagline(),
		];
	}

	/**
	 * Set the site title unless a custom one is in place (and not --force).
	 *
	 * @param bool $force Override an operator-chosen title too.
	 */
	private function ensure_title( bool $force ): Check_Result {
		$current = (string) get_option( 'blogname' );

		if ( self::NAME === $current ) {
			return new Check_Result( 'blogname', true, 'already set' );
		}

		if ( ! $force && $current !== '' && $current !== 'WordPress' ) {
			return new Check_Result( 'blogname', true, sprintf( "is '%s', skipped", $current ) );
		}

		update_option( 'blogname', self::NAME );

		return new Check_Result( 'blogname', true, sprintf( "set to '%s'", self::NAME ) );
	}

	/**
	 * Clear the default tagline; keep anything the operator wrote.
	 */
	private function ensure_tagline(): Check_Result {
		$current = (string) get_option( 'blogdescription' );

		if ( self::DEFAULT_TAGLINE !== $current ) {
			return new Check_Result( 'blogdescription', true, $current === '' ? 'empty, ok' : 'custom, kept' );
		}

		update_option( 'blogdescription', '' );

		return new Check_Result( 'blogdescription', true, 'default tagline cleared' );
	}
}
