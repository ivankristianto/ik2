<?php
/**
 * Title: Resume — Header
 * Slug: ik2/resume-page-header
 * Categories: ik2-page
 * Description: Eyebrow, name, title, location, and summary for the Resume page.
 *
 * @package IK2
 */

?>
<!-- wp:group {"tagName":"header","className":"ik-resume__head","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
<header class="wp-block-group ik-resume__head">
	<!-- wp:group {"className":"ik-resume__card","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
	<div class="wp-block-group ik-resume__card">
		<!-- wp:paragraph {"className":"ik-resume__eyebrow"} -->
		<p class="ik-resume__eyebrow"><span class="ik-resume__eyebrow-tag">Resume</span> <span class="ik-resume__eyebrow-sep">·</span> <span class="ik-resume__eyebrow-meta">Updated 2026</span> <span class="ik-resume__eyebrow-sep">·</span> <span class="ik-resume__eyebrow-status">Available for select work</span></p>
		<!-- /wp:paragraph -->

		<!-- wp:group {"className":"ik-resume__masthead","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
		<div class="wp-block-group ik-resume__masthead">
			<!-- wp:heading {"level":1,"className":"ik-resume__name"} -->
			<h1 class="wp-block-heading ik-resume__name">Ivan.</h1>
			<!-- /wp:heading -->

			<!-- wp:group {"tagName":"aside","className":"ik-resume__meta","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
			<aside class="wp-block-group ik-resume__meta">
				<!-- wp:paragraph {"className":"ik-resume__title"} -->
				<p class="ik-resume__title">Senior Web Engineer <span>Google Developer Expert (Web)</span></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph {"className":"ik-resume__location"} -->
				<p class="ik-resume__location">Jakarta, Indonesia &middot; remote</p>
				<!-- /wp:paragraph -->
			</aside>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->

	<!-- wp:paragraph {"className":"ik-resume__summary"} -->
	<p class="ik-resume__summary">Web engineer focused on WordPress, web performance, AI-assisted workflows, and developer tooling. Over a decade building content publishing platforms, contributing to WordPress core and plugins, and supporting the WordPress community in Indonesia through talks and events.</p>
	<!-- /wp:paragraph -->
</header>
<!-- /wp:group -->