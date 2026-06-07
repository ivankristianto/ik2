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

		// Flush in a fresh process, not in-process: plugins the plugins
		// step activated earlier in this run were included but their init
		// hooks never fired here, so an in-process flush_rewrite_rules()
		// would bake rules that miss theirs (e.g. Yoast sitemaps) — and a
		// re-run would never heal it, because the structure then matches
		// and this branch is skipped.
		$flush = \WP_CLI::runcommand(
			'rewrite flush',
			array(
				'launch'     => true,
				'exit_error' => false,
				'return'     => 'all',
			)
		);

		if ( 0 !== $flush->return_code ) {
			return array( new Check_Result( self::STRUCTURE, false, 'set, but rewrite flush failed' ) );
		}

		return array( new Check_Result( self::STRUCTURE, true, 'set and flushed' ) );
	}
}
