<?php
/**
 * Plugin bootstrap orchestrator. Delegates to each module's bootstrap() and
 * wires the activation/deactivation lifecycle hooks.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Boot every plugin module and register lifecycle hooks.
 */
function bootstrap(): void {
	Setup\bootstrap();
	Assets\bootstrap();
	Blocks\bootstrap();
	PostTypes\Project\bootstrap();

	register_activation_hook( PLUGIN_FILE, __NAMESPACE__ . '\\on_activate' );
	register_deactivation_hook( PLUGIN_FILE, __NAMESPACE__ . '\\on_deactivate' );
}

/**
 * Activation hook: register CPTs and flush rewrite rules.
 */
function on_activate(): void {
	PostTypes\Project\register();
	flush_rewrite_rules();
}

/**
 * Deactivation hook: flush rewrite rules to remove plugin-owned routes.
 */
function on_deactivate(): void {
	flush_rewrite_rules();
}
