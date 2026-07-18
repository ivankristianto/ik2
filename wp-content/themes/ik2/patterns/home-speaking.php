<?php
/**
 * Title: Home — Speaking
 * Slug: ik2/home-speaking
 * Categories: ik2-home
 * Viewport Width: 1400
 * Description: Intro column beside the four most recent talks.
 *
 * @package IK2
 */

?>
<!-- wp:group {"tagName":"section","align":"full","className":"ik-section","layout":{"type":"constrained","contentSize":"var(--wp--custom--width--full)"}} -->
<section class="wp-block-group alignfull ik-section">
	<!-- wp:group {"className":"ik-speaking-layout","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
	<div class="wp-block-group ik-speaking-layout">
		<!-- wp:group {"className":"ik-speaking-layout__intro","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
		<div class="wp-block-group ik-speaking-layout__intro">
			<!-- wp:paragraph {"className":"ik-section__eyebrow"} -->
			<p class="ik-section__eyebrow">// SPEAKING &amp; COMMUNITY</p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"level":2,"className":"ik-section__title"} -->
			<h2 class="wp-block-heading ik-section__title">Recent talks</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"ik-speaking-layout__deck"} -->
			<p class="ik-speaking-layout__deck">A few recent sessions on WordPress, tooling, and the community work around them.</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"className":"ik-speaking-layout__more"} -->
			<p class="ik-speaking-layout__more"><a href="<?php echo esc_url( home_url( '/speaking' ) ); ?>">All talks →</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"ik-speaking-layout__list","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
		<div class="wp-block-group ik-speaking-layout__list">
			<!-- wp:ik2/speaking-archive {"perPage":4,"headingLevel":3} /-->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
