<?php
/**
 * Server render for ik2/home-hero.
 *
 * @package IK2
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$ik2_portrait_uri = get_theme_file_uri( 'assets/images/ivan-portrait.webp' );
$ik2_portrait_dir = get_theme_file_path( 'assets/images/ivan-portrait.webp' );

$ik2_wrapper_attrs = get_block_wrapper_attributes(
	array( 'class' => 'container-full ik-section ik-hero' )
);
?>
<section <?php echo $ik2_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="ik-hero__layout">
		<div class="ik-hero__main">
			<p class="ik-section__eyebrow ik-hero__eyebrow"><?php esc_html_e( '// Web engineer · WordPress · AI · Performance · Tooling', 'ik2' ); ?></p>
			<h1 class="wp-block-heading ik-hero__title has-hero-font-size"><?php esc_html_e( "I'm Ivan Kristianto.", 'ik2' ); ?></h1>
			<p class="ik-hero__blurb has-xl-font-size"><?php esc_html_e( 'I explore WordPress, AI, performance, and developer tooling to build better experiences on the web.', 'ik2' ); ?></p>
			<p class="ik-hero__sub"><?php esc_html_e( 'This site is my public notebook: deep technical tutorials, short notes, experiments, talks, and links from years of building for the web.', 'ik2' ); ?></p>

			<div class="ik-hero__actions">
				<a class="ik-hero__primary" href="<?php echo esc_url( home_url( '/articles' ) ); ?>">
					<span class="ik-hero__primary-label"><?php esc_html_e( 'Browse the guides', 'ik2' ); ?></span>
					<span class="ik-hero__primary-arrow" aria-hidden="true">&rarr;</span>
				</a>
				<div class="ik-hero__secondary">
					<a href="<?php echo esc_url( home_url( '/resume' ) ); ?>"><?php esc_html_e( 'Read resume', 'ik2' ); ?></a>
					<span aria-hidden="true">&middot;</span>
					<a href="<?php echo esc_url( home_url( '/articles' ) ); ?>"><?php esc_html_e( 'Latest articles', 'ik2' ); ?></a>
					<span aria-hidden="true">&middot;</span>
					<a href="<?php echo esc_url( home_url( '/contact' ) ); ?>"><?php esc_html_e( 'Get in touch', 'ik2' ); ?></a>
				</div>
			</div>
		</div>

		<aside class="ik-hero__portrait">
			<div class="ik-hero__portrait-bar">
				<span class="ik-hero__portrait-dots" aria-hidden="true"><i></i><i></i><i></i></span>
				<span class="ik-hero__portrait-path">~ $ ./ivan.jpg</span>
			</div>
			<div class="ik-hero__portrait-frame">
				<?php if ( file_exists( $ik2_portrait_dir ) ) : ?>
					<img src="<?php echo esc_url( $ik2_portrait_uri ); ?>" alt="<?php esc_attr_e( 'Ivan Kristianto', 'ik2' ); ?>" class="ik-hero__portrait-img" loading="eager" decoding="async" />
				<?php else : ?>
					<div class="ik-hero__portrait-placeholder" aria-hidden="true">IK</div>
				<?php endif; ?>
			</div>
			<figcaption class="ik-hero__portrait-caption"><?php echo esc_html( sprintf( '// Ivan Kristianto · Jakarta · c. %s', gmdate( 'Y' ) ) ); ?></figcaption>
		</aside>
	</div>

	<p class="ik-hero__quicklinks"><span><?php esc_html_e( '// Exploring WordPress, AI, performance, and developer tooling.', 'ik2' ); ?></span></p>
</section>
