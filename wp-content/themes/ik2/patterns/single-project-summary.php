<?php
/**
 * Title: Single project — summary
 * Slug: ik2/single-project-summary
 * Categories: ik2-page
 * Description: Excerpt and project facts for the single project template.
 *
 * @package IK2
 */

declare( strict_types=1 );

use IK2\Plugin\PostTypes\Project;

$ik2_project = Project\get_card_data( get_the_ID() );
$ik2_excerpt = trim( (string) get_the_excerpt() );

if ( null === $ik2_project || ( '' === $ik2_excerpt && empty( $ik2_project['tech'] ) && empty( $ik2_project['links'] ) && '' === $ik2_project['learned'] ) ) {
	return;
}
?>
<!-- wp:group {"className":"ik-project-single__summary","layout":{"type":"default"},"metadata":{"name":"Project summary"}} -->
<div class="wp-block-group ik-project-single__summary">
	<?php if ( '' !== $ik2_excerpt ) : ?>
		<!-- wp:paragraph {"className":"ik-project-single__lede"} -->
		<p class="ik-project-single__lede"><?php echo esc_html( $ik2_excerpt ); ?></p>
		<!-- /wp:paragraph -->
	<?php endif; ?>

	<?php if ( ! empty( $ik2_project['tech'] ) || ! empty( $ik2_project['links'] ) || '' !== $ik2_project['learned'] ) : ?>
		<!-- wp:group {"className":"ik-project-single__facts","layout":{"type":"grid","columnCount":2,"minimumColumnWidth":"18rem"}} -->
		<div class="wp-block-group ik-project-single__facts">
			<?php if ( ! empty( $ik2_project['tech'] ) ) : ?>
				<div class="ik-project-single__fact">
					<p class="ik-project-single__label"><?php esc_html_e( 'Stack', 'ik2' ); ?></p>
					<div class="ik-project__tech">
						<?php foreach ( $ik2_project['tech'] as $ik2_tech ) : ?>
							<span><?php echo esc_html( $ik2_tech ); ?></span>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $ik2_project['links'] ) ) : ?>
				<div class="ik-project-single__fact">
					<p class="ik-project-single__label"><?php esc_html_e( 'Links', 'ik2' ); ?></p>
					<div class="ik-project__links">
						<?php foreach ( $ik2_project['links'] as $ik2_link ) : ?>
							<a href="<?php echo esc_url( $ik2_link['href'] ); ?>" rel="noopener">
								<?php echo esc_html( $ik2_link['label'] ); ?> →
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( '' !== $ik2_project['learned'] ) : ?>
				<div class="ik-project-single__fact">
					<p class="ik-project-single__label"><?php esc_html_e( 'What I learned', 'ik2' ); ?></p>
					<p class="ik-project-single__learned"><?php echo esc_html( $ik2_project['learned'] ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<!-- /wp:group -->
	<?php endif; ?>
</div>
<!-- /wp:group -->
