<?php
/**
 * Title: Home — Evergreen guides
 * Slug: ik2/home-evergreen-guides
 * Categories: ik2-home
 * Description: Intro-led list of posts in the "guide" category.
 *
 * @package IK2
 */

$ik2_guide_term    = get_term_by( 'slug', 'guide', 'category' );
$ik2_guide_term_id = $ik2_guide_term instanceof WP_Term ? (int) $ik2_guide_term->term_id : 0;
?>
<!-- wp:group {"className":"ik-section ik-section--muted","layout":{"type":"default"}} -->
<section class="wp-block-group ik-section ik-section--muted">
	<div class="container-full">
		<div class="ik-guides-layout">
			<div class="ik-guides-layout__intro">
				<!-- wp:paragraph {"className":"ik-section__eyebrow"} --><p class="ik-section__eyebrow">// START HERE</p><!-- /wp:paragraph -->
				<!-- wp:heading {"level":2,"className":"ik-section__title"} --><h2 class="wp-block-heading ik-section__title">Evergreen guides</h2><!-- /wp:heading -->
				<!-- wp:paragraph {"className":"ik-guides-layout__deck"} --><p class="ik-guides-layout__deck">If you&rsquo;re new here, start with the posts that have stayed useful over time.</p><!-- /wp:paragraph -->
				<!-- wp:paragraph {"className":"ik-guides-layout__more"} --><p class="ik-guides-layout__more"><a href="<?php echo esc_url( home_url( '/articles' ) ); ?>">All guides →</a></p><!-- /wp:paragraph -->
			</div>

			<div class="ik-guides-layout__list">
				<!-- wp:query {"queryId":1,"query":{"perPage":4,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","taxQuery":{"category":[<?php echo absint( $ik2_guide_term_id ); ?>]},"inherit":false}} -->
				<div class="wp-block-query">
					<!-- wp:post-template {"className":"ik-guides-list"} -->
						<!-- wp:group {"className":"ik-guide","layout":{"type":"default"}} -->
						<article class="wp-block-group ik-guide">
							<!-- wp:post-date {"className":"ik-guide__date","format":"F j, Y"} /-->
							<!-- wp:group {"className":"ik-guide__body","layout":{"type":"default"}} -->
							<div class="wp-block-group ik-guide__body">
								<!-- wp:post-title {"isLink":true,"level":3,"className":"ik-guide__title"} /-->
								<!-- wp:post-excerpt {"className":"ik-guide__excerpt","excerptLength":24} /-->
							</div>
							<!-- /wp:group -->
						</article>
						<!-- /wp:group -->
					<!-- /wp:post-template -->

					<!-- wp:query-no-results -->
						<!-- wp:paragraph -->
						<p>No guides yet. They will appear here once posts are tagged with the <code>guide</code> category.</p>
						<!-- /wp:paragraph -->
					<!-- /wp:query-no-results -->
				</div>
				<!-- /wp:query -->
			</div>
		</div>
	</div>
</section>
<!-- /wp:group -->
