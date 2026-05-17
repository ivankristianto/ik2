<?php
/**
 * Title: Home — Projects preview
 * Slug: ik2/home-projects-preview
 * Categories: ik2-home
 * Description: Three-card grid of recent projects pulled from the "project" category.
 *
 * @package IK2
 */

$ik2_status_map = array(
	'Active'     => 'active',
	'Experiment' => 'exploring',
	'Archived'   => 'archived',
);

$ik2_projects_query = new WP_Query(
	array(
		'post_type'           => 'post',
		'posts_per_page'      => 3,
		'orderby'             => 'date',
		'order'               => 'DESC',
		'ignore_sticky_posts' => true,
		'tax_query'           => array(
			array(
				'taxonomy' => 'category',
				'field'    => 'slug',
				'terms'    => 'project',
			),
		),
	)
);
?>
<!-- wp:group {"className":"ik-section ik-section--muted","layout":{"type":"default"}} -->
<section class="wp-block-group ik-section ik-section--muted">
	<div class="container-full">
		<div class="ik-section__head">
			<div>
				<!-- wp:paragraph {"className":"ik-section__eyebrow"} --><p class="ik-section__eyebrow">// THINGS I&rsquo;VE BUILT</p><!-- /wp:paragraph -->
				<!-- wp:heading {"level":2,"className":"ik-section__title"} --><h2 class="wp-block-heading ik-section__title">Projects</h2><!-- /wp:heading -->
			</div>
			<!-- wp:paragraph {"className":"ik-section__more"} --><p class="ik-section__more"><a href="<?php echo esc_url( home_url( '/projects' ) ); ?>">All projects →</a></p><!-- /wp:paragraph -->
		</div>

		<!-- wp:html -->
		<div class="ik-project-grid">
			<?php if ( $ik2_projects_query->have_posts() ) : ?>
				<?php
				while ( $ik2_projects_query->have_posts() ) :
					$ik2_projects_query->the_post();
					$ik2_status_label = (string) get_post_meta( get_the_ID(), 'status', true );
					$ik2_status_slug  = $ik2_status_map[ $ik2_status_label ] ?? strtolower( $ik2_status_label );
					$ik2_tech_raw     = (string) get_post_meta( get_the_ID(), 'tech', true );
					$ik2_tech_items   = array_filter( array_map( 'trim', explode( '·', $ik2_tech_raw ) ) );
					?>
					<article class="ik-project">
						<div class="ik-project__head">
							<h3 class="ik-project__name"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
							<?php if ( '' !== $ik2_status_label ) : ?>
								<span class="ik-project__status" data-status="<?php echo esc_attr( $ik2_status_slug ); ?>"><?php echo esc_html( strtolower( $ik2_status_label ) ); ?></span>
							<?php endif; ?>
						</div>
						<p class="ik-project__blurb"><?php echo esc_html( get_the_excerpt() ); ?></p>
						<?php if ( ! empty( $ik2_tech_items ) ) : ?>
							<div class="ik-project__tech">
								<?php foreach ( $ik2_tech_items as $ik2_tech_item ) : ?>
									<span><?php echo esc_html( $ik2_tech_item ); ?></span>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</article>
				<?php endwhile; ?>
				<?php wp_reset_postdata(); ?>
			<?php else : ?>
				<p class="ik-project__blurb">No projects published yet.</p>
			<?php endif; ?>
		</div>
		<!-- /wp:html -->
	</div>
</section>
<!-- /wp:group -->
