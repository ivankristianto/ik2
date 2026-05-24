<?php
/**
 * Title: Projects — Intro
 * Slug: ik2/projects-page-intro
 * Categories: ik2-page
 * Description: Eyebrow, large title, and lede paragraph for the Projects page.
 *
 * @package IK2
 */

?>
<!-- wp:group {"className":"ik-projects-archive__head","layout":{"type":"default"}} -->
<header class="wp-block-group ik-projects-archive__head">
	<!-- wp:paragraph {"className":"ik-section__eyebrow"} -->
	<p class="ik-section__eyebrow"><?php esc_html_e( '// PROJECTS  ·  TOOLS  ·  PLUGINS  ·  EXPERIMENTS', 'ik2' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":1,"className":"ik-projects-archive__title"} -->
	<h1 class="wp-block-heading ik-projects-archive__title"><?php esc_html_e( 'Projects', 'ik2' ); ?></h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"className":"ik-projects-archive__lede"} -->
	<p class="ik-projects-archive__lede"><?php esc_html_e( 'The things I&rsquo;ve built outside of client work &mdash; small CLI tools, the theme that powers this site, a plugin or two, and a growing pile of experiments. Some are still maintained. Some I&rsquo;ve let go. Each one taught me something.', 'ik2' ); ?></p>
	<!-- /wp:paragraph -->
</header>
<!-- /wp:group -->
