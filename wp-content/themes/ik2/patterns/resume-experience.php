<?php
/**
 * Title: Resume — Experience
 * Slug: ik2/resume-experience
 * Categories: ik2-page
 * Description: Chronological experience rows — a monospace date range beside role, organisation, and note.
 *
 * @package IK2
 */

$ik2_experience = [
	[
		'from'    => '2020',
		'to'      => __( 'Present', 'ik2' ),
		'current' => true,
		'role'    => __( 'Senior Engineer', 'ik2' ),
		'org'     => 'Human Made · Altis DXP',
		'note'    => __( 'Enterprise WordPress on the Altis platform — editorial tooling and performance work for large publishers.', 'ik2' ),
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

?>
<!-- wp:group {"tagName":"section","className":"ik-resume__section ik-resume__section--exp","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
<section class="wp-block-group ik-resume__section ik-resume__section--exp">
	<!-- wp:heading {"level":2,"className":"ik-resume__section-title"} -->
	<h2 class="wp-block-heading ik-resume__section-title">Experience</h2>
	<!-- /wp:heading -->

	<!-- wp:group {"className":"ik-resume__exp","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
	<div class="wp-block-group ik-resume__exp">
		<?php foreach ( $ik2_experience as $ik2_row ) : ?>
		<!-- wp:group {"className":"ik-resume__exp-row<?php echo $ik2_row['current'] ? ' is-current' : ''; ?>","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
		<div class="wp-block-group ik-resume__exp-row<?php echo $ik2_row['current'] ? ' is-current' : ''; ?>">
			<!-- wp:group {"className":"ik-resume__exp-when","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
			<div class="wp-block-group ik-resume__exp-when">
				<!-- wp:paragraph {"className":"ik-resume__exp-from"} -->
				<p class="ik-resume__exp-from"><?php echo esc_html( $ik2_row['from'] ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph {"className":"ik-resume__exp-range"} -->
				<p class="ik-resume__exp-range"><span class="ik-resume__exp-arrow" aria-hidden="true">&rarr;</span> <span class="ik-resume__exp-to<?php echo $ik2_row['current'] ? ' is-now' : ''; ?>"><?php echo esc_html( $ik2_row['to'] ); ?></span></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

			<!-- wp:group {"className":"ik-resume__exp-body","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
			<div class="wp-block-group ik-resume__exp-body">
				<!-- wp:paragraph {"className":"ik-resume__exp-role"} -->
				<p class="ik-resume__exp-role"><?php echo esc_html( $ik2_row['role'] ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph {"className":"ik-resume__exp-org"} -->
				<p class="ik-resume__exp-org"><?php echo esc_html( $ik2_row['org'] ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph {"className":"ik-resume__exp-note"} -->
				<p class="ik-resume__exp-note"><?php echo esc_html( $ik2_row['note'] ); ?></p>
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
