<?php
/**
 * Title: Header
 * Slug: ik2/header
 * Categories: header
 * Block Types: core/template-part/header
 * Description: Site header with wordmark, primary navigation, command palette trigger and Resume CTA.
 *
 * @package IK2
 */

?>
<!-- wp:group {"className":"ik-header","style":{"spacing":{"padding":{"top":"var:preset|spacing|5","bottom":"var:preset|spacing|5"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group ik-header" style="padding-top:var(--wp--preset--spacing--5);padding-bottom:var(--wp--preset--spacing--5)">
	<!-- wp:group {"className":"container-full ik-header__row","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group container-full ik-header__row">
		<!-- wp:site-title {"level":0,"className":"ik-wordmark"} /-->

		<!-- wp:group {"className":"ik-header__right","layout":{"type":"flex","flexWrap":"nowrap"}} -->
		<div class="wp-block-group ik-header__right">
			<!-- wp:navigation {"className":"ik-header__nav","overlayMenu":"mobile","layout":{"type":"flex","setCascadingProperties":true,"justifyContent":"left"}} /-->

			<!-- wp:html -->
			<button type="button" class="ik-header__cmd" aria-label="<?php esc_attr_e( 'Open command palette', 'ik2' ); ?>" aria-keyshortcuts="Meta+K Control+K">
				<span><?php esc_html_e( 'Search', 'ik2' ); ?></span>
				<kbd>⌘K</kbd>
			</button>
			<!-- /wp:html -->

			<!-- wp:buttons {"className":"ik-header__resume-wrap"} -->
			<div class="wp-block-buttons ik-header__resume-wrap">
				<?php
				$ik2_resume_is_current = \IK2\Theme\ik2_is_resume_current();
				$ik2_resume_link_class = 'wp-block-button__link wp-element-button';
				$ik2_resume_aria       = '';

				if ( $ik2_resume_is_current ) {
					$ik2_resume_link_class .= ' is-current';
					$ik2_resume_aria        = ' aria-current="page"';
				}
				?>
				<!-- wp:button {"className":"ik-header__resume"} -->
				<div class="wp-block-button ik-header__resume"><a class="<?php echo esc_attr( $ik2_resume_link_class ); ?>" href="/resume"<?php echo $ik2_resume_aria; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php esc_html_e( 'Resume', 'ik2' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
