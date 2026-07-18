<?php
/**
 * Title: About — Intro
 * Slug: ik2/about-page-intro
 * Categories: ik2-page
 * Description: Eyebrow, large title, lede paragraph, and body copy for the About page.
 *
 * @package IK2
 */

?>
<!-- wp:group {"className":"ik-about__head","layout":{"type":"default"}} -->
<div class="wp-block-group ik-about__head">
	<!-- wp:paragraph {"className":"ik-section__eyebrow"} -->
	<p class="ik-section__eyebrow"><?php esc_html_e( '// ABOUT', 'ik2' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":1,"className":"ik-about__title"} -->
	<h1 class="wp-block-heading ik-about__title"><?php esc_html_e( 'About Ivan', 'ik2' ); ?></h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"className":"ik-about__intro"} -->
	<p class="ik-about__intro"><?php esc_html_e( 'I&rsquo;m a web engineer who explores WordPress, AI, performance, and developer tooling. I use this site as a public notebook for technical tutorials, experiments, talks, and notes from my work on the web platform.', 'ik2' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:group {"className":"ik-about__body","layout":{"type":"default"}} -->
	<div class="wp-block-group ik-about__body">
		<!-- wp:paragraph -->
		<p><?php esc_html_e( 'My interests sit at the intersection of publishing platforms, open-source software, browser technology, and the small tools that help developers build better user experiences.', 'ik2' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph -->
		<p><?php esc_html_e( 'I have worked on content publishing platforms for over a decade &mdash; currently as a Senior Web Engineer at Human Made, and previously at 10up. I contribute to open source, speak at WordPress and web-tech events, and have helped grow the WordPress community in Indonesia since 2015.', 'ik2' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph -->
		<p><?php esc_html_e( 'Today, I&rsquo;m focused on learning in public: building small tools, testing new browser APIs, documenting practical workflows, and sharing what I learn here.', 'ik2' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
