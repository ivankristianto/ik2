<?php
/**
 * Title: Resume — Community
 * Slug: ik2/resume-community
 * Categories: ik2-page
 * Description: Volunteer and community involvement rows — compact period, role, organisation, and note.
 *
 * @package IK2
 */

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
		'when' => '2015 – 2020',
		'role' => __( 'Lead Organiser', 'ik2' ),
		'org'  => __( 'Jakarta WordPress Meetup', 'ik2' ),
		'note' => __( 'Monthly meetup — talks, workshops, and a community that ships.', 'ik2' ),
	],
	[
		'when' => '2016 & 2019',
		'role' => __( 'Lead Organiser', 'ik2' ),
		'org'  => __( 'WordCamp Jakarta', 'ik2' ),
		'note' => __( "Lead organiser for Indonesia's flagship WordCamp.", 'ik2' ),
	],
];

?>
<!-- wp:group {"tagName":"section","className":"ik-resume__section ik-resume__section--community","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
<section class="wp-block-group ik-resume__section ik-resume__section--community">
	<!-- wp:heading {"level":2,"className":"ik-resume__section-title"} -->
	<h2 class="wp-block-heading ik-resume__section-title">Community</h2>
	<!-- /wp:heading -->

	<!-- wp:group {"className":"ik-resume__community","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
	<div class="wp-block-group ik-resume__community">
		<?php foreach ( $ik2_community as $ik2_row ) : ?>
		<!-- wp:group {"className":"ik-resume__comm-row","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
		<div class="wp-block-group ik-resume__comm-row">
			<!-- wp:paragraph {"className":"ik-resume__comm-when"} -->
			<p class="ik-resume__comm-when"><?php echo esc_html( $ik2_row['when'] ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:group {"className":"ik-resume__comm-body","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
			<div class="wp-block-group ik-resume__comm-body">
				<!-- wp:paragraph {"className":"ik-resume__comm-role"} -->
				<p class="ik-resume__comm-role"><?php echo esc_html( $ik2_row['role'] ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph {"className":"ik-resume__comm-org"} -->
				<p class="ik-resume__comm-org"><?php echo esc_html( $ik2_row['org'] ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph {"className":"ik-resume__comm-note"} -->
				<p class="ik-resume__comm-note"><?php echo esc_html( $ik2_row['note'] ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->
		<?php endforeach; ?>
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
