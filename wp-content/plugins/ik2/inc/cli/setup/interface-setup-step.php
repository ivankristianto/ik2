<?php
/**
 * Contract for a single `wp ik2 setup` step.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Setup;

defined( 'ABSPATH' ) || exit;

/**
 * One unit of site setup. Steps must be idempotent: running them twice
 * without --force must not change state reported as already correct.
 */
interface Setup_Step {

	/**
	 * Section heading shown above this step's checks, e.g. "Pages".
	 */
	public function label(): string;

	/**
	 * Apply the step and report one result per check performed.
	 *
	 * Implementations must catch their own failures and turn them into
	 * failed Check_Result entries — never let an error escape.
	 *
	 * @param bool $force Re-apply state even where something already exists.
	 * @return array<int, Check_Result>
	 */
	public function run( bool $force ): array;
}
