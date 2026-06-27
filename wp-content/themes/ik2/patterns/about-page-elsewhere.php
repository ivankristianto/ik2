<?php
/**
 * Title: About — Elsewhere CTA
 * Slug: ik2/about-page-elsewhere
 * Categories: ik2-page
 * Description: Card with eyebrow, line of copy, and two CTAs linking to resume and contact.
 *
 * @package IK2
 */

?>
<!-- wp:group {"className":"ik-about__elsewhere","layout":{"type":"default"}} -->
<div class="wp-block-group ik-about__elsewhere">
	<!-- wp:group {"className":"ik-about__elsewhere-copy","layout":{"type":"default"}} -->
	<div class="wp-block-group ik-about__elsewhere-copy">
		<!-- wp:paragraph {"className":"ik-section__eyebrow"} -->
		<p class="ik-section__eyebrow">// ELSEWHERE</p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"className":"ik-about__elsewhere-text"} -->
		<p class="ik-about__elsewhere-text">For the recruiter-friendly version with experience, projects, and skills.</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:buttons {"className":"ik-about__elsewhere-actions"} -->
	<div class="wp-block-buttons ik-about__elsewhere-actions">
		<!-- wp:button {"className":"ik-btn--primary"} -->
		<div class="wp-block-button ik-btn--primary"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/resume/' ) ); ?>">Read Resume</a></div>
		<!-- /wp:button -->

		<!-- wp:button {"className":"ik-btn--secondary"} -->
		<div class="wp-block-button ik-btn--secondary"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Get in touch</a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
