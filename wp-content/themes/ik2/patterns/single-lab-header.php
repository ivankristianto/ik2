<?php
/**
 * Title: Single lab entry, header
 * Slug: ik2/single-lab-header
 * Categories: ik2-page
 * Inserter: no
 * Description: Lab entry title, the excerpt as the lead, then the date and kind. A write-up with no tool also gets its source link here; a tool carries that link on its frame. Depends on the current entry, so it is hidden from the manual inserter.
 *
 * @package IK2
 */

use IK2\Plugin\PostTypes\Lab;

$ik2_tool     = function_exists( 'IK2\\Plugin\\PostTypes\\Lab\\get_tool_data' ) ? Lab\get_tool_data( get_queried_object_id() ) : null;
$ik2_excerpt  = has_excerpt( get_queried_object_id() ) ? get_the_excerpt( get_queried_object_id() ) : '';
$ik2_has_tool = null !== $ik2_tool && '' !== $ik2_tool['url'];
// The tool's frame carries the source link, so the record is for write-ups only.
$ik2_source   = null !== $ik2_tool && ! $ik2_has_tool ? $ik2_tool['source'] : '';
$ik2_modified = get_the_modified_date( 'Y-m-d', get_queried_object_id() );
$ik2_date     = get_the_date( 'Y-m-d', get_queried_object_id() );
?>
<!-- wp:group {"tagName":"header","className":"ik-article__head ik-project-head","layout":{"type":"default"},"metadata":{"name":"Lab entry header"}} -->
<header class="wp-block-group ik-article__head ik-project-head">
	<!-- wp:post-title {"level":1,"className":"ik-article__title"} /-->
<?php if ( '' !== $ik2_excerpt ) : ?>

	<!-- wp:paragraph {"className":"ik-project-head__lead"} -->
	<p class="ik-project-head__lead"><?php echo esc_html( $ik2_excerpt ); ?></p>
	<!-- /wp:paragraph -->
<?php endif; ?>

	<!-- wp:html -->
	<div class="wp-block-group ik-article__meta">
		<div class="wp-block-group ik-article__meta-line">
			<time datetime="<?php echo esc_attr( (string) get_the_date( 'c', get_queried_object_id() ) ); ?>"><?php echo esc_html( (string) $ik2_date ); ?></time>
			<span><?php echo $ik2_has_tool ? esc_html__( 'tool', 'ik2' ) : esc_html__( 'write-up', 'ik2' ); ?></span>
		</div>
		<?php if ( $ik2_modified > $ik2_date ) : ?>
			<p><?php esc_html_e( 'updated', 'ik2' ); ?> <time datetime="<?php echo esc_attr( (string) get_the_modified_date( 'c', get_queried_object_id() ) ); ?>"><?php echo esc_html( (string) $ik2_modified ); ?></time></p>
		<?php endif; ?>
	</div>
	<!-- /wp:html -->
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
