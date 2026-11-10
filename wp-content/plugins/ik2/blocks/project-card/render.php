<?php
/**
 * Server render for ik2/project-card: one row of the projects ledger.
 *
 * Resolves a Project ID in this order:
 *   1. Explicit `postId` attribute (used by curated previews).
 *   2. `postId` from block context (set by core query loop / post template).
 *   3. The current main query post.
 *
 * The name links to the project page. Status shows only when it says something: never under a
 * status heading (`showStatus` false), and never for Active, the default.
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
// H3 under a section H2 (home preview); the Projects archive passes 2 so cards sit right under its H1.
$ik2_heading_tag = 'h' . min( 6, max( 2, (int) ( $attributes['headingLevel'] ?? 3 ) ) );

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

$ik2_show_status = ( $attributes['showStatus'] ?? true ) && 'Active' !== $ik2_card['status'];

$ik2_wrapper_attrs = get_block_wrapper_attributes(
	[
		'class'             => $ik2_classes,
		'data-status'       => $ik2_card['status'],
		'data-project-slug' => get_post_field( 'post_name', $ik2_post_id ),
	]
);
?>
<article <?php echo $ik2_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="ik-project__main">
		<<?php echo esc_html( $ik2_heading_tag ); ?> class="ik-project__name"><a href="<?php echo esc_url( (string) get_permalink( $ik2_post_id ) ); ?>"><?php echo esc_html( $ik2_card['title'] ); ?></a></<?php echo esc_html( $ik2_heading_tag ); ?>>

		<?php if ( $ik2_card['excerpt'] !== '' ) : ?>
			<p class="ik-project__blurb"><?php echo esc_html( $ik2_card['excerpt'] ); ?></p>
		<?php endif; ?>

		<?php if ( ! $ik2_compact && $ik2_card['learned'] !== '' ) : ?>
			<p class="ik-project__learned">
				<strong><?php esc_html_e( 'What I learned', 'ik2' ); ?></strong>
				<?php echo esc_html( $ik2_card['learned'] ); ?>
			</p>
		<?php endif; ?>
	</div>

	<div class="ik-project__side">
		<?php if ( $ik2_show_status ) : ?>
			<span class="ik-project__status" data-status="<?php echo esc_attr( $ik2_card['status'] ); ?>"><?php echo esc_html( $ik2_card['status'] ); ?></span>
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
					// Every row repeats "GitHub", so real (visually hidden) text names the project:
					// checkers see distinct link text and the accessible name still starts with the label.
					$ik2_link_suffix = sprintf(
						/* translators: %s: project title, appended to a link label for screen readers. */
						__( ', %s', 'ik2' ),
						$ik2_card['title']
					);
					?>
					<a href="<?php echo esc_url( $ik2_link['href'] ); ?>" rel="noopener"><?php echo esc_html( $ik2_link['label'] ); ?><span class="ik-project__link-context"><?php echo esc_html( $ik2_link_suffix ); ?></span> <span aria-hidden="true">&nearr;</span></a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</article>
