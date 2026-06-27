<?php
/**
 * Setup step: reading settings.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Setup;

defined( 'ABSPATH' ) || exit;

/**
 * Converges the reading settings the theme expects: posts_per_page
 * matches the 9-per-page articles grid, and blog_public guards against
 * the classic "cloned from staging, search engines still discouraged"
 * trap. The front page itself (show_on_front / page_on_front) is
 * provisioned by Home_Page_Step.
 */
class Reading_Step extends Options_Step {

	/**
	 * Section heading shown above this step's checks.
	 */
	public function label(): string {
		return 'Reading';
	}

	/**
	 * Option name => target value manifest.
	 *
	 * @return array<string, string|int>
	 */
	protected function options(): array {
		return [
			'posts_per_page' => 9,
			'blog_public'    => 1,
		];
	}
}
