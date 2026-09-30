<?php
/**
 * Title: Resume — Certifications
 * Slug: ik2/resume-certifications
 * Categories: ik2-page
 * Description: Certification rows — year, credential, issuer, scope note, validity, and a verify link.
 *
 * @package IK2
 */

$ik2_certifications = [
	[
		'when'  => '2026',
		'name'  => __( 'Advanced Professional WordPress Developer', 'ik2' ),
		'org'   => 'Automattic',
		'note'  => __( 'Professional-level exam covering WordPress core, custom development, security, performance, debugging, and scalable architecture.', 'ik2' ),
		'valid' => __( 'Valid until September 13, 2029', 'ik2' ),
		'href'  => 'https://automattic.credential.net/89602394-7eaf-4fac-b1aa-3989d33ef883',
		'link'  => __( 'Verify credential', 'ik2' ),
	],
	[
		'when'  => '2018',
		'name'  => __( 'Google Developer Expert, Web Technologies', 'ik2' ),
		'org'   => 'Google',
		'note'  => __( 'Recognized by Google as an expert in web technologies, specializing in web performance.', 'ik2' ),
		'valid' => __( 'Since 2018, renewed for 2026', 'ik2' ),
		'href'  => 'https://g.dev/ivan',
		'link'  => __( 'View GDE profile', 'ik2' ),
	],
];

?>
<!-- wp:group {"tagName":"section","className":"ik-resume__section ik-resume__section--certs","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
<section class="wp-block-group ik-resume__section ik-resume__section--certs">
	<!-- wp:heading {"level":2,"className":"ik-resume__section-title"} -->
	<h2 class="wp-block-heading ik-resume__section-title">Certifications</h2>
	<!-- /wp:heading -->

	<!-- wp:group {"className":"ik-resume__certs","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
	<div class="wp-block-group ik-resume__certs">
		<?php foreach ( $ik2_certifications as $ik2_cert ) : ?>
		<!-- wp:group {"className":"ik-resume__cert-row","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
		<div class="wp-block-group ik-resume__cert-row">
			<!-- wp:paragraph {"className":"ik-resume__cert-when"} -->
			<p class="ik-resume__cert-when"><?php echo esc_html( $ik2_cert['when'] ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:group {"className":"ik-resume__cert-body","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
			<div class="wp-block-group ik-resume__cert-body">
				<!-- wp:paragraph {"className":"ik-resume__cert-name"} -->
				<p class="ik-resume__cert-name"><?php echo esc_html( $ik2_cert['name'] ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph {"className":"ik-resume__cert-org"} -->
				<p class="ik-resume__cert-org"><?php echo esc_html( $ik2_cert['org'] ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph {"className":"ik-resume__cert-note"} -->
				<p class="ik-resume__cert-note"><?php echo esc_html( $ik2_cert['note'] ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph {"className":"ik-resume__cert-meta"} -->
				<p class="ik-resume__cert-meta"><?php echo esc_html( $ik2_cert['valid'] ); ?> <span aria-hidden="true">·</span> <a href="<?php echo esc_url( $ik2_cert['href'] ); ?>"><?php echo esc_html( $ik2_cert['link'] ); ?></a></p>
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
