<?php
/**
 * Server render for ik2/resume-experience.
 *
 * Outputs a list of resume experience rows. Each row pairs a monospace
 * date range with a role, organisation, and supporting note.
 *
 * @package IK2
 * @var array<string,mixed> $attributes
 * @var string              $content
 * @var WP_Block            $block
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$ik2_experience = [
	[
		'from'    => '2020',
		'to'      => __( 'Present', 'ik2' ),
		'current' => true,
		'role'    => __( 'Senior Engineer', 'ik2' ),
		'org'     => 'Human Made · Altis DXP',
		'note'    => __( 'Enterprise WordPress on the Altis platform &mdash; editorial tooling and performance work for large publishers.', 'ik2' ),
	],
	[
		'from'    => '2017',
		'to'      => '2020',
		'current' => false,
		'role'    => __( 'Senior Web Engineer', 'ik2' ),
		'org'     => '10up',
		'note'    => __( 'Custom WordPress for newsrooms and enterprise. Performance audits, Gutenberg block libraries, design system tooling.', 'ik2' ),
	],
	[
		'from'    => '2011',
		'to'      => '2016',
		'current' => false,
		'role'    => __( 'Senior Web Developer & System Architect', 'ik2' ),
		'org'     => 'Calibreworks',
		'note'    => __( 'Built and architected web applications end to end, from data model to front end.', 'ik2' ),
	],
];

$ik2_wrapper_attrs = get_block_wrapper_attributes(
	[ 'class' => 'ik-resume__section ik-resume__section--exp' ]
);
?>
<section <?php echo $ik2_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<h2 class="ik-resume__section-title">
		<span class="ik-resume__section-num" aria-hidden="true">01</span>
		<span class="ik-resume__section-name"><?php esc_html_e( 'Experience', 'ik2' ); ?></span>
	</h2>
	<ol class="ik-resume__exp">
		<?php foreach ( $ik2_experience as $ik2_row ) : ?>
			<li class="ik-resume__exp-row<?php echo $ik2_row['current'] ? ' is-current' : ''; ?>">
				<div class="ik-resume__exp-when">
					<span class="ik-resume__exp-from"><?php echo esc_html( $ik2_row['from'] ); ?></span>
					<span class="ik-resume__exp-range">
						<span class="ik-resume__exp-arrow" aria-hidden="true">&rarr;</span>
						<span class="ik-resume__exp-to<?php echo $ik2_row['current'] ? ' is-now' : ''; ?>"><?php echo esc_html( $ik2_row['to'] ); ?></span>
					</span>
				</div>
				<div class="ik-resume__exp-body">
					<p class="ik-resume__exp-role"><?php echo esc_html( $ik2_row['role'] ); ?></p>
					<p class="ik-resume__exp-org"><?php echo esc_html( $ik2_row['org'] ); ?></p>
					<p class="ik-resume__exp-note"><?php echo wp_kses( $ik2_row['note'], [] ); ?></p>
				</div>
			</li>
		<?php endforeach; ?>
	</ol>
</section>
