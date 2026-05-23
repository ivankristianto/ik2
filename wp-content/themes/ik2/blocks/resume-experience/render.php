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

$ik2_experience = array(
	array(
		'from' => '2024',
		'to'   => __( 'Present', 'ik2' ),
		'role' => __( 'Senior Web Engineer', 'ik2' ),
		'org'  => 'Human Made',
		'note' => __( 'Enterprise WordPress, editorial tooling, performance work for large publishers.', 'ik2' ),
	),
	array(
		'from' => '2018',
		'to'   => '2024',
		'role' => __( 'Senior Web Engineer', 'ik2' ),
		'org'  => '10up',
		'note' => __( 'Custom WordPress for newsrooms and enterprise. Performance audits, Gutenberg block libraries, design system tooling.', 'ik2' ),
	),
	array(
		'from' => '2017',
		'to'   => '2017',
		'role' => __( 'Lead Organiser', 'ik2' ),
		'org'  => 'WordCamp Jakarta 2017',
		'note' => __( 'Lead organiser for the largest WordCamp held in Indonesia at the time.', 'ik2' ),
	),
	array(
		'from' => '2015',
		'to'   => __( 'Present', 'ik2' ),
		'role' => __( 'Lead Organiser', 'ik2' ),
		'org'  => __( 'Jakarta WordPress Meetup', 'ik2' ),
		'note' => __( 'Monthly meetup — talks, workshops, and a community that ships.', 'ik2' ),
	),
);

$ik2_wrapper_attrs = get_block_wrapper_attributes(
	array( 'class' => 'ik-resume__section' )
);
?>
<section <?php echo $ik2_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<h2 class="ik-resume__section-title"><?php esc_html_e( 'Experience', 'ik2' ); ?></h2>
	<div class="ik-resume__exp">
		<?php foreach ( $ik2_experience as $ik2_row ) : ?>
			<div class="ik-resume__exp-row">
				<div class="ik-resume__exp-when">
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: start year, 2: end year or "Present". */
							_x( '%1$s–%2$s', 'experience date range', 'ik2' ),
							$ik2_row['from'],
							$ik2_row['to']
						)
					);
					?>
				</div>
				<div class="ik-resume__exp-body">
					<div class="ik-resume__exp-role">
						<?php echo esc_html( $ik2_row['role'] ); ?>
						<span> · <?php echo esc_html( $ik2_row['org'] ); ?></span>
					</div>
					<div class="ik-resume__exp-note"><?php echo esc_html( $ik2_row['note'] ); ?></div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</section>
