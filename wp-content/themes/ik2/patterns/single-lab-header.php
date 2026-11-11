<?php
/**
 * Title: Single lab entry, header
 * Slug: ik2/single-lab-header
 * Categories: ik2-page
 * Inserter: no
 * Description: Lab entry title, the excerpt as the lead, then a link to the tool's source when one is set. Depends on the current entry, so it is hidden from the manual inserter.
 *
 * @package IK2
 */

use IK2\Plugin\PostTypes\Lab;

$ik2_tool    = function_exists( 'IK2\\Plugin\\PostTypes\\Lab\\get_tool_data' ) ? Lab\get_tool_data( get_queried_object_id() ) : null;
$ik2_excerpt = has_excerpt( get_queried_object_id() ) ? get_the_excerpt( get_queried_object_id() ) : '';
$ik2_source  = null !== $ik2_tool ? $ik2_tool['source'] : '';
?>
<!-- wp:group {"tagName":"header","className":"ik-article__head ik-project-head","layout":{"type":"default"},"metadata":{"name":"Lab entry header"}} -->
<header class="wp-block-group ik-article__head ik-project-head">
	<!-- wp:post-title {"level":1,"className":"ik-article__title"} /-->
<?php if ( '' !== $ik2_excerpt ) : ?>

	<!-- wp:paragraph {"className":"ik-project-head__lead"} -->
	<p class="ik-project-head__lead"><?php echo esc_html( $ik2_excerpt ); ?></p>
	<!-- /wp:paragraph -->
<?php endif; ?>
<?php if ( '' !== $ik2_source ) : ?>
	<?php
	// Drop the scheme, and allow breaks after each slash so a long repo path wraps between segments.
	$ik2_parts     = explode( '/', untrailingslashit( (string) preg_replace( '#^https?://(www\.)?#', '', $ik2_source ) ) );
	$ik2_link_text = implode( '/<wbr>', array_map( 'esc_html', $ik2_parts ) );
	?>

	<!-- wp:html -->
	<dl class="ik-project-record">
		<div class="ik-project-record__row">
			<dt><?php esc_html_e( 'source', 'ik2' ); ?></dt>
			<dd><a href="<?php echo esc_url( $ik2_source ); ?>" rel="noopener"><?php echo $ik2_link_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each part is escaped. ?> <span aria-hidden="true">&nearr;</span></a></dd>
		</div>
	</dl>
	<!-- /wp:html -->
<?php endif; ?>
</header>
<!-- /wp:group -->
