<?php
/**
 * Server render for ik2/project-card.
 *
 * Resolves a Project ID in this order:
 *   1. Explicit `postId` attribute (used by curated previews).
 *   2. `postId` from block context (set by core query loop / post template).
 *   3. The current main query post.
 *
 * The card is the only front-end view of a Project — the post type has no
 * single template and no permalink — so the title renders as plain text.
 *
 * @package IK2\Plugin
 * @var array<string,mixed> $attributes
 * @var string              $content
 * @var WP_Block            $block
 */

declare(strict_types=1);

use IK2\Plugin\PostTypes\Project;

defined( 'ABSPATH' ) || exit;

$ik2_post_id = (int) ( $attributes['postId'] ?? 0 );
$ik2_compact = ! empty( $attributes['compact'] );

if ( $ik2_post_id <= 0 && isset( $block->context['postId'] ) ) {
	$ik2_post_id = (int) $block->context['postId'];
}

if ( $ik2_post_id <= 0 ) {
	$ik2_post_id = (int) get_the_ID();
}

$ik2_card = $ik2_post_id > 0 ? Project\get_card_data( $ik2_post_id ) : null;

if ( $ik2_card === null ) {
	return;
}

$ik2_classes = 'ik-project';
if ( $ik2_compact ) {
	$ik2_classes .= ' ik-project--compact';
}

$ik2_wrapper_attrs = get_block_wrapper_attributes(
	[
		'class'             => $ik2_classes,
		'data-status'       => $ik2_card['status'],
		'data-project-slug' => get_post_field( 'post_name', $ik2_post_id ),
	]
);
?>
<article <?php echo $ik2_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="ik-project__head">
		<h3 class="ik-project__name"><?php echo esc_html( $ik2_card['title'] ); ?></h3>
		<?php if ( $ik2_card['status'] !== '' ) : ?>
			<span class="ik-project__status" data-status="<?php echo esc_attr( $ik2_card['status'] ); ?>">
				<?php echo esc_html( $ik2_card['status'] ); ?>
			</span>
		<?php endif; ?>
	</div>

	<?php if ( $ik2_card['excerpt'] !== '' ) : ?>
		<p class="ik-project__blurb"><?php echo esc_html( $ik2_card['excerpt'] ); ?></p>
	<?php endif; ?>

	<?php if ( ! empty( $ik2_card['tech'] ) ) : ?>
		<div class="ik-project__tech">
			<?php foreach ( $ik2_card['tech'] as $ik2_tech ) : ?>
				<span><?php echo esc_html( $ik2_tech ); ?></span>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php if ( ! $ik2_compact && ! empty( $ik2_card['links'] ) ) : ?>
		<div class="ik-project__links">
			<?php foreach ( $ik2_card['links'] as $ik2_link ) : ?>
				<?php
				/*
				 * Every card repeats the same link labels ("GitHub", "Write-up"), so
				 * the label alone gives several links on the page the same name for
				 * different destinations. The project title is appended as real —
				 * visually hidden — text rather than an aria-label, so checkers that
				 * compare link text see the distinction too, and the accessible name
				 * still starts with the visible label (WCAG 2.5.3 Label in Name).
				 */
				$ik2_link_suffix = sprintf(
					/* translators: %s: project title, appended to a link label for screen readers. */
					__( ' — %s', 'ik2' ),
					$ik2_card['title']
				);
				?>
				<a href="<?php echo esc_url( $ik2_link['href'] ); ?>" rel="noopener">
					<?php echo esc_html( $ik2_link['label'] ); ?><span class="ik-project__link-context"><?php echo esc_html( $ik2_link_suffix ); ?></span> <span aria-hidden="true">&rarr;</span>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php if ( ! $ik2_compact && $ik2_card['learned'] !== '' ) : ?>
		<p class="ik-project__learned">
			<strong><?php esc_html_e( 'What I learned', 'ik2' ); ?></strong>
			<?php echo esc_html( $ik2_card['learned'] ); ?>
		</p>
	<?php endif; ?>
</article>
