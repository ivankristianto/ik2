<?php
/**
 * Server render for ik2/home-evergreen-guides.
 *
 * @package IK2
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$ik2_guides_query = new WP_Query(
	array(
		'post_type'           => 'post',
		'posts_per_page'      => 4,
		'orderby'             => 'date',
		'order'               => 'DESC',
		'ignore_sticky_posts' => true,
		'category_name'       => 'guide',
	)
);

$ik2_wrapper_attrs = get_block_wrapper_attributes(
	array( 'class' => 'ik-section ik-section--muted' )
);
?>
<section <?php echo $ik2_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="container-full ik-guides-layout">
		<div class="ik-guides-layout__intro">
			<p class="ik-section__eyebrow"><?php esc_html_e( '// START HERE', 'ik2' ); ?></p>
			<h2 class="wp-block-heading ik-section__title"><?php esc_html_e( 'Evergreen guides', 'ik2' ); ?></h2>
			<p class="ik-guides-layout__deck"><?php esc_html_e( "If you're new here, start with the posts that have stayed useful over time.", 'ik2' ); ?></p>
			<p class="ik-guides-layout__more"><a href="<?php echo esc_url( home_url( '/articles' ) ); ?>"><?php esc_html_e( 'All guides →', 'ik2' ); ?></a></p>
		</div>

		<div class="ik-guides-layout__list">
			<?php if ( $ik2_guides_query->have_posts() ) : ?>
				<div class="ik-guides-list">
					<?php
					while ( $ik2_guides_query->have_posts() ) :
						$ik2_guides_query->the_post();
						?>
						<article class="wp-block-group ik-guide">
							<div class="ik-guide__date"><?php echo esc_html( get_the_date( 'F j, Y' ) ); ?></div>
							<div class="wp-block-group ik-guide__body">
								<h3 class="ik-guide__title"><a href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html( get_the_title() ); ?></a></h3>
								<div class="ik-guide__excerpt"><?php the_excerpt(); ?></div>
							</div>
						</article>
					<?php endwhile; ?>
				</div>
				<?php wp_reset_postdata(); ?>
			<?php else : ?>
				<p><?php esc_html_e( 'No guides yet. They will appear here once posts are tagged with the guide category.', 'ik2' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</section>
