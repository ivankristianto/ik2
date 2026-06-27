<?php
/**
 * Setup step: activate the composer-installed plugins.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Setup;

defined( 'ABSPATH' ) || exit;

/**
 * Ensures the plugins this site depends on are active. Composer installs
 * plugin code into wp-content/plugins/ but never activates anything, so
 * a fresh database starts with every plugin off.
 *
 * Dev-only tooling (Query Monitor, Create Block Theme) is activated
 * everywhere except production.
 */
class Plugins_Step implements Setup_Step {

	/**
	 * Plugin basenames to activate in every environment.
	 *
	 * This is activation policy, deliberately separate from composer.json
	 * (which only installs): wordpress-importer stays installed but
	 * inactive on purpose (use-once import tool). Adding a plugin via
	 * `composer require` does NOT activate it — add it here too.
	 */
	private const PLUGINS = [
		'two-factor/two-factor.php',
		'wordpress-seo/wp-seo.php',
		'performance-lab/load.php',
		'wp-redis/wp-redis.php',
		'ai/ai.php',
	];

	/**
	 * Plugin basenames to activate everywhere except production.
	 */
	private const DEV_PLUGINS = [
		'query-monitor/query-monitor.php',
		'create-block-theme/create-block-theme.php',
	];

	/**
	 * Section heading shown above this step's checks.
	 */
	public function label(): string {
		return 'Plugins';
	}

	/**
	 * Ensure each manifest plugin is active, one result per plugin.
	 *
	 * @param bool $force Unused; activation is an idempotent switch.
	 * @return array<int, Check_Result>
	 */
	public function run( bool $force ): array {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$results = [];

		foreach ( self::PLUGINS as $basename ) {
			$results[] = $this->ensure_active( $basename );
		}

		$is_production = wp_get_environment_type() === 'production';

		foreach ( self::DEV_PLUGINS as $basename ) {
			if ( $is_production ) {
				$results[] = new Check_Result( dirname( $basename ), true, 'dev only, skipped in production' );
				continue;
			}

			$results[] = $this->ensure_active( $basename );
		}

		return $results;
	}

	/**
	 * Activate a single plugin if it is installed and not already active.
	 *
	 * @param string $basename Plugin basename, e.g. "two-factor/two-factor.php".
	 */
	private function ensure_active( string $basename ): Check_Result {
		$slug = dirname( $basename );

		if ( is_plugin_active( $basename ) ) {
			return new Check_Result( $slug, true, 'already active' );
		}

		if ( ! file_exists( WP_PLUGIN_DIR . '/' . $basename ) ) {
			return new Check_Result( $slug, false, sprintf( 'not installed (composer require wpackagist-plugin/%s)', $slug ) );
		}

		$activated = activate_plugin( $basename );

		if ( is_wp_error( $activated ) ) {
			return new Check_Result( $slug, false, $activated->get_error_message() );
		}

		return new Check_Result( $slug, true, 'activated' );
	}
}
