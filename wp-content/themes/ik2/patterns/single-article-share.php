<?php
/**
 * Title: Single article — share footer
 * Slug: ik2/single-article-share
 * Categories: ik2-page
 * Description: Horizontal separator and share links for the single post template.
 *
 * @package IK2
 */

$ik2_share_permalink = get_permalink();
$ik2_share_title     = wp_strip_all_tags( get_the_title() );

$ik2_twitter_url  = 'https://twitter.com/intent/tweet?url=' . rawurlencode( (string) $ik2_share_permalink ) . '&text=' . rawurlencode( $ik2_share_title );
$ik2_linkedin_url = 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode( (string) $ik2_share_permalink );
?>
<!-- wp:separator {"className":"ik-article__sep-rule"} -->
<hr class="wp-block-separator has-alpha-channel-opacity ik-article__sep-rule"/>
<!-- /wp:separator -->

<!-- wp:group {"className":"ik-article__share","layout":{"type":"flex","flexWrap":"wrap"},"metadata":{"name":"Share"}} -->
<div class="wp-block-group ik-article__share">
	<!-- wp:paragraph {"className":"ik-article__share-label"} -->
	<p class="ik-article__share-label"><?php esc_html_e( 'Share:', 'ik2' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph -->
	<p><a href="<?php echo esc_url( $ik2_twitter_url ); ?>" rel="noopener" target="_blank"><?php esc_html_e( 'Twitter', 'ik2' ); ?></a></p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph -->
	<p><a href="<?php echo esc_url( $ik2_linkedin_url ); ?>" rel="noopener" target="_blank"><?php esc_html_e( 'LinkedIn', 'ik2' ); ?></a></p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph -->
	<p><a class="ik-article__share-copy" href="<?php echo esc_url( (string) $ik2_share_permalink ); ?>"><?php esc_html_e( 'Copy link', 'ik2' ); ?></a></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
