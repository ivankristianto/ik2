<?php
/**
 * Title: Home — Projects
 * Slug: ik2/home-projects
 * Categories: ik2-home
 * Viewport Width: 1400
 * Description: Muted section with a header and the curated Projects grid.
 *
 * @package IK2
 */

?>
<!-- wp:group {"tagName":"section","align":"full","className":"ik-section ik-section--muted","layout":{"type":"constrained","contentSize":"var(--wp--custom--width--full)"}} -->
<section class="wp-block-group alignfull ik-section ik-section--muted">
	<!-- wp:group {"className":"ik-section__head","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
	<div class="wp-block-group ik-section__head">
		<!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
		<div class="wp-block-group">
			<!-- wp:paragraph {"className":"ik-section__eyebrow"} -->
			<p class="ik-section__eyebrow">// THINGS I'VE BUILT</p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"level":2,"className":"ik-section__title"} -->
			<h2 class="wp-block-heading ik-section__title">Projects</h2>
			<!-- /wp:heading -->
		</div>
		<!-- /wp:group -->

		<!-- wp:paragraph {"className":"ik-section__more"} -->
		<p class="ik-section__more"><a href="<?php echo esc_url( home_url( '/projects' ) ); ?>">All projects →</a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:ik2/home-projects-preview /-->
</section>
<!-- /wp:group -->
