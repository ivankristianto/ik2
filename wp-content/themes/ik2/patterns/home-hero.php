<?php
/**
 * Title: Home — Hero
 * Slug: ik2/home-hero
 * Categories: ik2-home
 * Description: Homepage hero with eyebrow, headline, intro, and two CTAs.
 *
 * @package IK2
 */

?>
<!-- wp:group {"className":"ik-section ik-hero","layout":{"type":"constrained"}} -->
<section class="wp-block-group ik-section ik-hero">
	<div class="container-full">
		<!-- wp:paragraph {"className":"ik-section__eyebrow"} -->
		<p class="ik-section__eyebrow">// CURRENTLY EXPLORING</p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"level":1,"className":"ik-hero__title","fontSize":"hero"} -->
		<h1 class="wp-block-heading ik-hero__title has-hero-font-size">Building things on the web — mostly with WordPress and AI.</h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"ik-hero__lede","fontSize":"xl"} -->
		<p class="ik-hero__lede has-xl-font-size">I write about WordPress engineering, AI-assisted development, performance, and the developer tooling that quietly makes large projects bearable. Most of what I publish here started as a working note to myself.</p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons {"className":"ik-hero__ctas"} -->
		<div class="wp-block-buttons ik-hero__ctas">
			<!-- wp:button {"className":"is-style-fill","style":{"border":{"radius":"0.375rem"}}} -->
			<div class="wp-block-button is-style-fill"><a class="wp-block-button__link wp-element-button" href="/articles" style="border-radius:0.375rem">Browse articles</a></div>
			<!-- /wp:button -->
			<!-- wp:button {"className":"is-style-outline","style":{"border":{"radius":"0.375rem"}}} -->
			<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/about" style="border-radius:0.375rem">About me</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
</section>
<!-- /wp:group -->
