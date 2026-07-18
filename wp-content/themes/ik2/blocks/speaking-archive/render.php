<?php
/**
 * Server render for ik2/speaking-archive.
 *
 * Lists every published talk (posts in the `talk` category) as a dated
 * timeline, newest first. Row markup mirrors ik2/home-speaking-preview so the
 * homepage teaser and the full Speaking page stay visually identical; the only
 * difference is the heading level (h2 here, under the page h1).
 *
 * @package IK2
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$ik2_per_page      = isset( $attributes['perPage'] ) ? max( 1, (int) $attributes['perPage'] ) : 100;
$ik2_heading_level = isset( $attributes['headingLevel'] ) ? (int) $attributes['headingLevel'] : 2;
$ik2_heading_tag   = in_array( $ik2_heading_level, [ 2, 3 ], true ) ? "h{$ik2_heading_level}" : 'h2';

$ik2_talks_query = new WP_Query(
	[
		'post_type'              => 'post',
		'posts_per_page'         => $ik2_per_page,
		'orderby'                => 'date',
		'order'                  => 'DESC',
		'ignore_sticky_posts'    => true,
		'category_name'          => 'talk',
		'no_found_rows'          => true,
		'update_post_term_cache' => false,
	]
);

$ik2_wrapper_attrs = get_block_wrapper_attributes(
	[ 'class' => 'ik-talks-list' ]
);
?>
<div <?php echo $ik2_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
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
					<<?php echo $ik2_heading_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- whitelisted h2/h3. ?> class="ik-talk__title"><a href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html( get_the_title() ); ?></a></<?php echo $ik2_heading_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
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
		<p class="ik-talks-list__empty"><?php esc_html_e( 'No talks published yet.', 'ik2' ); ?></p>
	<?php endif; ?>
</div>
