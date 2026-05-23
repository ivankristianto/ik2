<?php
/**
 * Title: Contact — Intro
 * Slug: ik2/contact-page-intro
 * Categories: ik2-page
 * Description: Eyebrow, big title, and lede for the Contact page.
 *
 * @package IK2
 */

?>
<!-- wp:group {"className":"ik-contact__head","layout":{"type":"default"}} -->
<div class="wp-block-group ik-contact__head">
	<!-- wp:paragraph {"className":"ik-section__eyebrow"} -->
	<p class="ik-section__eyebrow"><?php esc_html_e( '// CONTACT', 'ik2' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":1,"className":"ik-contact__title"} -->
	<h1 class="wp-block-heading ik-contact__title"><?php esc_html_e( 'Get in touch', 'ik2' ); ?></h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"className":"ik-contact__lede"} -->
	<p class="ik-contact__lede"><?php esc_html_e( 'The fastest way to reach me is email. I read everything; I reply to most of it.', 'ik2' ); ?></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
