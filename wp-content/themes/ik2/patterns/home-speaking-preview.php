<?php
/**
 * Title: Home — Speaking preview
 * Slug: ik2/home-speaking-preview
 * Categories: ik2-home
 * Description: Four most recent talks pulled from the "talk" category.
 *
 * @package IK2
 */

$ik2_talks_query = new WP_Query(
	array(
		'post_type'           => 'post',
		'posts_per_page'      => 4,
		'orderby'             => 'date',
		'order'               => 'DESC',
		'ignore_sticky_posts' => true,
		'tax_query'           => array(
			array(
				'taxonomy' => 'category',
				'field'    => 'slug',
				'terms'    => 'talk',
			),
		),
	)
);
?>
<!-- wp:group {"tagName":"section","className":"container-full ik-section","layout":{"type":"default"}} -->
<section class="wp-block-group container-full ik-section">
	<div class="ik-speaking-layout">
		<div class="ik-speaking-layout__intro">
			<!-- wp:paragraph {"className":"ik-section__eyebrow"} --><p class="ik-section__eyebrow">// SPEAKING &amp; COMMUNITY</p><!-- /wp:paragraph -->
			<!-- wp:heading {"level":2,"className":"ik-section__title"} --><h2 class="wp-block-heading ik-section__title">Recent talks</h2><!-- /wp:heading -->
			<!-- wp:paragraph {"className":"ik-speaking-layout__deck"} --><p class="ik-speaking-layout__deck">A few recent sessions on WordPress, tooling, and the community work around them.</p><!-- /wp:paragraph -->
			<!-- wp:paragraph {"className":"ik-speaking-layout__more"} --><p class="ik-speaking-layout__more"><a href="<?php echo esc_url( home_url( '/speaking' ) ); ?>">All talks →</a></p><!-- /wp:paragraph -->
		</div>

		<div class="ik-speaking-layout__list">
			<!-- wp:html -->
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
								<h3 class="ik-talk__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
								<?php if ( '' !== $ik2_venue ) : ?>
									<div class="ik-talk__venue"><?php echo esc_html( $ik2_venue ); ?></div>
								<?php endif; ?>
							</div>
							<?php if ( '' !== $ik2_kind ) : ?>
								<span class="ik-talk__kind"><?php echo esc_html( $ik2_kind ); ?></span>
							<?php endif; ?>
						</article>
					<?php endwhile; ?>
					<?php wp_reset_postdata(); ?>
				<?php else : ?>
					<p>No talks published yet.</p>
				<?php endif; ?>
			</div>
			<!-- /wp:html -->
		</div>
	</div>
</section>
<!-- /wp:group -->
