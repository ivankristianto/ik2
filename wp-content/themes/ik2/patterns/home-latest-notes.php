<?php
/**
 * Title: Home — Latest notes + /now
 * Slug: ik2/home-latest-notes
 * Categories: ik2-home
 * Description: Latest notes list alongside a static /now sidebar.
 *
 * @package IK2
 */

?>
<!-- wp:group {"className":"ik-section","layout":{"type":"constrained"}} -->
<section class="wp-block-group ik-section">
	<div class="container-full">
		<div class="ik-section__head">
			<div>
				<!-- wp:paragraph {"className":"ik-section__eyebrow"} --><p class="ik-section__eyebrow">// LATEST NOTES  ·  TIL  ·  EXPERIMENTS  ·  LINKS</p><!-- /wp:paragraph -->
				<!-- wp:heading {"level":2,"className":"ik-section__title"} --><h2 class="wp-block-heading ik-section__title">What I&rsquo;ve been working on</h2><!-- /wp:heading -->
			</div>
			<!-- wp:paragraph {"className":"ik-section__more"} --><p class="ik-section__more"><a href="/articles">All articles →</a></p><!-- /wp:paragraph -->
		</div>

		<!-- wp:columns {"className":"ik-notes-layout"} -->
		<div class="wp-block-columns ik-notes-layout">

			<!-- wp:column {"width":"66.66%","className":"ik-notes-layout__main"} -->
			<div class="wp-block-column ik-notes-layout__main" style="flex-basis:66.66%">
				<!-- wp:query {"queryId":2,"query":{"perPage":6,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","taxQuery":{"category":["note"]},"inherit":false}} -->
				<div class="wp-block-query">
					<!-- wp:post-template -->
						<!-- wp:group {"className":"ik-note","layout":{"type":"constrained"}} -->
						<article class="wp-block-group ik-note">
							<!-- wp:post-date {"className":"ik-note__date","format":"F j, Y"} /-->
							<!-- wp:post-title {"isLink":true,"level":3,"className":"ik-note__title"} /-->
							<!-- wp:post-excerpt {"className":"ik-note__excerpt","excerptLength":24} /-->
						</article>
						<!-- /wp:group -->
					<!-- /wp:post-template -->
					<!-- wp:query-no-results -->
						<!-- wp:paragraph --><p>No notes yet.</p><!-- /wp:paragraph -->
					<!-- /wp:query-no-results -->
				</div>
				<!-- /wp:query -->
				<!-- wp:paragraph {"className":"ik-notes-layout__more"} -->
				<p class="ik-notes-layout__more"><a href="/articles">Read every note →</a></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"width":"33.33%","className":"ik-notes-layout__aside"} -->
			<div class="wp-block-column ik-notes-layout__aside" style="flex-basis:33.33%">
				<!-- wp:html -->
				<aside class="ik-now" aria-label="What Ivan is doing right now">
					<header class="ik-now__head">
						<span class="ik-now__dot" aria-hidden="true"></span>
						<span class="ik-now__label">// /now</span>
						<span class="ik-now__date">May 12, 2026</span>
					</header>
					<div class="ik-now__group">
						<div class="ik-now__group-title">Currently building</div>
						<div class="ik-now__item"><code>ivankristianto-theme</code> &mdash; rebuilding this site as a block theme.</div>
					</div>
					<div class="ik-now__group">
						<div class="ik-now__group-title">Currently reading</div>
						<div class="ik-now__item"><em>Designing Data-Intensive Applications</em>, Kleppmann &mdash; for the third time.</div>
					</div>
					<div class="ik-now__group">
						<div class="ik-now__group-title">Currently learning</div>
						<div class="ik-now__item">The WordPress Interactivity API &mdash; instant search + a real command palette.</div>
					</div>
					<div class="ik-now__group">
						<div class="ik-now__group-title">Listening</div>
						<div class="ik-now__item">The Changelog &middot; Syntax.fm &middot; WP Tavern Jukebox</div>
					</div>
					<footer class="ik-now__foot">Inspired by <a href="https://nownownow.com" target="_blank" rel="noreferrer">/now</a>. Updated when the world changes &mdash; not on a schedule.</footer>
				</aside>
				<!-- /wp:html -->
			</div>
			<!-- /wp:column -->

		</div>
		<!-- /wp:columns -->
	</div>
</section>
<!-- /wp:group -->
