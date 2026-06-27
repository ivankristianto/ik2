<?php
/**
 * Server render for ik2/home-featured-topics.
 *
 * @package IK2
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$ik2_topic_slugs = [ 'wordpress', 'ai', 'performance', 'web-apis', 'tooling' ];

// Fetch all featured topics in one query, then restore the curated slug order
// (get_terms() does not honour the order of the `slug` array).
$ik2_terms     = get_terms(
	[
		'taxonomy'   => 'category',
		'slug'       => $ik2_topic_slugs,
		'hide_empty' => false,
	]
);
$ik2_terms     = is_wp_error( $ik2_terms ) ? [] : $ik2_terms;
$ik2_terms_map = [];
foreach ( $ik2_terms as $ik2_term ) {
	$ik2_terms_map[ $ik2_term->slug ] = $ik2_term;
}

$ik2_topics = [];
foreach ( $ik2_topic_slugs as $ik2_topic_slug ) {
	if ( isset( $ik2_terms_map[ $ik2_topic_slug ] ) ) {
		$ik2_topics[] = $ik2_terms_map[ $ik2_topic_slug ];
	}
}

$ik2_wrapper_attrs = get_block_wrapper_attributes(
	[ 'class' => 'container-full ik-section' ]
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
