<?php
/**
 * Title: Contact — Channels
 * Slug: ik2/contact-channels
 * Categories: ik2-page
 * Description: Two-column grid of contact channels — monospace label, value link, and a secondary note.
 *
 * @package IK2
 */

$ik2_channels = [
	[
		'label' => __( 'Email', 'ik2' ),
		'value' => 'hello@ivankristianto.com',
		'href'  => 'mailto:hello@ivankristianto.com',
		'note'  => __( 'Best for work, talks, mentoring.', 'ik2' ),
	],
	[
		'label' => __( 'GitHub', 'ik2' ),
		'value' => '@ivankristianto',
		'href'  => 'https://github.com/ivankristianto',
		'note'  => __( 'Issues, PRs, code review.', 'ik2' ),
	],
	[
		'label' => __( 'LinkedIn', 'ik2' ),
		'value' => 'in/ivankristianto',
		'href'  => 'https://www.linkedin.com/in/ivankristianto',
		'note'  => __( 'Recruiting and roles.', 'ik2' ),
	],
	[
		'label' => __( 'Twitter', 'ik2' ),
		'value' => '@ivankristianto',
		'href'  => 'https://twitter.com/ivankristianto',
		'note'  => __( 'Drive-by chat, retweets, occasional rants.', 'ik2' ),
	],
	[
		'label' => __( 'WordPress.org', 'ik2' ),
		'value' => '@ivankristianto',
		'href'  => 'https://profiles.wordpress.org/ivankristianto/',
		'note'  => __( 'Core, plugin, theme conversations.', 'ik2' ),
	],
	[
		'label' => __( 'RSS', 'ik2' ),
		'value' => '/feed/',
		'href'  => home_url( '/feed/' ),
		'note'  => __( 'Subscribe via your reader of choice.', 'ik2' ),
	],
];

?>
<!-- wp:group {"className":"ik-contact-list","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
<div class="wp-block-group ik-contact-list">
	<?php foreach ( $ik2_channels as $ik2_channel ) : ?>
	<!-- wp:group {"className":"ik-contact-row","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
	<div class="wp-block-group ik-contact-row">
		<!-- wp:paragraph {"className":"ik-contact-row__label"} -->
		<p class="ik-contact-row__label"><?php echo esc_html( $ik2_channel['label'] ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:group {"className":"ik-contact-row__body","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
		<div class="wp-block-group ik-contact-row__body">
			<!-- wp:paragraph {"className":"ik-contact-row__value"} -->
			<p class="ik-contact-row__value"><a href="<?php echo esc_url( $ik2_channel['href'] ); ?>"><?php echo esc_html( $ik2_channel['value'] ); ?></a></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"className":"ik-contact-row__note"} -->
			<p class="ik-contact-row__note"><?php echo esc_html( $ik2_channel['note'] ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
	<?php endforeach; ?>
</div>
<!-- /wp:group -->
