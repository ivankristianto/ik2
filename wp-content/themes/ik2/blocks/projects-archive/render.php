<?php
/**
 * Server render for ik2/projects-archive.
 *
 * Lists every published Project as a project-card grid. Active projects come
 * first, then Experiments, then Archived; within each group, newest first.
 *
 * @package IK2
 * @var array<string,mixed> $attributes
 * @var string              $content
 * @var WP_Block            $block
 */

declare(strict_types=1);

use IK2\Plugin\PostTypes\Project;

defined( 'ABSPATH' ) || exit;

$ik2_projects = get_posts(
	array(
		'post_type'      => Project\POST_TYPE,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	)
);

$ik2_status_rank = array(
	'Active'     => 0,
	'Experiment' => 1,
	'Archived'   => 2,
);

usort(
	$ik2_projects,
	static function ( WP_Post $a, WP_Post $b ) use ( $ik2_status_rank ): int {
		$a_status = Project\normalize_status( (string) get_post_meta( $a->ID, 'status', true ) );
		$b_status = Project\normalize_status( (string) get_post_meta( $b->ID, 'status', true ) );

		$a_rank = $ik2_status_rank[ $a_status ] ?? 99;
		$b_rank = $ik2_status_rank[ $b_status ] ?? 99;

		if ( $a_rank !== $b_rank ) {
			return $a_rank <=> $b_rank;
		}

		return strtotime( $b->post_date ) <=> strtotime( $a->post_date );
	}
);

$ik2_wrapper_attrs = get_block_wrapper_attributes(
	array( 'class' => 'ik-project-grid' )
);
?>
<div <?php echo $ik2_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( empty( $ik2_projects ) ) : ?>
		<p class="ik-project__blurb"><?php esc_html_e( 'No projects published yet.', 'ik2' ); ?></p>
	<?php else : ?>
		<?php
		foreach ( $ik2_projects as $ik2_project ) {
			// `do_blocks` returns block-rendered HTML; ik2/project-card escapes
			// every untrusted value internally.
			echo do_blocks( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				sprintf(
					'<!-- wp:ik2/project-card {"postId":%d} /-->',
					(int) $ik2_project->ID
				)
			);
		}
		?>
	<?php endif; ?>
</div>
