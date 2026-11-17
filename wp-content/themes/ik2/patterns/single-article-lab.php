<?php
/**
 * Title: Single article, lab entry
 * Slug: ik2/single-article-lab
 * Categories: ik2-page
 * Inserter: no
 * Description: Links the post to each lab entry it is tagged with in the post sidebar, under the tags. Prints nothing for untagged posts. Depends on the current post, so it is hidden from the manual inserter.
 *
 * @package IK2
 */

use IK2\Plugin\PostTypes\Lab;

$ik2_labs = function_exists( 'IK2\\Plugin\\PostTypes\\Lab\\labs_for_post' ) ? Lab\labs_for_post( get_queried_object_id() ) : [];

if ( empty( $ik2_labs ) ) {
	return;
}
?>
<!-- wp:html -->
<aside class="ik-article-project" aria-label="<?php esc_attr_e( 'Lab', 'ik2' ); ?>">
<?php foreach ( $ik2_labs as $ik2_lab ) : ?>
	<div class="ik-article-project__item">
		<p class="ik-article-project__label"><?php esc_html_e( 'lab', 'ik2' ); ?></p>
		<a class="ik-article-project__name" href="<?php echo esc_url( (string) get_permalink( $ik2_lab ) ); ?>"><?php echo esc_html( get_the_title( $ik2_lab ) ); ?></a>
		<?php if ( has_excerpt( $ik2_lab ) ) : ?>
		<p class="ik-article-project__blurb"><?php echo esc_html( get_the_excerpt( $ik2_lab ) ); ?></p>
		<?php endif; ?>
	</div>
<?php endforeach; ?>
</aside>
<!-- /wp:html -->
