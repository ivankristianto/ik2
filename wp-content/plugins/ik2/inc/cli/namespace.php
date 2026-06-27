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
	require_once __DIR__ . '/setup/class-options-step.php';
	require_once __DIR__ . '/setup/class-theme-step.php';
	require_once __DIR__ . '/setup/class-plugins-step.php';
	require_once __DIR__ . '/setup/class-pages-step.php';
	require_once __DIR__ . '/setup/class-privacy-page-step.php';
	require_once __DIR__ . '/setup/class-home-page-step.php';
	require_once __DIR__ . '/setup/class-permalinks-step.php';
	require_once __DIR__ . '/setup/class-timezone-step.php';
	require_once __DIR__ . '/setup/class-date-formats-step.php';
	require_once __DIR__ . '/setup/class-reading-step.php';
	require_once __DIR__ . '/setup/class-discussion-step.php';
	require_once __DIR__ . '/setup/class-registration-step.php';
	require_once __DIR__ . '/setup/class-site-identity-step.php';
	require_once __DIR__ . '/setup/class-object-cache-step.php';
	require_once __DIR__ . '/setup/class-sample-content-step.php';
	require_once __DIR__ . '/class-setup-command.php';
	require_once __DIR__ . '/migrate/class-migration-config.php';
	require_once __DIR__ . '/migrate/class-migration-result.php';
	require_once __DIR__ . '/migrate/class-content-rewriter.php';
	require_once __DIR__ . '/class-migrate-articles-command.php';

	\WP_CLI::add_command( 'ik2 stats', Stats_Command::class );
	\WP_CLI::add_command( 'ik2 setup', Setup_Command::class );
	\WP_CLI::add_command( 'ik2 migrate-articles', Migrate_Articles_Command::class );
}
