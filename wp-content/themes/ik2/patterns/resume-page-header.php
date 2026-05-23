<?php
/**
 * Title: Resume — Header
 * Slug: ik2/resume-page-header
 * Categories: ik2-page
 * Description: Eyebrow, name, title, location, and summary for the Resume page.
 *
 * @package IK2
 */

?>
<!-- wp:group {"className":"ik-resume__head","layout":{"type":"default"}} -->
<div class="wp-block-group ik-resume__head">
	<!-- wp:paragraph {"className":"ik-resume__eyebrow"} -->
	<p class="ik-resume__eyebrow"><?php esc_html_e( 'RESUME · UPDATED 2026', 'ik2' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":1,"className":"ik-resume__name"} -->
	<h1 class="wp-block-heading ik-resume__name"><?php esc_html_e( 'Ivan Kristianto', 'ik2' ); ?></h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"className":"ik-resume__title"} -->
	<p class="ik-resume__title"><?php esc_html_e( 'Senior Web Engineer · Google Developer Expert (Web)', 'ik2' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph {"className":"ik-resume__location"} -->
	<p class="ik-resume__location"><?php esc_html_e( 'Jakarta, Indonesia · remote', 'ik2' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph {"className":"ik-resume__summary"} -->
	<p class="ik-resume__summary"><?php esc_html_e( 'Web engineer focused on WordPress, web performance, AI-assisted workflows, and developer tooling. Twelve years building content publishing platforms, contributing to open source, and supporting the WordPress community in Indonesia through talks and events.', 'ik2' ); ?></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
