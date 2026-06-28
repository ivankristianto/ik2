<?php
/**
 * Server render for ik2/resume-community.
 *
 * Outputs volunteer / community involvement rows. Visually lighter than the
 * paid experience list: a compact mono period, then role, organisation, and
 * an optional note. Kept separate so unpaid work never reads as employment.
 *
 * @package IK2
 * @var array<string,mixed> $attributes
 * @var string              $content
 * @var WP_Block            $block
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$ik2_community = [
	[
		'when' => '2025',
		'role' => __( 'Technology Team', 'ik2' ),
		'org'  => 'WordCamp Asia',
		'note' => __( 'Event technology and tooling for the largest WordPress event in Asia.', 'ik2' ),
	],
	[
		'when' => '2023',
		'role' => __( 'Volunteers Lead', 'ik2' ),
		'org'  => 'WordCamp Asia',
		'note' => __( 'Led the volunteer team across the multi-day conference.', 'ik2' ),
	],
	[
		'when' => '2015 — 2020',
		'role' => __( 'Lead Organiser', 'ik2' ),
		'org'  => __( 'Jakarta WordPress Meetup', 'ik2' ),
		'note' => __( 'Monthly meetup &mdash; talks, workshops, and a community that ships.', 'ik2' ),
	],
	[
		'when' => '2016 & 2019',
		'role' => __( 'Lead Organiser', 'ik2' ),
		'org'  => __( 'WordCamp Jakarta', 'ik2' ),
		'note' => __( "Lead organiser for Indonesia's flagship WordCamp.", 'ik2' ),
	],
];

$ik2_wrapper_attrs = get_block_wrapper_attributes(
	[ 'class' => 'ik-resume__section ik-resume__section--community' ]
);
?>
<section <?php echo $ik2_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<h2 class="ik-resume__section-title">
		<span class="ik-resume__section-num" aria-hidden="true">02</span>
		<span class="ik-resume__section-name"><?php esc_html_e( 'Community', 'ik2' ); ?></span>
	</h2>
	<ul class="ik-resume__community">
		<?php foreach ( $ik2_community as $ik2_row ) : ?>
			<li class="ik-resume__comm-row">
				<p class="ik-resume__comm-when"><?php echo esc_html( $ik2_row['when'] ); ?></p>
				<div class="ik-resume__comm-body">
					<p class="ik-resume__comm-role"><?php echo esc_html( $ik2_row['role'] ); ?></p>
					<p class="ik-resume__comm-org"><?php echo esc_html( $ik2_row['org'] ); ?></p>
					<p class="ik-resume__comm-note"><?php echo wp_kses( $ik2_row['note'], [] ); ?></p>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
