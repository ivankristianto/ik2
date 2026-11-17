<?php
/**
 * Title: Single lab entry, more from the lab
 * Slug: ik2/single-lab-more
 * Categories: ik2-page
 * Inserter: no
 * Description: The other published lab entries, newest first, so an entry never ends in a dead end. Prints nothing when this is the only one. Depends on the current entry, so it is hidden from the manual inserter.
 *
 * @package IK2
 */

use IK2\Plugin\PostTypes\Lab;

$ik2_others = defined( 'IK2\\Plugin\\PostTypes\\Lab\\POST_TYPE' )
	? get_posts(
		[
			'post_type'              => Lab\POST_TYPE,
			'post_status'            => 'publish',
			'posts_per_page'         => 5,
			'post__not_in'           => [ get_queried_object_id() ],
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		]
	)
	: [];

if ( empty( $ik2_others ) ) {
	return;
}
?>
<!-- wp:group {"tagName":"section","className":"ik-project-writing","layout":{"type":"default"},"metadata":{"name":"More from the lab"}} -->
<section class="wp-block-group ik-project-writing">
	<!-- wp:group {"className":"ik-related__head","layout":{"type":"default"}} -->
	<div class="wp-block-group ik-related__head">
		<!-- wp:heading {"level":2,"className":"ik-related__title"} -->
		<h2 class="wp-block-heading ik-related__title"><?php esc_html_e( 'More from the lab', 'ik2' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"ik-related__note"} -->
		<p class="ik-related__note"><a href="<?php echo esc_url( home_url( '/lab/' ) ); ?>"><?php esc_html_e( 'all entries', 'ik2' ); ?></a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:html -->
	<ol class="ik-project-writing__list">
	<?php foreach ( $ik2_others as $ik2_other ) : ?>
		<li class="ik-project-writing__item">
			<time class="ik-project-writing__date" datetime="<?php echo esc_attr( (string) get_the_date( 'c', $ik2_other ) ); ?>"><?php echo esc_html( (string) get_the_date( 'Y-m-d', $ik2_other ) ); ?></time>
			<a class="ik-project-writing__title" href="<?php echo esc_url( (string) get_permalink( $ik2_other ) ); ?>"><?php echo esc_html( get_the_title( $ik2_other ) ); ?></a>
		</li>
	<?php endforeach; ?>
	</ol>
	<!-- /wp:html -->
</section>
<!-- /wp:group -->
