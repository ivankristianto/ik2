<?php
/**
 * WP-CLI module: registers all `wp ik2 <command>` commands.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Register the plugin's WP-CLI commands. No-op outside a WP-CLI context.
 */
function bootstrap(): void {
	if ( ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
		return;
	}

	require_once __DIR__ . '/class-stats-command.php';

	\WP_CLI::add_command( 'ik2 stats', Stats_Command::class );
}
