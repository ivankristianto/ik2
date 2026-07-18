<?php
/**
 * Server render for ik2/now-card.
 *
 * Outputs the /now card chrome — pulsing dot, `// /now` label, last-updated
 * date, and the footer note — around the inner ik2/now-item blocks. The date
 * and footer are block attributes so both stay editable in the editor; an
 * emptied attribute drops the element entirely.
 *
 * @package IK2
 * @var array<string,mixed> $attributes
 * @var string              $content
 * @var WP_Block            $block
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$ik2_now_date = isset( $attributes['date'] ) ? trim( (string) $attributes['date'] ) : '';
$ik2_now_foot = isset( $attributes['foot'] ) ? trim( (string) $attributes['foot'] ) : '';

$ik2_now_foot_tags = [
	'a'      => [
		'href'   => true,
		'target' => true,
		'rel'    => true,
	],
	'em'     => [],
	'strong' => [],
	'code'   => [],
];

$ik2_wrapper_attrs = get_block_wrapper_attributes( [ 'class' => 'ik-now' ] );
?>
<aside <?php echo $ik2_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<p class="ik-now__head">
		<span class="ik-now__dot" aria-hidden="true"></span>
		<span class="ik-now__label">// /now</span>
		<?php if ( '' !== $ik2_now_date ) : ?>
			<span class="ik-now__date"><?php echo esc_html( $ik2_now_date ); ?></span>
		<?php endif; ?>
	</p>
	<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inner ik2/now-item blocks; each escapes its own fields. ?>
	<?php if ( '' !== $ik2_now_foot ) : ?>
		<p class="ik-now__foot"><?php echo wp_kses( $ik2_now_foot, $ik2_now_foot_tags ); ?></p>
	<?php endif; ?>
</aside>
