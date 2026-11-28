<?php
/**
 * Plugin Name:       Disable WordPress 7.2 Random Content Redirect
 * Description:       Disables the default-on /random/ redirect endpoint introduced in WordPress 7.2 to avoid uncacheable 302 origin traffic and unintended CPT exposure.
 * Version:           0.1.0
 * Requires at least: 6.6
 * Requires PHP:      8.4
 * Author:            Ivan Kristianto
 * License:           proprietary
 *
 * @package IK2\MuPlugins
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

add_filter( 'wp_enable_random_content_redirect', '__return_false' );
