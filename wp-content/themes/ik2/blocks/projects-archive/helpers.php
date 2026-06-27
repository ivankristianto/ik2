<?php
/**
 * Helpers for the ik2/projects-archive block render template.
 *
 * Loaded once via require_once from render.php — keeps the comparator out of
 * the per-render closure scope so it shows up in stack traces.
 *
 * @package IK2
 */

declare(strict_types=1);

namespace IK2\Theme\Blocks\ProjectsArchive;

use IK2\Plugin\PostTypes\Project;
use WP_Post;

defined( 'ABSPATH' ) || exit;

const STATUS_RANK = [
	'Active'     => 0,
	'Experiment' => 1,
	'Archived'   => 2,
];

/**
 * Order projects by status (Active → Experiment → Archived), then newest first.
 *
 * @param WP_Post $a First project.
 * @param WP_Post $b Second project.
 */
function compare_projects( WP_Post $a, WP_Post $b ): int {
	$a_status = Project\normalize_status( (string) get_post_meta( $a->ID, 'status', true ) );
	$b_status = Project\normalize_status( (string) get_post_meta( $b->ID, 'status', true ) );

	$a_rank = STATUS_RANK[ $a_status ] ?? 99;
	$b_rank = STATUS_RANK[ $b_status ] ?? 99;

	if ( $a_rank !== $b_rank ) {
		return $a_rank <=> $b_rank;
	}

	return strtotime( $b->post_date ) <=> strtotime( $a->post_date );
}
