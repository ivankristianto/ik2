<?php
/**
 * Title: Home — Evergreen guides
 * Slug: ik2/home-evergreen-guides
 * Categories: ik2-home
 * Description: 2-column Query Loop of posts in the "guide" category.
 *
 * @package IK2
 */

$ik2_guide_term    = get_term_by( 'slug', 'guide', 'category' );
$ik2_guide_term_id = $ik2_guide_term instanceof WP_Term ? (int) $ik2_guide_term->term_id : 0;
?>
<!-- wp:group {"className":"ik-section ik-section--muted","layout":{"type":"default"}} -->
<section class="wp-block-group ik-section ik-section--muted">
	<div class="container-full">
		<div class="ik-section__head">
			<div>
				<!-- wp:paragraph {"className":"ik-section__eyebrow"} --><p class="ik-section__eyebrow">// START HERE</p><!-- /wp:paragraph -->
				<!-- wp:heading {"level":2,"className":"ik-section__title"} --><h2 class="wp-block-heading ik-section__title">Evergreen guides</h2><!-- /wp:heading -->
			</div>
			<!-- wp:paragraph {"className":"ik-section__more"} --><p class="ik-section__more"><a href="<?php echo esc_url( home_url( '/articles' ) ); ?>">All guides →</a></p><!-- /wp:paragraph -->
		</div>

		<!-- wp:query {"queryId":1,"query":{"perPage":4,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","taxQuery":{"category":[<?php echo absint( $ik2_guide_term_id ); ?>]},"inherit":false}} -->
		<div class="wp-block-query">
			<!-- wp:post-template {"className":"ik-grid-2"} -->
				<!-- wp:group {"className":"ik-guide","layout":{"type":"constrained"}} -->
				<article class="wp-block-group ik-guide">
					<!-- wp:post-title {"isLink":true,"level":3,"className":"ik-guide__title"} /-->
					<!-- wp:post-excerpt {"className":"ik-guide__excerpt","excerptLength":28} /-->
					<!-- wp:post-date {"className":"ik-guide__meta","format":"F j, Y"} /-->
				</article>
				<!-- /wp:group -->
			<!-- /wp:post-template -->

			<!-- wp:query-no-results -->
				<!-- wp:paragraph -->
				<p>No guides yet — they will appear here once posts are tagged with the <code>guide</code> category.</p>
				<!-- /wp:paragraph -->
			<!-- /wp:query-no-results -->
		</div>
		<!-- /wp:query -->
	</div>
</section>
<!-- /wp:group -->
