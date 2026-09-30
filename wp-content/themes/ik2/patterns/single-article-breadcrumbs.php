<?php
/**
 * Title: Single article — breadcrumbs
 * Slug: ik2/single-article-breadcrumbs
 * Categories: ik2-page
 * Inserter: no
 * Description: Home / Articles (or Speaking for talk posts) / current-title breadcrumb trail for the single post template. Uses Yoast's breadcrumbs block when Yoast breadcrumbs are enabled, theme markup otherwise. Depends on the current post — hidden from the manual inserter.
 *
 * @package IK2
 */

use function IK2\Theme\Breadcrumbs\parent_crumb;
use function IK2\Theme\Breadcrumbs\use_yoast;

?>
<!-- wp:group {"tagName":"nav","className":"ik-crumbs","layout":{"type":"flex","flexWrap":"wrap"},"metadata":{"name":"Breadcrumbs"}} -->
<nav class="wp-block-group ik-crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'ik2' ); ?>">
<?php if ( use_yoast() ) : ?>
	<!-- wp:yoast-seo/breadcrumbs /-->
<?php else : ?>
	<?php $ik2_parent = parent_crumb(); ?>
	<!-- wp:paragraph {"className":"ik-crumbs__item"} -->
	<p class="ik-crumbs__item">
		<a class="ik-crumbs__link" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'ik2' ); ?></a>
		<span class="ik-crumbs__sep" aria-hidden="true">/</span>
	</p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph {"className":"ik-crumbs__item"} -->
	<p class="ik-crumbs__item">
		<a class="ik-crumbs__link" href="<?php echo esc_url( $ik2_parent['url'] ); ?>"><?php echo esc_html( $ik2_parent['text'] ); ?></a>
		<span class="ik-crumbs__sep" aria-hidden="true">/</span>
	</p>
	<!-- /wp:paragraph -->

	<!-- wp:post-title {"level":0,"isLink":false,"className":"ik-crumbs__current"} /-->
<?php endif; ?>
</nav>
<!-- /wp:group -->
