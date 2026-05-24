<?php
/**
 * Server render for ik2/project-card.
 *
 * Resolves a Project ID in this order:
 *   1. Explicit `postId` attribute (used by curated previews).
 *   2. `postId` from block context (set by core query loop / post template).
 *   3. The current main query post (single-project context).
 *
 * Variants:
 *   - `default`: standard project card (h3 title link + status + excerpt + tech + links + learned).
 *   - `feature`: hero summary used on the single-project template. Suppresses the
 *     duplicate h3 title and inline status pill (the surrounding template already
 *     supplies an h1 + status in the page header) and promotes the excerpt to a
 *     full-width lede.
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
$ik2_variant = isset( $attributes['variant'] ) && 'feature' === $attributes['variant'] ? 'feature' : 'default';
$ik2_feature = 'feature' === $ik2_variant;

if ( $ik2_post_id <= 0 && isset( $block->context['postId'] ) ) {
	$ik2_post_id = (int) $block->context['postId'];
}

if ( $ik2_post_id <= 0 ) {
	$ik2_post_id = (int) get_the_ID();
}

$ik2_card = $ik2_post_id > 0 ? Project\get_card_data( $ik2_post_id ) : null;

if ( null === $ik2_card ) {
	return;
}

$ik2_classes = 'ik-project';
if ( $ik2_compact ) {
	$ik2_classes .= ' ik-project--compact';
}
if ( $ik2_feature ) {
	$ik2_classes .= ' ik-project--feature';
}

$ik2_tag           = $ik2_feature ? 'div' : 'article';
$ik2_wrapper_attrs = get_block_wrapper_attributes(
	array(
		'class'             => $ik2_classes,
		'data-status'       => $ik2_card['status'],
		'data-project-slug' => get_post_field( 'post_name', $ik2_post_id ),
	)
);
?>
<<?php echo esc_attr( $ik2_tag ); ?> <?php echo $ik2_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( ! $ik2_feature ) : ?>
		<div class="ik-project__head">
			<h3 class="ik-project__name">
				<a href="<?php echo esc_url( $ik2_card['permalink'] ); ?>"><?php echo esc_html( $ik2_card['title'] ); ?></a>
			</h3>
			<?php if ( '' !== $ik2_card['status'] ) : ?>
				<span class="ik-project__status" data-status="<?php echo esc_attr( $ik2_card['status'] ); ?>">
					<?php echo esc_html( $ik2_card['status'] ); ?>
				</span>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( '' !== $ik2_card['excerpt'] ) : ?>
		<p class="ik-project__blurb"><?php echo esc_html( $ik2_card['excerpt'] ); ?></p>
	<?php endif; ?>

	<?php if ( ! empty( $ik2_card['tech'] ) ) : ?>
		<div class="ik-project__tech">
			<?php if ( $ik2_feature ) : ?>
				<span class="ik-project__tech-label">Stack</span>
			<?php endif; ?>
			<?php foreach ( $ik2_card['tech'] as $ik2_tech ) : ?>
				<span><?php echo esc_html( $ik2_tech ); ?></span>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php if ( ! $ik2_compact && ! empty( $ik2_card['links'] ) ) : ?>
		<div class="ik-project__links">
			<?php if ( $ik2_feature ) : ?>
				<span class="ik-project__links-label">Links</span>
			<?php endif; ?>
			<?php foreach ( $ik2_card['links'] as $ik2_link ) : ?>
				<a href="<?php echo esc_url( $ik2_link['href'] ); ?>" rel="noopener">
					<?php echo esc_html( $ik2_link['label'] ); ?> →
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php if ( ! $ik2_compact && '' !== $ik2_card['learned'] ) : ?>
		<p class="ik-project__learned">
			<strong><?php esc_html_e( 'What I learned', 'ik2' ); ?></strong>
			<?php echo esc_html( $ik2_card['learned'] ); ?>
		</p>
	<?php endif; ?>
</<?php echo esc_attr( $ik2_tag ); ?>>
