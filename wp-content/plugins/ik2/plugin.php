<?php
/**
 * Plugin Name:       IK2
 * Plugin URI:        https://www.ivankristianto.com/
 * Description:       Site-specific functionality for ivankristianto.com — custom post types, taxonomies, blocks, and backend business logic.
 * Version:           0.1.0
 * Requires at least: 6.6
 * Requires PHP:      8.4
 * Author:            Ivan Kristianto
 * Author URI:        https://www.ivankristianto.com/
 * License:           proprietary
 * Text Domain:       ik2
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin;

defined( 'ABSPATH' ) || exit;

const PLUGIN_FILE    = __FILE__;
const PLUGIN_DIR     = __DIR__;
const PLUGIN_VERSION = '0.1.0';

require_once __DIR__ . '/inc/Setup.php';
require_once __DIR__ . '/inc/Assets.php';
require_once __DIR__ . '/inc/Blocks.php';
require_once __DIR__ . '/inc/PostTypes/Project.php';
require_once __DIR__ . '/inc/PostTypes/ProjectData.php';

register_activation_hook( __FILE__, __NAMESPACE__ . '\\on_activate' );
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\\on_deactivate' );

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
