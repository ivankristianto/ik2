<?php
/**
 * `wp ik2 setup` — provision the site to match the theme's expectations.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI;

use IK2\Plugin\CLI\Setup\Date_Formats_Step;
use IK2\Plugin\CLI\Setup\Discussion_Step;
use IK2\Plugin\CLI\Setup\Home_Page_Step;
use IK2\Plugin\CLI\Setup\Object_Cache_Step;
use IK2\Plugin\CLI\Setup\Pages_Step;
use IK2\Plugin\CLI\Setup\Permalinks_Step;
use IK2\Plugin\CLI\Setup\Plugins_Step;
use IK2\Plugin\CLI\Setup\Privacy_Page_Step;
use IK2\Plugin\CLI\Setup\Reading_Step;
use IK2\Plugin\CLI\Setup\Registration_Step;
use IK2\Plugin\CLI\Setup\Sample_Content_Step;
use IK2\Plugin\CLI\Setup\Setup_Step;
use IK2\Plugin\CLI\Setup\Site_Identity_Step;
use IK2\Plugin\CLI\Setup\Theme_Step;
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
	 * Sets up the site: theme, plugins, pages, options, and cleanup.
	 *
	 * Activates the ik2 theme and the composer-installed plugins, creates
	 * the pages the theme templates link to, designates the privacy page,
	 * provisions the static Home front page, converges permalinks /
	 * timezone / date formats / reading /
	 * discussion / registration / site identity options, verifies the
	 * Redis object cache, and trashes WordPress's sample content. Every
	 * step is idempotent: state that already matches is skipped, so the
	 * command is safe to re-run any time to reset drift.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Re-apply state that exists but was deliberately changed: page
	 * title/slug/status (the page ID is preserved), a custom site title,
	 * a privacy page pointing at a different published page, and a front
	 * page that is unpublished or pointing at a different published page.
	 *
	 * [--only=<steps>]
	 * : Comma-separated list of steps to run, e.g. --only=plugins,pages.
	 * Case-insensitive. Valid keys: theme, plugins, pages, privacy-page,
	 * home-page, permalinks, timezone, date-formats, reading, discussion,
	 * registration, site-identity, object-cache, sample-content.
	 *
	 * [--skip=<steps>]
	 * : Comma-separated list of steps to skip. Same keys as --only.
	 *
	 * ## EXAMPLES
	 *
	 *     # Set up the site, skipping state that already matches.
	 *     $ wp ik2 setup
	 *
	 *     # Re-apply deliberately changed state too.
	 *     $ wp ik2 setup --force
	 *
	 *     # Re-run just the plugin activation and cache verification.
	 *     $ wp ik2 setup --only=plugins,object-cache
	 *
	 *     # Provision during a Redis outage without the cache verification
	 *     # failing the run.
	 *     $ wp ik2 setup --skip=object-cache
	 *
	 * @param array<int, string>    $args       Positional arguments (unused).
	 * @param array<string, string> $assoc_args Associative arguments.
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		$force = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'force', false );

		$ok     = 0;
		$failed = 0;

		foreach ( $this->filter_steps( $assoc_args ) as $step ) {
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
	 * Order matters: the theme and plugins go first so later steps see
	 * the right active state, permalinks flush after both (so plugin
	 * rewrite rules are included), and the sample-content cleanup runs
	 * last (so the privacy-page step has repointed the option off the
	 * seeded draft before it is trashed).
	 *
	 * @return array<int, Setup_Step>
	 */
	private function steps(): array {
		return [
			new Theme_Step(),
			new Plugins_Step(),
			new Pages_Step(),
			new Privacy_Page_Step(),
			new Home_Page_Step(),
			new Permalinks_Step(),
			new Timezone_Step(),
			new Date_Formats_Step(),
			new Reading_Step(),
			new Discussion_Step(),
			new Registration_Step(),
			new Site_Identity_Step(),
			new Object_Cache_Step(),
			new Sample_Content_Step(),
		];
	}

	/**
	 * Apply --only / --skip to the step registry. Step keys are the
	 * slugified labels, e.g. "Privacy page" => privacy-page.
	 *
	 * @param array<string, string> $assoc_args Associative arguments.
	 * @return array<int, Setup_Step>
	 */
	private function filter_steps( array $assoc_args ): array {
		$only = $this->parse_step_list( $assoc_args, 'only' );
		$skip = $this->parse_step_list( $assoc_args, 'skip' );

		$keyed = [];

		foreach ( $this->steps() as $step ) {
			$keyed[ sanitize_title( $step->label() ) ] = $step;
		}

		$unknown = array_diff( array_merge( $only, $skip ), array_keys( $keyed ) );

		if ( [] !== $unknown ) {
			WP_CLI::error(
				sprintf(
					'Unknown step(s): %s. Valid keys: %s.',
					implode( ', ', $unknown ),
					implode( ', ', array_keys( $keyed ) )
				)
			);
		}

		if ( [] !== $only ) {
			$keyed = array_intersect_key( $keyed, array_flip( $only ) );
		}

		$selected = array_values( array_diff_key( $keyed, array_flip( $skip ) ) );

		if ( [] === $selected ) {
			WP_CLI::error( 'No steps left to run: --skip removed everything --only selected.' );
		}

		return $selected;
	}

	/**
	 * Read a comma-separated step list from an associative argument.
	 *
	 * Tokens are normalized the same way step keys are built from labels
	 * (sanitize_title, underscores folded to hyphens), so --only=Pages or
	 * --only=Privacy-Page match the pages / privacy-page keys.
	 *
	 * @param array<string, string> $assoc_args Associative arguments.
	 * @param string                $name       Argument name (only|skip).
	 * @return array<int, string>
	 */
	private function parse_step_list( array $assoc_args, string $name ): array {
		$raw = WP_CLI\Utils\get_flag_value( $assoc_args, $name, '' );

		if ( $raw === true ) {
			WP_CLI::error( sprintf( '--%s requires a comma-separated list of step keys.', $name ) );
		}

		$tokens = [];

		foreach ( explode( ',', (string) $raw ) as $token ) {
			$token = str_replace( '_', '-', sanitize_title( $token ) );

			if ( $token !== '' ) {
				$tokens[] = $token;
			}
		}

		return $tokens;
	}
}
