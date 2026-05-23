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
<!-- wp:html -->
<section class="ik-about__elsewhere">
	<div class="ik-about__elsewhere-copy">
		<p class="ik-section__eyebrow">// ELSEWHERE</p>
		<p class="ik-about__elsewhere-text">For the recruiter-friendly version with experience, projects, and skills.</p>
	</div>
	<div class="ik-about__elsewhere-actions">
		<a class="ik-btn ik-btn--primary" href="<?php echo esc_url( home_url( '/resume/' ) ); ?>">Read Resume</a>
		<a class="ik-btn ik-btn--secondary" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Get in touch</a>
	</div>
</section>
<!-- /wp:html -->
