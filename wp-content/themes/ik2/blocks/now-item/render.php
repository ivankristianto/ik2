<?php
/**
 * Server render for ik2/now-item.
 *
 * One `/now` entry: a small mono label above a line of text. Markup matches
 * the original hand-authored card (`.ik-now__group` and children) so the
 * existing theme CSS applies unchanged.
 *
 * @package IK2
 * @var array<string,mixed> $attributes
 * @var string              $content
 * @var WP_Block            $block
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$ik2_item_label = isset( $attributes['label'] ) ? trim( (string) $attributes['label'] ) : '';
$ik2_item_text  = isset( $attributes['text'] ) ? trim( (string) $attributes['text'] ) : '';

if ( '' === $ik2_item_label && '' === $ik2_item_text ) {
	return;
}

$ik2_item_text_tags = [
	'a'      => [
		'href'   => true,
		'target' => true,
		'rel'    => true,
	],
	'em'     => [],
	'strong' => [],
	'code'   => [],
];

$ik2_wrapper_attrs = get_block_wrapper_attributes( [ 'class' => 'ik-now__group' ] );
?>
<div <?php echo $ik2_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( '' !== $ik2_item_label ) : ?>
		<p class="ik-now__group-title"><?php echo esc_html( $ik2_item_label ); ?></p>
	<?php endif; ?>
	<?php if ( '' !== $ik2_item_text ) : ?>
		<p class="ik-now__item"><?php echo wp_kses( $ik2_item_text, $ik2_item_text_tags ); ?></p>
	<?php endif; ?>
</div>
