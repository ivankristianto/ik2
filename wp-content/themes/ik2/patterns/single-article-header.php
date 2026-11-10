<?php
/**
 * Title: Single article — header
 * Slug: ik2/single-article-header
 * Categories: ik2-page
 * Inserter: no
 * Description: Title, then the meta: date · reading time · byline on one line, and the updated date on its own line when the post changed on a later day. The category lives in the breadcrumb trail above the title. Depends on the current post, so it is hidden from the manual inserter.
 *
 * @package IK2
 */

?>
<!-- wp:group {"tagName":"header","className":"ik-article__head","layout":{"type":"default"},"metadata":{"name":"Article header"}} -->
<header class="wp-block-group ik-article__head">
	<!-- wp:post-title {"level":1,"className":"ik-article__title"} /-->

	<!-- wp:group {"className":"ik-article__meta","layout":{"type":"default"}} -->
	<div class="wp-block-group ik-article__meta">
		<!-- wp:group {"className":"ik-article__meta-line","layout":{"type":"default"}} -->
		<div class="wp-block-group ik-article__meta-line">
			<!-- wp:post-date {"className":"ik-article__date"} /-->

			<!-- wp:post-time-to-read {"displayAsRange":false,"className":"ik-article__reading"} /-->

			<!-- wp:post-author-name {"isLink":true,"className":"ik-article__byline"} /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:post-date {"displayType":"modified","className":"ik-article__updated"} /-->
	</div>
	<!-- /wp:group -->
</header>
<!-- /wp:group -->
