<?php
/**
 * Title: About focus and open source
 * Slug: ik2/about-page-columns
 * Categories: ik2-page
 * Description: Two columns for current focus and open-source work.
 *
 * @package IK2
 */

?>
<!-- wp:group {"className":"ik-about__cols","layout":{"type":"default"}} -->
<div class="wp-block-group ik-about__cols">
	<!-- wp:group {"tagName":"section","className":"ik-about__col","layout":{"type":"default"}} -->
	<section class="wp-block-group ik-about__col">
		<!-- wp:heading {"level":2,"className":"ik-about__col-title"} -->
		<h2 class="wp-block-heading ik-about__col-title">What I'm working on</h2>
		<!-- /wp:heading -->

		<!-- wp:list {"className":"ik-about__focus"} -->
		<ul class="wp-block-list ik-about__focus">
			<!-- wp:list-item -->
			<li>WordPress block themes and editor experience</li>
			<!-- /wp:list-item -->

			<!-- wp:list-item -->
			<li>Web performance for large publishers</li>
			<!-- /wp:list-item -->

			<!-- wp:list-item -->
			<li>AI-assisted development workflows</li>
			<!-- /wp:list-item -->

			<!-- wp:list-item -->
			<li>Browser APIs: View Transitions, Interactivity API, scroll-driven animations</li>
			<!-- /wp:list-item -->

			<!-- wp:list-item -->
			<li>Developer tooling and CLI ergonomics</li>
			<!-- /wp:list-item -->
		</ul>
		<!-- /wp:list -->
	</section>
	<!-- /wp:group -->

	<!-- wp:group {"tagName":"section","className":"ik-about__col","layout":{"type":"default"}} -->
	<section class="wp-block-group ik-about__col">
		<!-- wp:heading {"level":2,"className":"ik-about__col-title"} -->
		<h2 class="wp-block-heading ik-about__col-title">Working in open source</h2>
		<!-- /wp:heading -->

		<!-- wp:group {"className":"ik-about__body","layout":{"type":"default"}} -->
		<div class="wp-block-group ik-about__body">
			<!-- wp:paragraph -->
			<p>I've worked on publishing platforms for over a decade, including at 10up and Human Made. I also build small tools and test browser APIs in the <a href="/lab/">Lab</a>.</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph -->
			<p>I've helped organise the Jakarta WordPress Meetup and WordCamps in Indonesia, and volunteered at WordCamp US in 2019. I led volunteers at WordCamp Asia in 2023 and joined its technology team in 2025. I also <a href="/speaking/">speak about WordPress and the web</a>.</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	</section>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
