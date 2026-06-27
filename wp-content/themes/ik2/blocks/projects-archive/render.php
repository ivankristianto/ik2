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

$ik2_wrapper_attrs = get_block_wrapper_attributes(
	[ 'class' => 'ik-project-grid' ]
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
