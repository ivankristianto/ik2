<?php
/**
 * Server render for ik2/home-speaking-preview.
 *
 * @package IK2
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$ik2_talks_query = new WP_Query(
	[
		'post_type'              => 'post',
		'posts_per_page'         => 4,
		'orderby'                => 'date',
		'order'                  => 'DESC',
		'ignore_sticky_posts'    => true,
		'category_name'          => 'talk',
		'no_found_rows'          => true,
		'update_post_term_cache' => false,
	]
);

$ik2_wrapper_attrs = get_block_wrapper_attributes(
	[ 'class' => 'container-full ik-section' ]
);
?>
<section <?php echo $ik2_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="ik-speaking-layout">
		<div class="ik-speaking-layout__intro">
			<p class="ik-section__eyebrow"><?php esc_html_e( '// SPEAKING & COMMUNITY', 'ik2' ); ?></p>
			<h2 class="wp-block-heading ik-section__title"><?php esc_html_e( 'Recent talks', 'ik2' ); ?></h2>
			<p class="ik-speaking-layout__deck"><?php esc_html_e( 'A few recent sessions on WordPress, tooling, and the community work around them.', 'ik2' ); ?></p>
			<p class="ik-speaking-layout__more"><a href="<?php echo esc_url( home_url( '/speaking' ) ); ?>"><?php esc_html_e( 'All talks →', 'ik2' ); ?></a></p>
		</div>

		<div class="ik-speaking-layout__list">
			<div class="ik-talks-list">
				<?php if ( $ik2_talks_query->have_posts() ) : ?>
					<?php
					while ( $ik2_talks_query->have_posts() ) :
						$ik2_talks_query->the_post();
						$ik2_venue = (string) get_post_meta( get_the_ID(), 'venue', true );
						$ik2_kind  = (string) get_post_meta( get_the_ID(), 'kind', true );
						?>
						<article class="ik-talk">
							<span class="ik-talk__date"><?php echo esc_html( get_the_date( 'F j, Y' ) ); ?></span>
							<div class="ik-talk__body">
								<h3 class="ik-talk__title"><a href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html( get_the_title() ); ?></a></h3>
								<?php if ( $ik2_venue !== '' ) : ?>
									<div class="ik-talk__venue"><?php echo esc_html( $ik2_venue ); ?></div>
								<?php endif; ?>
							</div>
							<?php if ( $ik2_kind !== '' ) : ?>
								<span class="ik-talk__kind"><?php echo esc_html( $ik2_kind ); ?></span>
							<?php endif; ?>
						</article>
					<?php endwhile; ?>
					<?php wp_reset_postdata(); ?>
				<?php else : ?>
					<p><?php esc_html_e( 'No talks published yet.', 'ik2' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
