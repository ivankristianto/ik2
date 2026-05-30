<?php
/**
 * IK2 theme bootstrap. Loads namespaced modules from inc/.
 *
 * @package IK2
 */

declare(strict_types=1);

namespace IK2\Theme;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/namespace.php';
require_once __DIR__ . '/inc/setup.php';
require_once __DIR__ . '/inc/assets.php';
require_once __DIR__ . '/inc/patterns.php';
require_once __DIR__ . '/inc/blocks.php';
require_once __DIR__ . '/inc/block-styles.php';
require_once __DIR__ . '/inc/navigation.php';

bootstrap();
