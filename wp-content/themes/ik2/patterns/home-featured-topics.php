<?php
/**
 * Title: Home — Featured topics
 * Slug: ik2/home-featured-topics
 * Categories: ik2-home
 * Description: Six topic cards with one-line blurbs.
 *
 * @package IK2
 */

?>
<!-- wp:group {"className":"ik-section","layout":{"type":"constrained"}} -->
<section class="wp-block-group ik-section">
	<div class="container-full">
		<div class="ik-section__head">
			<div>
				<!-- wp:paragraph {"className":"ik-section__eyebrow"} --><p class="ik-section__eyebrow">// FEATURED TOPICS</p><!-- /wp:paragraph -->
				<!-- wp:heading {"level":2,"className":"ik-section__title"} --><h2 class="wp-block-heading ik-section__title">Where I spend my time on the web</h2><!-- /wp:heading -->
			</div>
			<!-- wp:paragraph {"className":"ik-section__more"} --><p class="ik-section__more"><a href="/articles">All articles →</a></p><!-- /wp:paragraph -->
		</div>

		<!-- wp:html -->
		<div class="ik-topics">
			<a class="ik-topic" href="/category/wordpress">
				<div class="ik-topic__row"><span class="ik-topic__name">WordPress</span><span class="ik-topic__count">42</span></div>
				<p class="ik-topic__blurb">Engineering notes from large-scale WordPress builds.</p>
			</a>
			<a class="ik-topic" href="/category/ai">
				<div class="ik-topic__row"><span class="ik-topic__name">AI</span><span class="ik-topic__count">18</span></div>
				<p class="ik-topic__blurb">How I use LLMs day-to-day, and where they actually help.</p>
			</a>
			<a class="ik-topic" href="/category/performance">
				<div class="ik-topic__row"><span class="ik-topic__name">Performance</span><span class="ik-topic__count">23</span></div>
				<p class="ik-topic__blurb">Real numbers from real sites — caching, queries, Core Web Vitals.</p>
			</a>
			<a class="ik-topic" href="/category/web-apis">
				<div class="ik-topic__row"><span class="ik-topic__name">Web APIs</span><span class="ik-topic__count">11</span></div>
				<p class="ik-topic__blurb">Platform primitives — what's new, what's stable, what's worth using.</p>
			</a>
			<a class="ik-topic" href="/category/tooling">
				<div class="ik-topic__row"><span class="ik-topic__name">Tooling</span><span class="ik-topic__count">16</span></div>
				<p class="ik-topic__blurb">Editor setup, CLI scripts, CI tricks, things that compound.</p>
			</a>
			<a class="ik-topic" href="/category/process">
				<div class="ik-topic__row"><span class="ik-topic__name">Process</span><span class="ik-topic__count">9</span></div>
				<p class="ik-topic__blurb">How I plan work, run reviews, and ship without drama.</p>
			</a>
		</div>
		<!-- /wp:html -->
	</div>
</section>
<!-- /wp:group -->
