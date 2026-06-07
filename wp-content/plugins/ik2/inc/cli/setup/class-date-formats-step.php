<?php
/**
 * Setup step: date formats.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Setup;

defined( 'ABSPATH' ) || exit;

/**
 * Converges the date display settings to the design brief: dates render
 * as "July 8, 2020" and the week starts on Monday.
 */
class Date_Formats_Step extends Options_Step {

	/**
	 * Section heading shown above this step's checks.
	 */
	public function label(): string {
		return 'Date formats';
	}

	/**
	 * Option name => target value manifest.
	 *
	 * @return array<string, string|int>
	 */
	protected function options(): array {
		return array(
			'date_format'   => 'F j, Y',
			'start_of_week' => 1,
		);
	}
}
