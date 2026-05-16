<?php
/**
 * IK2 theme bootstrap. Loads namespaced modules from inc/.
 *
 * @package IK2
 */

declare(strict_types=1);

namespace IK2\Theme;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/Setup.php';
require_once __DIR__ . '/inc/Assets.php';
require_once __DIR__ . '/inc/Patterns.php';
