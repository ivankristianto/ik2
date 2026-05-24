<?php
/**
 * Title: Single project — header
 * Slug: ik2/single-project-header
 * Categories: ik2-page
 * Description: Eyebrow, title, and project meta for the single project template.
 *
 * @package IK2
 */

declare( strict_types=1 );

use IK2\Plugin\PostTypes\Project;

$ik2_post_id = (int) get_the_ID();
$ik2_project = $ik2_post_id > 0 ? Project\get_card_data( $ik2_post_id ) : null;
$ik2_status  = $ik2_project['status'] ?? '';
?>
<!-- wp:group {"tagName":"header","className":"ik-article__head ik-project-single__head","layout":{"type":"default"},"metadata":{"name":"Project header"}} -->
<header class="wp-block-group ik-article__head ik-project-single__head">
	<!-- wp:post-title {"level":1,"className":"ik-article__title ik-project-single__title"} /-->

	<!-- wp:group {"className":"ik-article__meta ik-project-single__meta","layout":{"type":"flex","flexWrap":"wrap"}} -->
	<div class="wp-block-group ik-article__meta ik-project-single__meta">
		<!-- wp:post-date {"format":"F j, Y","className":"ik-article__date"} /-->

		<?php if ( '' !== $ik2_status ) : ?>
			<!-- wp:paragraph {"className":"ik-article__sep"} -->
			<p class="ik-article__sep" aria-hidden="true">·</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"className":"ik-project-single__status-wrap"} -->
			<p class="ik-project-single__status-wrap">
				<span class="ik-project__status ik-project-single__status" data-status="<?php echo esc_attr( $ik2_status ); ?>">
					<?php echo esc_html( $ik2_status ); ?>
				</span>
			</p>
			<!-- /wp:paragraph -->
		<?php endif; ?>
	</div>
	<!-- /wp:group -->
</header>
<!-- /wp:group -->
