<?php
/**
 * Title: Resume — Contact links
 * Slug: ik2/resume-page-contact
 * Categories: ik2-page
 * Description: Contact link list for the Resume page (email, GitHub, LinkedIn, WordPress).
 *
 * @package IK2
 */

$ik2_contacts = [
	[
		'label' => __( 'Email', 'ik2' ),
		'value' => 'hello@ivankristianto.com',
		'href'  => 'mailto:hello@ivankristianto.com',
	],
	[
		'label' => __( 'GitHub', 'ik2' ),
		'value' => 'github.com/ivankristianto',
		'href'  => 'https://github.com/ivankristianto',
	],
	[
		'label' => __( 'LinkedIn', 'ik2' ),
		'value' => 'linkedin.com/in/ivankristianto',
		'href'  => 'https://www.linkedin.com/in/ivankristianto',
	],
	[
		'label' => __( 'WordPress', 'ik2' ),
		'value' => 'profiles.wordpress.org/ivankristianto',
		'href'  => 'https://profiles.wordpress.org/ivankristianto/',
	],
];

?>
<!-- wp:group {"tagName":"section","className":"ik-resume__section ik-resume__section--contact","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
<section class="wp-block-group ik-resume__section ik-resume__section--contact">
	<!-- wp:heading {"level":2,"className":"ik-resume__section-title"} -->
	<h2 class="wp-block-heading ik-resume__section-title">Contact</h2>
	<!-- /wp:heading -->

	<!-- wp:list {"className":"ik-resume__contact"} -->
	<ul class="wp-block-list ik-resume__contact">
		<?php foreach ( $ik2_contacts as $ik2_contact ) : ?>
		<!-- wp:list-item -->
		<li><a href="<?php echo esc_url( $ik2_contact['href'] ); ?>"><span class="ik-resume__contact-label"><?php echo esc_html( $ik2_contact['label'] ); ?></span><span class="ik-resume__contact-value"><?php echo esc_html( $ik2_contact['value'] ); ?></span></a></li>
		<!-- /wp:list-item -->
		<?php endforeach; ?>
	</ul>
	<!-- /wp:list -->
</section>
<!-- /wp:group -->
