<?php
/**
 * Title: About profile intro
 * Slug: ik2/about-page-intro
 * Categories: ik2-page
 * Description: A profile heading, links to articles and projects, and a short facts column.
 *
 * @package IK2
 */

?>
<!-- wp:group {"tagName":"header","className":"ik-about__profile-hero","layout":{"type":"default"}} -->
<header class="wp-block-group ik-about__profile-hero">
	<!-- wp:group {"className":"ik-about__profile-intro","layout":{"type":"default"}} -->
	<div class="wp-block-group ik-about__profile-intro">
		<!-- wp:heading {"level":1,"className":"ik-about__title"} -->
		<h1 class="wp-block-heading ik-about__title">Ivan Kristianto</h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"ik-about__intro"} -->
		<p class="ik-about__intro">I build publishing platforms and write about the work. Here I share what I learn about WordPress, web performance, AI and developer tooling.</p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons {"className":"ik-about__elsewhere-actions"} -->
		<div class="wp-block-buttons ik-about__elsewhere-actions">
			<!-- wp:button {"className":"ik-btn--primary"} -->
			<div class="wp-block-button ik-btn--primary"><a class="wp-block-button__link wp-element-button" href="/articles/">Browse articles</a></div>
			<!-- /wp:button -->

			<!-- wp:button {"className":"ik-btn--secondary"} -->
			<div class="wp-block-button ik-btn--secondary"><a class="wp-block-button__link wp-element-button" href="/projects/">See projects</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"ik-about__profile-facts","layout":{"type":"default"}} -->
	<div class="wp-block-group ik-about__profile-facts">
		<!-- wp:paragraph -->
		<p><strong>Current role</strong><br>Senior Web Engineer at Human Made</p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph -->
		<p><strong>Web technology</strong><br>Recognised as a Google Developer Expert in 2018</p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph -->
		<p><strong>Writing here</strong><br>Since 2009</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</header>
<!-- /wp:group -->
