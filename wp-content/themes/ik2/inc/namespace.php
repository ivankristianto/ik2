<?php
/**
 * Theme bootstrap orchestrator. Delegates to each module's bootstrap().
 *
 * @package IK2
 */

declare(strict_types=1);

namespace IK2\Theme;

defined( 'ABSPATH' ) || exit;

/**
 * Boot every theme module by delegating to its own bootstrap().
 */
function bootstrap(): void {
	Setup\bootstrap();
	Assets\bootstrap();
	Patterns\bootstrap();
	Blocks\bootstrap();
	BlockStyles\bootstrap();
	Navigation\bootstrap();
}
