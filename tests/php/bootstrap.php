<?php
/**
 * PHPUnit bootstrap: pure modules only, no WordPress.
 *
 * @package IK2\Tests
 */

declare(strict_types=1);

require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';
require_once dirname( __DIR__, 2 ) . '/wp-content/plugins/ik2/inc/corpus/text.php';
require_once dirname( __DIR__, 2 ) . '/wp-content/plugins/ik2/inc/corpus/batch.php';
