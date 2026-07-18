<?php
/**
 * Title: Resume — Facts ledger
 * Slug: ik2/resume-facts
 * Categories: ik2-page
 * Description: Inline ledger of credibility receipts — a value paired with a short label, divided by hairlines.
 *
 * @package IK2
 */

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

?>
<!-- wp:group {"className":"ik-resume__facts","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
<div class="wp-block-group ik-resume__facts">
	<?php foreach ( $ik2_facts as $ik2_fact ) : ?>
	<!-- wp:group {"className":"ik-resume__fact","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
	<div class="wp-block-group ik-resume__fact">
		<!-- wp:paragraph {"className":"ik-resume__fact-value"} -->
		<p class="ik-resume__fact-value"><?php echo esc_html( $ik2_fact['value'] ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"className":"ik-resume__fact-label"} -->
		<p class="ik-resume__fact-label"><?php echo esc_html( $ik2_fact['label'] ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
	<?php endforeach; ?>
</div>
<!-- /wp:group -->
