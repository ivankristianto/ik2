<?php
/**
 * Server render for ik2/home-featured-topics.
 *
 * @package IK2
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$ik2_topic_slugs = array( 'wordpress', 'ai', 'performance', 'web-apis', 'tooling' );
$ik2_topics      = array();

foreach ( $ik2_topic_slugs as $ik2_topic_slug ) {
	$ik2_term = get_term_by( 'slug', $ik2_topic_slug, 'category' );
	if ( $ik2_term instanceof WP_Term ) {
		$ik2_topics[] = $ik2_term;
	}
}

$ik2_wrapper_attrs = get_block_wrapper_attributes(
	array( 'class' => 'container-full ik-section' )
);
?>
<section <?php echo $ik2_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="ik-section__head">
		<div>
			<p class="ik-section__eyebrow"><?php esc_html_e( '// FEATURED TOPICS', 'ik2' ); ?></p>
			<h2 class="wp-block-heading ik-section__title"><?php esc_html_e( 'Where I spend my time on the web', 'ik2' ); ?></h2>
		</div>
		<p class="ik-section__more"><a href="<?php echo esc_url( home_url( '/articles' ) ); ?>"><?php esc_html_e( 'All articles →', 'ik2' ); ?></a></p>
	</div>

	<div class="ik-topics">
		<?php foreach ( $ik2_topics as $ik2_topic ) : ?>
			<a class="ik-topic" href="<?php echo esc_url( get_category_link( $ik2_topic ) ); ?>">
				<div class="ik-topic__row">
					<span class="ik-topic__name"><?php echo esc_html( $ik2_topic->name ); ?></span>
					<span class="ik-topic__count">
						<?php
						printf(
							/* translators: %d: post count */
							esc_html( _n( '%d post', '%d posts', (int) $ik2_topic->count, 'ik2' ) ),
							(int) $ik2_topic->count
						);
						?>
					</span>
				</div>
				<p class="ik-topic__blurb"><?php echo esc_html( $ik2_topic->description ); ?></p>
			</a>
		<?php endforeach; ?>
	</div>
</section>
