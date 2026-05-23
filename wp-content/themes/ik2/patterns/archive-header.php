<?php
/**
 * Title: Archive — Header
 * Slug: ik2/archive-header
 * Categories: ik2-archive
 * Description: Eyebrow, title, and lede built from the queried category or tag term.
 *
 * @package IK2
 */

$ik2_term = get_queried_object();

if ( ! $ik2_term instanceof WP_Term ) {
	return;
}

$ik2_taxonomy_obj   = get_taxonomy( $ik2_term->taxonomy );
$ik2_taxonomy_label = $ik2_taxonomy_obj instanceof WP_Taxonomy
	? $ik2_taxonomy_obj->labels->singular_name
	: ucfirst( $ik2_term->taxonomy );

$ik2_count       = (int) $ik2_term->count;
$ik2_description = trim( (string) term_description( $ik2_term->term_id ) );
?>
<!-- wp:group {"className":"ik-articles-archive__head","layout":{"type":"default"}} -->
<header class="wp-block-group ik-articles-archive__head">
	<!-- wp:paragraph {"className":"ik-section__eyebrow"} -->
	<p class="ik-section__eyebrow">
		<?php
		printf(
			/* translators: 1: taxonomy label (e.g. CATEGORY), 2: number of posts, 3: term name */
			esc_html__( '// %1$s  ·  %2$d POSTS  ·  %3$s', 'ik2' ),
			esc_html( strtoupper( $ik2_taxonomy_label ) ),
			$ik2_count, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html( strtoupper( $ik2_term->name ) )
		);
		?>
	</p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":1,"className":"ik-articles-archive__title"} -->
	<h1 class="wp-block-heading ik-articles-archive__title"><?php echo esc_html( $ik2_term->name ); ?></h1>
	<!-- /wp:heading -->

	<?php if ( '' !== $ik2_description ) : ?>
		<!-- wp:paragraph {"className":"ik-articles-archive__lede"} -->
		<p class="ik-articles-archive__lede"><?php echo wp_kses_post( $ik2_description ); ?></p>
		<!-- /wp:paragraph -->
	<?php endif; ?>
</header>
<!-- /wp:group -->
