<?php
/**
 * Title: Home — Projects preview
 * Slug: ik2/home-projects-preview
 * Categories: ik2-home
 * Description: Three-card grid of recent project highlights.
 *
 * @package IK2
 */

?>
<!-- wp:group {"className":"ik-section ik-section--muted","layout":{"type":"constrained"}} -->
<section class="wp-block-group ik-section ik-section--muted">
	<div class="container-full">
		<div class="ik-section__head">
			<div>
				<!-- wp:paragraph {"className":"ik-section__eyebrow"} --><p class="ik-section__eyebrow">// THINGS I&rsquo;VE BUILT</p><!-- /wp:paragraph -->
				<!-- wp:heading {"level":2,"className":"ik-section__title"} --><h2 class="wp-block-heading ik-section__title">Projects</h2><!-- /wp:heading -->
			</div>
			<!-- wp:paragraph {"className":"ik-section__more"} --><p class="ik-section__more"><a href="/projects">All projects →</a></p><!-- /wp:paragraph -->
		</div>

		<!-- wp:html -->
		<div class="ik-project-grid">
			<article class="ik-project">
				<div class="ik-project__head">
					<h3 class="ik-project__name"><a href="/projects/ivankristianto-theme">ivankristianto-theme</a></h3>
					<span class="ik-project__status" data-status="building">building</span>
				</div>
				<p class="ik-project__blurb">This very site. A block theme with FSE, design tokens, and the Interactivity API for command-palette search.</p>
				<div class="ik-project__tech"><span>WordPress</span><span>PHP 8.4</span><span>wp-scripts</span><span>SCSS</span></div>
			</article>

			<article class="ik-project">
				<div class="ik-project__head">
					<h3 class="ik-project__name"><a href="/projects/wp-perf-toolkit">wp-perf-toolkit</a></h3>
					<span class="ik-project__status" data-status="shipped">shipped</span>
				</div>
				<p class="ik-project__blurb">A small mu-plugin for measuring real-user query and template performance — designed for big editorial sites.</p>
				<div class="ik-project__tech"><span>WordPress</span><span>PHP</span><span>SQLite</span></div>
			</article>

			<article class="ik-project">
				<div class="ik-project__head">
					<h3 class="ik-project__name"><a href="/projects/ai-editor-helpers">ai-editor-helpers</a></h3>
					<span class="ik-project__status" data-status="exploring">exploring</span>
				</div>
				<p class="ik-project__blurb">Block-editor side experiments using Claude + the WordPress REST API for drafting, summaries, and inline rewrites.</p>
				<div class="ik-project__tech"><span>JavaScript</span><span>Anthropic API</span><span>WP REST</span></div>
			</article>
		</div>
		<!-- /wp:html -->
	</div>
</section>
<!-- /wp:group -->
