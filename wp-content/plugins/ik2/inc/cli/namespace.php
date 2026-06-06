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
	require_once __DIR__ . '/setup/class-check-result.php';
	require_once __DIR__ . '/setup/interface-setup-step.php';
	require_once __DIR__ . '/setup/class-pages-step.php';
	require_once __DIR__ . '/setup/class-permalinks-step.php';
	require_once __DIR__ . '/setup/class-timezone-step.php';
	require_once __DIR__ . '/setup/class-registration-step.php';
	require_once __DIR__ . '/class-setup-command.php';

	\WP_CLI::add_command( 'ik2 stats', Stats_Command::class );
	\WP_CLI::add_command( 'ik2 setup', Setup_Command::class );
}
