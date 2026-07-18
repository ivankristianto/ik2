<?php
/**
 * Title: Home — Featured topics
 * Slug: ik2/home-featured-topics
 * Categories: ik2-home
 * Viewport Width: 1400
 * Description: Section header plus the grid of featured topic cards with live post counts.
 *
 * @package IK2
 */

?>
<!-- wp:group {"tagName":"section","align":"full","className":"ik-section","layout":{"type":"constrained","contentSize":"var(--wp--custom--width--full)"}} -->
<section class="wp-block-group alignfull ik-section">
	<!-- wp:group {"className":"ik-section__head","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
	<div class="wp-block-group ik-section__head">
		<!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
		<div class="wp-block-group">
			<!-- wp:paragraph {"className":"ik-section__eyebrow"} -->
			<p class="ik-section__eyebrow">// FEATURED TOPICS</p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"level":2,"className":"ik-section__title"} -->
			<h2 class="wp-block-heading ik-section__title">Where I spend my time on the web</h2>
			<!-- /wp:heading -->
		</div>
		<!-- /wp:group -->

		<!-- wp:paragraph {"className":"ik-section__more"} -->
		<p class="ik-section__more"><a href="<?php echo esc_url( home_url( '/articles' ) ); ?>">All articles →</a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:ik2/home-featured-topics /-->
</section>
<!-- /wp:group -->
