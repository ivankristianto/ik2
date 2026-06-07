<?php
/**
 * Base class for setup steps that converge a manifest of options.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Setup;

defined( 'ABSPATH' ) || exit;

/**
 * Converges each option in the manifest to its target value. Comparison
 * is string-based because WordPress stores most options as strings.
 * Always idempotent: an option already at its target reports "already set".
 */
abstract class Options_Step implements Setup_Step {

	/**
	 * Option name => target value manifest.
	 *
	 * @return array<string, string|int>
	 */
	abstract protected function options(): array;

	/**
	 * Converge each manifest option, one result per option.
	 *
	 * @param bool $force Unused; option writes are idempotent.
	 * @return array<int, Check_Result>
	 */
	public function run( bool $force ): array {
		$results = array();

		foreach ( $this->options() as $name => $value ) {
			$results[] = $this->ensure_option( $name, $value );
		}

		return $results;
	}

	/**
	 * Set a single option if it differs from the target value.
	 *
	 * @param string     $name  Option name.
	 * @param string|int $value Target value.
	 */
	private function ensure_option( string $name, string|int $value ): Check_Result {
		$current = get_option( $name );

		if ( (string) $value === (string) $current ) {
			return new Check_Result( $name, true, 'already set' );
		}

		if ( ! update_option( $name, $value ) ) {
			return new Check_Result( $name, false, sprintf( "could not set to '%s'", (string) $value ) );
		}

		return new Check_Result( $name, true, sprintf( "set to '%s'", (string) $value ) );
	}
}
