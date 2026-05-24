<?php
/**
 * Title: Articles — Archive header
 * Slug: ik2/articles-archive-header
 * Categories: ik2-archive
 * Description: Eyebrow, big title, and intro paragraph for the Articles page.
 *
 * @package IK2
 */

?>
<!-- wp:group {"className":"ik-articles-archive__head","layout":{"type":"default"}} -->
<header class="wp-block-group ik-articles-archive__head">
	<!-- wp:paragraph {"className":"ik-section__eyebrow"} -->
	<p class="ik-section__eyebrow"><?php esc_html_e( '// ARTICLES  ·  GUIDES, NOTES, EXPERIMENTS, LINKS', 'ik2' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":1,"className":"ik-articles-archive__title"} -->
	<h1 class="wp-block-heading ik-articles-archive__title"><?php esc_html_e( "Everything I've written", 'ik2' ); ?></h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"className":"ik-articles-archive__lede"} -->
	<p class="ik-articles-archive__lede"><?php esc_html_e( 'WordPress, performance, security, AI, browser APIs, and the boring devops in between. Newest first. The old posts are kept for context — they may reflect the tools and versions of their time.', 'ik2' ); ?></p>
	<!-- /wp:paragraph -->
</header>
<!-- /wp:group -->
