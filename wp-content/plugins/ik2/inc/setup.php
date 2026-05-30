<?php
/**
 * Plugin-wide setup: text domain and shared bootstrapping.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\Setup;

use const IK2\Plugin\PLUGIN_FILE;

defined( 'ABSPATH' ) || exit;

/**
 * Register hooks owned by this module.
 */
function bootstrap(): void {
	add_action( 'init', __NAMESPACE__ . '\\load_textdomain' );
}

/**
 * Load the plugin text domain from /languages.
 */
function load_textdomain(): void {
	load_plugin_textdomain( 'ik2', false, dirname( plugin_basename( PLUGIN_FILE ) ) . '/languages' );
}
