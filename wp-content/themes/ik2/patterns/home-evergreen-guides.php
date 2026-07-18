<?php
/**
 * Title: Home — Evergreen guides
 * Slug: ik2/home-evergreen-guides
 * Categories: ik2-home
 * Viewport Width: 1400
 * Description: Muted section with an intro column and a Query Loop of the latest guide posts.
 *
 * @package IK2
 */

// Omit taxQuery entirely when the category is missing (fresh install): a baked
// [0] filter would permanently show "no results" once saved into post_content.
$ik2_guide_cat = get_category_by_slug( 'guide' );
$ik2_guide_id  = $ik2_guide_cat instanceof WP_Term ? (int) $ik2_guide_cat->term_id : 0;

?>
<!-- wp:group {"tagName":"section","align":"full","className":"ik-section ik-section--muted","layout":{"type":"constrained","contentSize":"var(--wp--custom--width--full)"}} -->
<section class="wp-block-group alignfull ik-section ik-section--muted">
	<!-- wp:group {"className":"ik-guides-layout","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
	<div class="wp-block-group ik-guides-layout">
		<!-- wp:group {"className":"ik-guides-layout__intro","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
		<div class="wp-block-group ik-guides-layout__intro">
			<!-- wp:paragraph {"className":"ik-section__eyebrow"} -->
			<p class="ik-section__eyebrow">// START HERE</p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"level":2,"className":"ik-section__title"} -->
			<h2 class="wp-block-heading ik-section__title">Evergreen guides</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"ik-guides-layout__deck"} -->
			<p class="ik-guides-layout__deck">If you're new here, start with the posts that have stayed useful over time.</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"className":"ik-guides-layout__more"} -->
			<p class="ik-guides-layout__more"><a href="<?php echo esc_url( home_url( '/articles' ) ); ?>">All guides →</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"ik-guides-layout__list","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
		<div class="wp-block-group ik-guides-layout__list">
			<!-- wp:query {"queryId":10,"query":{"perPage":4,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false<?php echo $ik2_guide_id > 0 ? ',"taxQuery":{"category":[' . (int) $ik2_guide_id . ']}' : ''; ?>}} -->
			<div class="wp-block-query">
				<!-- wp:post-template {"className":"ik-guides-list","layout":{"type":"default"}} -->
				<!-- wp:group {"className":"ik-guide","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
				<div class="wp-block-group ik-guide">
					<!-- wp:post-date {"format":"F j, Y","className":"ik-guide__date"} /-->

					<!-- wp:group {"className":"ik-guide__body","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
					<div class="wp-block-group ik-guide__body">
						<!-- wp:post-title {"level":3,"isLink":true,"className":"ik-guide__title"} /-->

						<!-- wp:post-excerpt {"className":"ik-guide__excerpt"} /-->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->
				<!-- /wp:post-template -->

				<!-- wp:query-no-results -->
				<!-- wp:paragraph -->
				<p>No guides yet. They will appear here once posts are tagged with the guide category.</p>
				<!-- /wp:paragraph -->
				<!-- /wp:query-no-results -->
			</div>
			<!-- /wp:query -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
