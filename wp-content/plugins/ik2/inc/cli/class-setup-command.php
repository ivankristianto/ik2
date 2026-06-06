<?php
/**
 * `wp ik2 setup` — provision the site to match the theme's expectations.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI;

use IK2\Plugin\CLI\Setup\Pages_Step;
use IK2\Plugin\CLI\Setup\Permalinks_Step;
use IK2\Plugin\CLI\Setup\Registration_Step;
use IK2\Plugin\CLI\Setup\Setup_Step;
use IK2\Plugin\CLI\Setup\Timezone_Step;
use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Runs an ordered list of setup steps and prints a ✓/✗ checklist.
 *
 * To add a step, create a class implementing Setup_Step under inc/cli/setup/
 * and append an instance in steps().
 */
class Setup_Command {

	/**
	 * Sets up the site: pages, permalinks, timezone, and registration.
	 *
	 * Creates the pages the theme templates link to, sets the permalink
	 * structure to /%postname%/, sets the timezone to Asia/Jakarta, and
	 * disables open registration. Existing pages are skipped unless
	 * --force is given.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Re-apply title, slug, and published status on pages that already
	 * exist (the page ID is preserved).
	 *
	 * ## EXAMPLES
	 *
	 *     # Set up the site, skipping pages that already exist.
	 *     $ wp ik2 setup
	 *
	 *     # Re-apply page state on existing pages too.
	 *     $ wp ik2 setup --force
	 *
	 * @param array<int, string>    $args       Positional arguments (unused).
	 * @param array<string, string> $assoc_args Associative arguments.
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		$force = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'force', false );

		$ok     = 0;
		$failed = 0;

		foreach ( $this->steps() as $step ) {
			WP_CLI::log( $step->label() );

			foreach ( $step->run( $force ) as $result ) {
				if ( $result->success ) {
					++$ok;
				} else {
					++$failed;
				}

				WP_CLI::log( sprintf( '  %s %s — %s', $result->success ? '✓' : '✗', $result->label, $result->note ) );
			}
		}

		$summary = sprintf( 'Setup complete: %d ok, %d failed.', $ok, $failed );

		if ( $failed > 0 ) {
			WP_CLI::error( $summary );
		}

		WP_CLI::success( $summary );
	}

	/**
	 * The ordered step registry. Append new steps here.
	 *
	 * @return array<int, Setup_Step>
	 */
	private function steps(): array {
		return array(
			new Pages_Step(),
			new Permalinks_Step(),
			new Timezone_Step(),
			new Registration_Step(),
		);
	}
}
