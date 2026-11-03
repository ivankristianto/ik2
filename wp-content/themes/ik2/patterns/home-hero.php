<?php
/**
 * Title: Home — Hero
 * Slug: ik2/home-hero
 * Categories: ik2-home
 * Viewport Width: 1400
 * Description: Homepage hero: title, blurb, CTA links, dated facts, and the terminal-framed portrait.
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
			<!-- wp:heading {"level":1,"className":"ik-hero__title"} -->
			<h1 class="wp-block-heading ik-hero__title">I'm Ivan.</h1>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"ik-hero__blurb"} -->
			<p class="ik-hero__blurb">I've spent over a decade building publishing platforms. This site is my public notebook: what I learn about WordPress, performance, AI, and the tools I build along the way.</p>
			<!-- /wp:paragraph -->

			<!-- wp:group {"className":"ik-hero__actions","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
			<div class="wp-block-group ik-hero__actions">
				<!-- wp:buttons -->
				<div class="wp-block-buttons">
					<!-- wp:button {"className":"is-style-hero-cta"} -->
					<div class="wp-block-button is-style-hero-cta"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/about/' ) ); ?>">More about me</a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->

				<!-- wp:paragraph {"className":"ik-hero__secondary"} -->
				<p class="ik-hero__secondary"><a href="<?php echo esc_url( home_url( '/articles/' ) ); ?>">Read the articles</a> <span aria-hidden="true">·</span> <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Get in touch</a></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

			<!-- wp:html -->
			<dl class="ik-hero__receipts">
				<dt>now</dt>
				<dd>Senior web engineer at Human Made</dd>
				<dt>2026</dt>
				<dd>Advanced Professional WordPress Developer, Automattic</dd>
				<dt>since 2018</dt>
				<dd>Google Developer Expert, web technologies</dd>
				<dt>since 2015</dt>
				<dd>Organising WordPress meetups and WordCamps</dd>
			</dl>
			<!-- /wp:html -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"ik-hero__portrait","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
		<div class="wp-block-group ik-hero__portrait">
			<!-- wp:html -->
			<div class="ik-hero__portrait-bar">
				<span class="ik-hero__portrait-dots" aria-hidden="true"><i></i><i></i><i></i></span>
				<span class="ik-hero__portrait-path" aria-hidden="true">~ $ ./ivan.jpg</span>
			</div>
			<!-- /wp:html -->

			<!-- wp:image {"sizeSlug":"full","className":"ik-hero__portrait-frame"} -->
			<figure class="wp-block-image size-full ik-hero__portrait-frame"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/ivan-portrait.webp' ) ); ?>" alt="Ivan Kristianto"/><figcaption class="wp-element-caption"><span aria-hidden="true">//</span> Ivan · c. <?php echo esc_html( gmdate( 'Y' ) ); ?></figcaption></figure>
			<!-- /wp:image -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
