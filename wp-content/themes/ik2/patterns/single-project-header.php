<?php
/**
 * Title: Single project, header
 * Slug: ik2/single-project-header
 * Categories: ik2-page
 * Inserter: no
 * Description: Project title, the excerpt as the lead, then a ruled record of status, stack and links. Depends on the current project, so it is hidden from the manual inserter.
 *
 * @package IK2
 */

use IK2\Plugin\PostTypes\Project;

$ik2_card = function_exists( 'IK2\\Plugin\\PostTypes\\Project\\get_card_data' ) ? Project\get_card_data( get_queried_object_id() ) : null;
?>
<!-- wp:group {"tagName":"header","className":"ik-article__head ik-project-head","layout":{"type":"default"},"metadata":{"name":"Project header"}} -->
<header class="wp-block-group ik-article__head ik-project-head">
	<!-- wp:post-title {"level":1,"className":"ik-article__title"} /-->
<?php if ( null !== $ik2_card && '' !== $ik2_card['excerpt'] ) : ?>

	<!-- wp:paragraph {"className":"ik-project-head__lead"} -->
	<p class="ik-project-head__lead"><?php echo esc_html( $ik2_card['excerpt'] ); ?></p>
	<!-- /wp:paragraph -->
<?php endif; ?>
<?php if ( null !== $ik2_card ) : ?>

	<!-- wp:html -->
	<dl class="ik-project-record">
		<div class="ik-project-record__row">
			<dt><?php esc_html_e( 'status', 'ik2' ); ?></dt>
			<dd class="ik-project-record__status" data-status="<?php echo esc_attr( $ik2_card['status'] ); ?>"><?php echo esc_html( $ik2_card['status'] ); ?></dd>
		</div>
	<?php if ( ! empty( $ik2_card['tech'] ) ) : ?>
		<div class="ik-project-record__row">
			<dt><?php esc_html_e( 'stack', 'ik2' ); ?></dt>
			<dd><?php echo esc_html( implode( ', ', $ik2_card['tech'] ) ); ?></dd>
		</div>
	<?php endif; ?>
	<?php foreach ( $ik2_card['links'] as $ik2_link ) : ?>
		<?php
		// Drop the scheme, and allow breaks after each slash so a long repo path wraps between segments.
		$ik2_parts     = explode( '/', untrailingslashit( (string) preg_replace( '#^https?://(www\.)?#', '', $ik2_link['href'] ) ) );
		$ik2_link_text = implode( '/<wbr>', array_map( 'esc_html', $ik2_parts ) );
		?>
		<div class="ik-project-record__row">
			<dt><?php echo esc_html( $ik2_link['label'] ); ?></dt>
			<dd><a href="<?php echo esc_url( $ik2_link['href'] ); ?>" rel="noopener"><?php echo $ik2_link_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each part is escaped. ?> <span aria-hidden="true">&nearr;</span></a></dd>
		</div>
	<?php endforeach; ?>
	</dl>
	<!-- /wp:html -->
<?php endif; ?>
</header>
<!-- /wp:group -->
