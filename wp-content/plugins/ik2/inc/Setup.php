<?php
/**
 * Plugin-wide setup: text domain and shared bootstrapping.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin;

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	static function (): void {
		load_plugin_textdomain( 'ik2', false, dirname( plugin_basename( PLUGIN_FILE ) ) . '/languages' );
	}
);
