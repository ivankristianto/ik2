<?php
/**
 * Server render for ik2/resume-facts.
 *
 * Outputs an inline ledger of credibility receipts — a value paired with a
 * short label, divided by hairlines. Deliberately not a metric-card grid:
 * the items read as one technical spec line, not as marketing tiles.
 *
 * @package IK2
 * @var array<string,mixed> $attributes
 * @var string              $content
 * @var WP_Block            $block
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$ik2_facts = [
	[
		'value' => '15+',
		'label' => __( 'years shipping', 'ik2' ),
	],
	[
		'value' => '5',
		'label' => __( 'WordPress core releases', 'ik2' ),
	],
	[
		'value' => '2018',
		'label' => __( 'Google Developer Expert', 'ik2' ),
	],
	[
		'value' => '5',
		'label' => __( 'plugins published', 'ik2' ),
	],
	[
		'value' => 'id_ID',
		'label' => __( 'locale manager', 'ik2' ),
	],
];

$ik2_wrapper_attrs = get_block_wrapper_attributes(
	[ 'class' => 'ik-resume__facts' ]
);
?>
<dl <?php echo $ik2_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php foreach ( $ik2_facts as $ik2_fact ) : ?>
		<div class="ik-resume__fact">
			<dt class="ik-resume__fact-value"><?php echo esc_html( $ik2_fact['value'] ); ?></dt>
			<dd class="ik-resume__fact-label"><?php echo esc_html( $ik2_fact['label'] ); ?></dd>
		</div>
	<?php endforeach; ?>
</dl>
