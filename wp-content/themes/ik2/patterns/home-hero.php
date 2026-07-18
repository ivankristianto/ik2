<?php
/**
 * Title: Home — Hero
 * Slug: ik2/home-hero
 * Categories: ik2-home
 * Viewport Width: 1400
 * Description: Homepage hero — eyebrow, title, blurbs, CTA links, and the terminal-framed portrait.
 *
 * @package IK2
 */

?>
<!-- wp:group {"tagName":"section","align":"full","className":"ik-section ik-hero","layout":{"type":"constrained","contentSize":"var(--wp--custom--width--full)"}} -->
<section class="wp-block-group alignfull ik-section ik-hero">
	<!-- wp:group {"className":"ik-hero__layout","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
	<div class="wp-block-group ik-hero__layout">
		<!-- wp:group {"className":"ik-hero__main","layout":{"type":"default"}} -->
		<div class="wp-block-group ik-hero__main">
			<!-- wp:paragraph {"className":"ik-section__eyebrow ik-hero__eyebrow"} -->
			<p class="ik-section__eyebrow ik-hero__eyebrow">// Web engineer · WordPress · AI · Performance · Tooling</p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"level":1,"className":"ik-hero__title","fontSize":"hero"} -->
			<h1 class="wp-block-heading ik-hero__title has-hero-font-size">I'm Ivan.</h1>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"ik-hero__blurb","fontSize":"xl"} -->
			<p class="ik-hero__blurb has-xl-font-size">I explore WordPress, AI, performance, and developer tooling to build better experiences on the web.</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"className":"ik-hero__sub"} -->
			<p class="ik-hero__sub">This site is my public notebook: deep technical tutorials, short notes, experiments, talks, and links from years of building for the web.</p>
			<!-- /wp:paragraph -->

			<!-- wp:group {"className":"ik-hero__actions","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
			<div class="wp-block-group ik-hero__actions">
				<!-- wp:buttons -->
				<div class="wp-block-buttons">
					<!-- wp:button {"className":"is-style-hero-cta"} -->
					<div class="wp-block-button is-style-hero-cta"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/articles' ) ); ?>">Browse the guides</a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->

				<!-- wp:paragraph {"className":"ik-hero__secondary"} -->
				<p class="ik-hero__secondary"><a href="<?php echo esc_url( home_url( '/resume' ) ); ?>">Read resume</a> <span aria-hidden="true">·</span> <a href="<?php echo esc_url( home_url( '/articles' ) ); ?>">Latest articles</a> <span aria-hidden="true">·</span> <a href="<?php echo esc_url( home_url( '/contact' ) ); ?>">Get in touch</a></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"tagName":"aside","className":"ik-hero__portrait","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
		<aside class="wp-block-group ik-hero__portrait">
			<!-- wp:html -->
			<div class="ik-hero__portrait-bar">
				<span class="ik-hero__portrait-dots" aria-hidden="true"><i></i><i></i><i></i></span>
				<span class="ik-hero__portrait-path">~ $ ./ivan.jpg</span>
			</div>
			<!-- /wp:html -->

			<!-- wp:image {"sizeSlug":"full","className":"ik-hero__portrait-frame"} -->
			<figure class="wp-block-image size-full ik-hero__portrait-frame"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/ivan-portrait.webp' ) ); ?>" alt="Ivan"/><figcaption class="wp-element-caption ik-hero__portrait-caption">// Ivan · Jakarta · c. <?php echo esc_html( gmdate( 'Y' ) ); ?></figcaption></figure>
			<!-- /wp:image -->
		</aside>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->

	<!-- wp:paragraph {"className":"ik-hero__quicklinks"} -->
	<p class="ik-hero__quicklinks"><span>// Exploring WordPress, AI, performance, and developer tooling.</span></p>
	<!-- /wp:paragraph -->
</section>
<!-- /wp:group -->
