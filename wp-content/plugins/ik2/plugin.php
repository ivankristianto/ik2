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

require_once __DIR__ . '/inc/namespace.php';
require_once __DIR__ . '/inc/setup.php';
require_once __DIR__ . '/inc/assets.php';
require_once __DIR__ . '/inc/blocks.php';
require_once __DIR__ . '/inc/code-highlight.php';
require_once __DIR__ . '/inc/legacy-redirects.php';
require_once __DIR__ . '/inc/llms-txt.php';
require_once __DIR__ . '/inc/cli/namespace.php';
require_once __DIR__ . '/inc/corpus/text.php';
require_once __DIR__ . '/inc/corpus/batch.php';
require_once __DIR__ . '/inc/corpus/schema.php';
require_once __DIR__ . '/inc/corpus/queue.php';
require_once __DIR__ . '/inc/corpus/related.php';
require_once __DIR__ . '/inc/corpus/report.php';
require_once __DIR__ . '/inc/corpus/admin.php';
require_once __DIR__ . '/inc/corpus/namespace.php';
require_once __DIR__ . '/inc/post-types/project.php';
require_once __DIR__ . '/inc/post-types/project-data.php';
require_once __DIR__ . '/inc/post-types/talk-meta.php';

bootstrap();
