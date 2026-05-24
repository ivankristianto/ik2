<?php
/**
 * Title: Single project — header
 * Slug: ik2/single-project-header
 * Categories: ik2-page
 * Description: Title and project meta line for the single project template.
 *
 * @package IK2
 */

declare( strict_types=1 );

use IK2\Plugin\PostTypes\Project;

$ik2_project = Project\get_card_data( get_the_ID() );
?>
<!-- wp:group {"tagName":"header","className":"ik-article__head","layout":{"type":"default"},"metadata":{"name":"Project header"}} -->
<header class="wp-block-group ik-article__head">
	<!-- wp:post-title {"level":1,"className":"ik-article__title"} /-->

	<!-- wp:group {"className":"ik-article__meta ik-project-single__meta","layout":{"type":"flex","flexWrap":"wrap"}} -->
	<div class="wp-block-group ik-article__meta ik-project-single__meta">
		<!-- wp:post-date {"format":"F j, Y","className":"ik-article__date"} /-->

		<?php if ( ! empty( $ik2_project['status'] ) ) : ?>
			<!-- wp:paragraph {"className":"ik-article__sep"} -->
			<p class="ik-article__sep" aria-hidden="true">·</p>
			<!-- /wp:paragraph -->

			<p><span class="ik-project__status" data-status="<?php echo esc_attr( $ik2_project['status'] ); ?>"><?php echo esc_html( $ik2_project['status'] ); ?></span></p>
		<?php endif; ?>
	</div>
	<!-- /wp:group -->
</header>
<!-- /wp:group -->
