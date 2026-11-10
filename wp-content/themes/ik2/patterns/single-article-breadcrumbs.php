<?php
/**
 * Title: Single article — breadcrumbs
 * Slug: ik2/single-article-breadcrumbs
 * Categories: ik2-page
 * Inserter: no
 * Description: Section / primary category trail above the single post title (Speaking alone for talks). The title itself is the H1, so the trail stops before it. Yoast still prints the full BreadcrumbList schema. Depends on the current post, so it is hidden from the manual inserter.
 *
 * @package IK2
 */

use function IK2\Theme\Breadcrumbs\trail;

$ik2_crumbs = trail();
$ik2_last   = count( $ik2_crumbs ) - 1;
?>
<!-- wp:group {"tagName":"nav","className":"ik-crumbs","layout":{"type":"flex","flexWrap":"wrap"},"metadata":{"name":"Breadcrumbs"}} -->
<nav class="wp-block-group ik-crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'ik2' ); ?>">
<?php foreach ( $ik2_crumbs as $ik2_index => $ik2_crumb ) : ?>
	<!-- wp:paragraph {"className":"ik-crumbs__item"} -->
	<p class="ik-crumbs__item">
		<a class="<?php echo esc_attr( 0 === $ik2_index ? 'ik-crumbs__link' : 'ik-crumbs__link ik-crumbs__link--category' ); ?>" href="<?php echo esc_url( $ik2_crumb['url'] ); ?>"><?php echo esc_html( $ik2_crumb['text'] ); ?></a>
		<?php if ( $ik2_index < $ik2_last ) : ?>
		<span class="ik-crumbs__sep" aria-hidden="true">/</span>
		<?php endif; ?>
	</p>
	<!-- /wp:paragraph -->
<?php endforeach; ?>
</nav>
<!-- /wp:group -->
