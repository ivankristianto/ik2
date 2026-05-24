<?php
/**
 * Server render for ik2/home-latest-notes.
 *
 * @package IK2
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$ik2_notes_query = new WP_Query(
	array(
		'post_type'           => 'post',
		'posts_per_page'      => 6,
		'orderby'             => 'date',
		'order'               => 'DESC',
		'ignore_sticky_posts' => true,
		'category_name'       => 'note',
	)
);

$ik2_wrapper_attrs = get_block_wrapper_attributes(
	array( 'class' => 'container-full ik-section' )
);
?>
<section <?php echo $ik2_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="ik-section__head">
		<div>
			<p class="ik-section__eyebrow"><?php esc_html_e( '// LATEST NOTES · TIL · EXPERIMENTS · LINKS', 'ik2' ); ?></p>
			<h2 class="wp-block-heading ik-section__title"><?php esc_html_e( "What I've been working on", 'ik2' ); ?></h2>
		</div>
		<p class="ik-section__more"><a href="<?php echo esc_url( home_url( '/articles' ) ); ?>"><?php esc_html_e( 'All articles →', 'ik2' ); ?></a></p>
	</div>

	<div class="wp-block-columns ik-notes-layout">
		<div class="wp-block-column ik-notes-layout__main">
			<?php if ( $ik2_notes_query->have_posts() ) : ?>
				<div class="ik-notes-list">
					<?php
					while ( $ik2_notes_query->have_posts() ) :
						$ik2_notes_query->the_post();
						?>
						<article class="wp-block-group ik-note">
							<div class="ik-note__date"><?php echo esc_html( get_the_date( 'F j, Y' ) ); ?></div>
							<div class="wp-block-group ik-note__body">
								<h3 class="ik-note__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
								<div class="ik-note__excerpt"><?php the_excerpt(); ?></div>
							</div>
						</article>
					<?php endwhile; ?>
				</div>
				<?php wp_reset_postdata(); ?>
			<?php else : ?>
				<p><?php esc_html_e( 'No notes yet.', 'ik2' ); ?></p>
			<?php endif; ?>
			<p class="ik-notes-layout__more"><a href="<?php echo esc_url( home_url( '/articles' ) ); ?>"><?php esc_html_e( 'Read every note →', 'ik2' ); ?></a></p>
		</div>

		<div class="wp-block-column ik-notes-layout__aside">
			<aside class="ik-now" aria-label="<?php esc_attr_e( 'What Ivan is doing right now', 'ik2' ); ?>">
				<header class="ik-now__head">
					<span class="ik-now__dot" aria-hidden="true"></span>
					<span class="ik-now__label">// /now</span>
					<span class="ik-now__date">May 12, 2026</span>
				</header>
				<div class="ik-now__group">
					<div class="ik-now__group-title"><?php esc_html_e( 'Currently building', 'ik2' ); ?></div>
					<div class="ik-now__item"><code>ivankristianto-theme</code> &mdash; <?php esc_html_e( 'rebuilding this site as a block theme.', 'ik2' ); ?></div>
				</div>
				<div class="ik-now__group">
					<div class="ik-now__group-title"><?php esc_html_e( 'Currently reading', 'ik2' ); ?></div>
					<div class="ik-now__item"><em>Designing Data-Intensive Applications</em>, Kleppmann &mdash; <?php esc_html_e( 'for the third time.', 'ik2' ); ?></div>
				</div>
				<div class="ik-now__group">
					<div class="ik-now__group-title"><?php esc_html_e( 'Currently learning', 'ik2' ); ?></div>
					<div class="ik-now__item"><?php esc_html_e( 'The WordPress Interactivity API — instant search + a real command palette.', 'ik2' ); ?></div>
				</div>
				<div class="ik-now__group">
					<div class="ik-now__group-title"><?php esc_html_e( 'Listening', 'ik2' ); ?></div>
					<div class="ik-now__item"><?php esc_html_e( 'The Changelog · Syntax.fm · WP Tavern Jukebox', 'ik2' ); ?></div>
				</div>
				<footer class="ik-now__foot">
					<?php esc_html_e( 'Inspired by ', 'ik2' ); ?>
					<a href="https://nownownow.com" target="_blank" rel="noreferrer">/now</a>.
					<?php esc_html_e( ' Updated when the world changes — not on a schedule.', 'ik2' ); ?>
				</footer>
			</aside>
		</div>
	</div>
</section>
