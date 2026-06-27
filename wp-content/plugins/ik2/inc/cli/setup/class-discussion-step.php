<?php
/**
 * Setup step: discussion settings.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Setup;

defined( 'ABSPATH' ) || exit;

/**
 * Converges the comment policy for a personal blog: pingbacks and
 * trackbacks off in both directions, and first-time commenters held
 * for moderation.
 */
class Discussion_Step extends Options_Step {

	/**
	 * Section heading shown above this step's checks.
	 */
	public function label(): string {
		return 'Discussion';
	}

	/**
	 * Option name => target value manifest.
	 *
	 * @return array<string, string|int>
	 */
	protected function options(): array {
		return [
			'default_ping_status'         => 'closed',
			'default_pingback_flag'       => 0,
			'comment_previously_approved' => 1,
		];
	}
}
