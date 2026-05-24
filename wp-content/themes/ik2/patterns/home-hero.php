<?php
/**
 * Title: Home — Hero
 * Slug: ik2/home-hero
 * Categories: ik2-home
 * Description: Explorer-variant hero with typographic primary CTA and portrait card.
 *
 * @package IK2
 */

$ik2_portrait_uri = get_theme_file_uri( 'assets/images/ivan-portrait.webp' );
$ik2_portrait_dir = get_theme_file_path( 'assets/images/ivan-portrait.webp' );

?>
<!-- wp:group {"tagName":"section","className":"container-full ik-section ik-hero","layout":{"type":"default"}} -->
<section class="wp-block-group container-full ik-section ik-hero">
	<div class="ik-hero__layout">
		<div class="ik-hero__main">
			<!-- wp:paragraph {"className":"ik-section__eyebrow ik-hero__eyebrow"} -->
			<p class="ik-section__eyebrow ik-hero__eyebrow">// Web engineer &middot; WordPress &middot; AI &middot; Performance &middot; Tooling</p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"level":1,"className":"ik-hero__title","fontSize":"hero"} -->
			<h1 class="wp-block-heading ik-hero__title has-hero-font-size">I&rsquo;m Ivan Kristianto.</h1>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"ik-hero__blurb","fontSize":"xl"} -->
			<p class="ik-hero__blurb has-xl-font-size">I explore WordPress, AI, performance, and developer tooling to build better experiences on the web.</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"className":"ik-hero__sub"} -->
			<p class="ik-hero__sub">This site is my public notebook: deep technical tutorials, short notes, experiments, talks, and links from years of building for the web.</p>
			<!-- /wp:paragraph -->

			<!-- wp:html -->
			<div class="ik-hero__actions">
				<a class="ik-hero__primary" href="<?php echo esc_url( home_url( '/articles' ) ); ?>">
					<span class="ik-hero__primary-label">Browse the guides</span>
					<span class="ik-hero__primary-arrow" aria-hidden="true">&rarr;</span>
				</a>
				<div class="ik-hero__secondary">
					<a href="<?php echo esc_url( home_url( '/resume' ) ); ?>">Read resume</a>
					<span aria-hidden="true">&middot;</span>
					<a href="<?php echo esc_url( home_url( '/articles' ) ); ?>">Latest articles</a>
					<span aria-hidden="true">&middot;</span>
					<a href="<?php echo esc_url( home_url( '/contact' ) ); ?>">Get in touch</a>
				</div>
			</div>
			<!-- /wp:html -->
		</div>

		<!-- wp:html -->
		<aside class="ik-hero__portrait">
			<div class="ik-hero__portrait-bar">
				<span class="ik-hero__portrait-dots" aria-hidden="true"><i></i><i></i><i></i></span>
				<span class="ik-hero__portrait-path">~ $ ./ivan.jpg</span>
			</div>
			<div class="ik-hero__portrait-frame">
				<?php if ( file_exists( $ik2_portrait_dir ) ) : ?>
				<img src="<?php echo esc_url( $ik2_portrait_uri ); ?>" alt="Ivan Kristianto" class="ik-hero__portrait-img" loading="eager" decoding="async" />
			<?php else : ?>
				<div class="ik-hero__portrait-placeholder" aria-hidden="true">IK</div>
			<?php endif; ?>
			</div>
			<figcaption class="ik-hero__portrait-caption">// Ivan Kristianto &middot; Jakarta &middot; c. <?php echo esc_html( gmdate( 'Y' ) ); ?></figcaption>
		</aside>
		<!-- /wp:html -->
	</div>

	<!-- wp:paragraph {"className":"ik-hero__quicklinks"} -->
	<p class="ik-hero__quicklinks"><span>// Exploring WordPress, AI, performance, and developer tooling.</span></p>
	<!-- /wp:paragraph -->
</section>
<!-- /wp:group -->
