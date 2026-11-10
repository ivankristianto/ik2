<?php
/**
 * Server render for ik2/projects-archive.
 *
 * Lists every published Project as ledger rows under one heading per status:
 * Active, then Experiment, then Archived, newest first within each. The
 * heading names the status, so the rows leave it out.
 *
 * @package IK2
 * @var array<string,mixed> $attributes
 * @var string              $content
 * @var WP_Block            $block
 */

declare(strict_types=1);

use IK2\Plugin\PostTypes\Project;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/helpers.php';

$ik2_projects = get_posts(
	[
		'post_type'              => Project\POST_TYPE,
		'post_status'            => 'publish',
		'posts_per_page'         => 100,
		'orderby'                => 'date',
		'order'                  => 'DESC',
		'update_post_term_cache' => false,
	]
);

// Prime the meta cache in a single query so the comparator's per-pair
// get_post_meta() calls during usort() are served from cache, not re-queried.
if ( ! empty( $ik2_projects ) ) {
	update_meta_cache( 'post', wp_list_pluck( $ik2_projects, 'ID' ) );
}

usort( $ik2_projects, 'IK2\\Theme\\Blocks\\ProjectsArchive\\compare_projects' );

$ik2_groups = [];
foreach ( $ik2_projects as $ik2_project ) {
	$ik2_status                  = Project\normalize_status( (string) get_post_meta( $ik2_project->ID, 'status', true ) );
	$ik2_groups[ $ik2_status ][] = (int) $ik2_project->ID;
}

$ik2_wrapper_attrs = get_block_wrapper_attributes(
	[ 'class' => 'ik-project-ledger' ]
);
?>
<div <?php echo $ik2_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( empty( $ik2_groups ) ) : ?>
		<p class="ik-project__blurb"><?php esc_html_e( 'No projects published yet.', 'ik2' ); ?></p>
	<?php endif; ?>

	<?php foreach ( $ik2_groups as $ik2_status => $ik2_ids ) : ?>
		<section class="ik-project-group" data-status="<?php echo esc_attr( (string) $ik2_status ); ?>">
			<h2 class="ik-project-group__title"><span class="ik-project-group__status"><?php echo esc_html( (string) $ik2_status ); ?></span> <span class="ik-project-group__count"><?php echo esc_html( (string) count( $ik2_ids ) ); ?></span></h2>
			<div class="ik-project-list">
				<?php
				foreach ( $ik2_ids as $ik2_id ) {
					// `do_blocks` returns block-rendered HTML; ik2/project-card escapes
					// every untrusted value internally.
					echo do_blocks( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						sprintf(
							'<!-- wp:ik2/project-card {"postId":%d,"showStatus":false} /-->',
							$ik2_id
						)
					);
				}
				?>
			</div>
		</section>
	<?php endforeach; ?>
</div>
