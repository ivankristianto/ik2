<?php
/**
 * Title: Single lab entry, related notes
 * Slug: ik2/single-lab-notes
 * Categories: ik2-page
 * Inserter: no
 * Description: Every published post tagged with this lab entry in the post sidebar, newest first. Prints nothing when there are none. Depends on the current entry, so it is hidden from the manual inserter.
 *
 * @package IK2
 */

use IK2\Plugin\PostTypes\Lab;

$ik2_posts = function_exists( 'IK2\\Plugin\\PostTypes\\Lab\\posts_for_lab' ) ? Lab\posts_for_lab( get_queried_object_id() ) : [];

if ( empty( $ik2_posts ) ) {
	return;
}
?>
<!-- wp:group {"tagName":"section","className":"ik-project-writing","layout":{"type":"default"},"metadata":{"name":"Related notes"}} -->
<section class="wp-block-group ik-project-writing">
	<!-- wp:group {"className":"ik-related__head","layout":{"type":"default"}} -->
	<div class="wp-block-group ik-related__head">
		<!-- wp:heading {"level":2,"className":"ik-related__title"} -->
		<h2 class="wp-block-heading ik-related__title"><?php esc_html_e( 'Related notes', 'ik2' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"ik-related__note"} -->
		<p class="ik-related__note">
		<?php
		/* translators: %d: number of posts about this lab entry. */
		echo esc_html( sprintf( _n( '%d post', '%d posts', count( $ik2_posts ), 'ik2' ), count( $ik2_posts ) ) );
		?>
		</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:html -->
	<ol class="ik-project-writing__list">
	<?php foreach ( $ik2_posts as $ik2_post ) : ?>
		<li class="ik-project-writing__item">
			<time class="ik-project-writing__date" datetime="<?php echo esc_attr( (string) get_the_date( 'c', $ik2_post ) ); ?>"><?php echo esc_html( (string) get_the_date( 'Y-m-d', $ik2_post ) ); ?></time>
			<a class="ik-project-writing__title" href="<?php echo esc_url( (string) get_permalink( $ik2_post ) ); ?>"><?php echo esc_html( get_the_title( $ik2_post ) ); ?></a>
		</li>
	<?php endforeach; ?>
	</ol>
	<!-- /wp:html -->
</section>
<!-- /wp:group -->
