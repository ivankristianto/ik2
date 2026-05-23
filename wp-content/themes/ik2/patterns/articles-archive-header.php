<?php
/**
 * Title: Articles — Archive header
 * Slug: ik2/articles-archive-header
 * Categories: ik2-archive
 * Description: Eyebrow, big title, and intro paragraph for the Articles page.
 *
 * @package IK2
 */

$ik2_post_count = (int) wp_count_posts( 'post' )->publish;
?>
<!-- wp:group {"className":"ik-articles-archive__head","layout":{"type":"default"}} -->
<header class="wp-block-group ik-articles-archive__head">
	<!-- wp:paragraph {"className":"ik-section__eyebrow"} -->
	<p class="ik-section__eyebrow">
		<?php
		printf(
			/* translators: %d: number of posts */
			esc_html__( '// ARTICLES  ·  %d POSTS  ·  GUIDES, NOTES, EXPERIMENTS, LINKS', 'ik2' ),
			$ik2_post_count // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
		?>
	</p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":1,"className":"ik-articles-archive__title"} -->
	<h1 class="wp-block-heading ik-articles-archive__title"><?php esc_html_e( 'Everything I&rsquo;ve written', 'ik2' ); ?></h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"className":"ik-articles-archive__lede"} -->
	<p class="ik-articles-archive__lede"><?php esc_html_e( 'WordPress, performance, security, AI, browser APIs, and the boring devops in between. Newest first. The old posts are kept for context — they may reflect the tools and versions of their time.', 'ik2' ); ?></p>
	<!-- /wp:paragraph -->
</header>
<!-- /wp:group -->
