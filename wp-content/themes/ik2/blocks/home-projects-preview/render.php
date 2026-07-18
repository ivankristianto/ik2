<?php
/**
 * Server render for ik2/home-projects-preview.
 *
 * Renders a curated grid of `ik2/project-card` blocks. The editor picks the
 * Project IDs via the block's sidebar. The grid always shows an even count
 * (2 or 4) so the 2-column layout never leaves a dangling card: odd curated
 * counts are padded up to the next even number with the latest non-curated
 * projects, and an empty curated list falls back to the four latest.
 *
 * @package IK2
 * @var array<string,mixed> $attributes
 * @var string              $content
 * @var WP_Block            $block
 */

declare(strict_types=1);

use IK2\Plugin\PostTypes\Project;

defined( 'ABSPATH' ) || exit;

$ik2_max_projects = 4;

$ik2_curated_ids = [];
if ( isset( $attributes['projectIds'] ) && is_array( $attributes['projectIds'] ) ) {
	foreach ( $attributes['projectIds'] as $ik2_raw_id ) {
		$ik2_id = (int) $ik2_raw_id;
		if ( $ik2_id > 0 ) {
			$ik2_curated_ids[] = $ik2_id;
		}
	}
}

$ik2_project_ids = [];
foreach ( $ik2_curated_ids as $ik2_curated_id ) {
	if ( count( $ik2_project_ids ) >= $ik2_max_projects ) {
		break;
	}
	$ik2_post = get_post( $ik2_curated_id );
	if ( $ik2_post && Project\POST_TYPE === $ik2_post->post_type && $ik2_post->post_status === 'publish' ) {
		$ik2_project_ids[] = $ik2_curated_id;
	}
}

// Round up to the nearest even count (0 -> 4, 1 -> 2, 3 -> 4) by padding with
// the latest non-curated projects so the 2-column grid is never left odd.
$ik2_target_count = count( $ik2_project_ids ) === 0
	? $ik2_max_projects
	: (int) ( ceil( count( $ik2_project_ids ) / 2 ) * 2 );

if ( count( $ik2_project_ids ) < $ik2_target_count ) {
	$ik2_fill = get_posts(
		[
			'post_type'      => Project\POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => $ik2_target_count - count( $ik2_project_ids ),
			'orderby'        => 'date',
			'order'          => 'DESC',
			'fields'         => 'ids',
			'post__not_in'   => $ik2_project_ids,
		]
	);
	foreach ( $ik2_fill as $ik2_fill_id ) {
		$ik2_project_ids[] = (int) $ik2_fill_id;
	}
}

$ik2_wrapper_attrs = get_block_wrapper_attributes(
	[ 'class' => 'ik-project-grid' ]
);
?>
<div <?php echo $ik2_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( empty( $ik2_project_ids ) ) : ?>
		<p class="ik-project__blurb"><?php esc_html_e( 'No projects published yet.', 'ik2' ); ?></p>
	<?php else : ?>
		<?php
		foreach ( $ik2_project_ids as $ik2_id ) {
			// `do_blocks` returns block-rendered HTML; each ik2/project-card
			// already calls `esc_*` on every untrusted field internally.
			echo do_blocks( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				sprintf(
					'<!-- wp:ik2/project-card {"postId":%d} /-->',
					(int) $ik2_id
				)
			);
		}
		?>
	<?php endif; ?>
</div>
