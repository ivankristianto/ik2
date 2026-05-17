<?php
/**
 * Title: Home — Featured topics
 * Slug: ik2/home-featured-topics
 * Categories: ik2-home
 * Description: Topic cards rendered from real category data.
 *
 * @package IK2
 */

$ik2_topic_slugs = array( 'wordpress', 'ai', 'performance', 'web-apis', 'tooling' );
$ik2_topics      = array();

foreach ( $ik2_topic_slugs as $ik2_topic_slug ) {
	$ik2_term = get_term_by( 'slug', $ik2_topic_slug, 'category' );
	if ( $ik2_term instanceof WP_Term ) {
		$ik2_topics[] = $ik2_term;
	}
}
?>
<!-- wp:group {"className":"ik-section","layout":{"type":"constrained"}} -->
<section class="wp-block-group ik-section">
	<div class="container-full">
		<div class="ik-section__head">
			<div>
				<!-- wp:paragraph {"className":"ik-section__eyebrow"} --><p class="ik-section__eyebrow">// FEATURED TOPICS</p><!-- /wp:paragraph -->
				<!-- wp:heading {"level":2,"className":"ik-section__title"} --><h2 class="wp-block-heading ik-section__title">Where I spend my time on the web</h2><!-- /wp:heading -->
			</div>
			<!-- wp:paragraph {"className":"ik-section__more"} --><p class="ik-section__more"><a href="<?php echo esc_url( home_url( '/articles' ) ); ?>">All articles →</a></p><!-- /wp:paragraph -->
		</div>

		<!-- wp:html -->
		<div class="ik-topics">
			<?php foreach ( $ik2_topics as $ik2_topic ) : ?>
				<a class="ik-topic" href="<?php echo esc_url( get_category_link( $ik2_topic ) ); ?>">
					<div class="ik-topic__row">
						<span class="ik-topic__name"><?php echo esc_html( $ik2_topic->name ); ?></span>
						<span class="ik-topic__count"><?php echo (int) $ik2_topic->count; ?> post<?php echo 1 === (int) $ik2_topic->count ? '' : 's'; ?></span>
					</div>
					<p class="ik-topic__blurb"><?php echo esc_html( $ik2_topic->description ); ?></p>
				</a>
			<?php endforeach; ?>
		</div>
		<!-- /wp:html -->
	</div>
</section>
<!-- /wp:group -->
