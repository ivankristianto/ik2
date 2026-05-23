<?php
/**
 * Server render for ik2/contact-channels.
 *
 * Outputs a two-column grid of contact channels. Each row has a monospace
 * label, a primary value link, and a secondary note.
 *
 * @package IK2
 * @var array<string,mixed> $attributes
 * @var string              $content
 * @var WP_Block            $block
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$ik2_channels = array(
	array(
		'label' => __( 'Email', 'ik2' ),
		'value' => 'hello@ivankristianto.com',
		'href'  => 'mailto:hello@ivankristianto.com',
		'note'  => __( 'Best for work, talks, mentoring.', 'ik2' ),
	),
	array(
		'label' => __( 'GitHub', 'ik2' ),
		'value' => '@ivankristianto',
		'href'  => 'https://github.com/ivankristianto',
		'note'  => __( 'Issues, PRs, code review.', 'ik2' ),
	),
	array(
		'label' => __( 'LinkedIn', 'ik2' ),
		'value' => 'in/ivankristianto',
		'href'  => 'https://www.linkedin.com/in/ivankristianto',
		'note'  => __( 'Recruiting and roles.', 'ik2' ),
	),
	array(
		'label' => __( 'Twitter', 'ik2' ),
		'value' => '@ivankristianto',
		'href'  => 'https://twitter.com/ivankristianto',
		'note'  => __( 'Drive-by chat, retweets, occasional rants.', 'ik2' ),
	),
	array(
		'label' => __( 'WordPress.org', 'ik2' ),
		'value' => '@ivankristianto',
		'href'  => 'https://profiles.wordpress.org/ivankristianto/',
		'note'  => __( 'Core, plugin, theme conversations.', 'ik2' ),
	),
	array(
		'label' => __( 'RSS', 'ik2' ),
		'value' => '/feed.xml',
		'href'  => home_url( '/feed/' ),
		'note'  => __( 'Subscribe via your reader of choice.', 'ik2' ),
	),
);

$ik2_wrapper_attrs = get_block_wrapper_attributes(
	array( 'class' => 'ik-contact-list' )
);
?>
<div <?php echo $ik2_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php foreach ( $ik2_channels as $ik2_channel ) : ?>
		<a class="ik-contact-row" href="<?php echo esc_url( $ik2_channel['href'] ); ?>">
			<span class="ik-contact-row__label"><?php echo esc_html( $ik2_channel['label'] ); ?></span>
			<span class="ik-contact-row__body">
				<span class="ik-contact-row__value"><?php echo esc_html( $ik2_channel['value'] ); ?></span>
				<span class="ik-contact-row__note"><?php echo esc_html( $ik2_channel['note'] ); ?></span>
			</span>
		</a>
	<?php endforeach; ?>
</div>
