<?php
/**
 * Title: Single project — breadcrumbs
 * Slug: ik2/single-project-breadcrumbs
 * Categories: ik2-page
 * Description: Home / Projects / current-title breadcrumb trail for the single project template.
 *
 * @package IK2
 */

?>
<!-- wp:group {"tagName":"nav","className":"ik-crumbs","layout":{"type":"flex","flexWrap":"wrap"},"metadata":{"name":"Breadcrumbs"}} -->
<nav class="wp-block-group ik-crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'ik2' ); ?>">
	<!-- wp:paragraph {"className":"ik-crumbs__item"} -->
	<p class="ik-crumbs__item">
		<a class="ik-crumbs__link" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'ik2' ); ?></a>
		<span class="ik-crumbs__sep" aria-hidden="true">/</span>
	</p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph {"className":"ik-crumbs__item"} -->
	<p class="ik-crumbs__item">
		<a class="ik-crumbs__link" href="<?php echo esc_url( home_url( '/projects/' ) ); ?>"><?php esc_html_e( 'Projects', 'ik2' ); ?></a>
		<span class="ik-crumbs__sep" aria-hidden="true">/</span>
	</p>
	<!-- /wp:paragraph -->

	<!-- wp:post-title {"level":0,"isLink":false,"className":"ik-crumbs__current"} /-->
</nav>
<!-- /wp:group -->
