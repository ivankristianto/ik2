<?php
/**
 * Title: Single article — header
 * Slug: ik2/single-article-header
 * Categories: ik2-page
 * Description: Title, meta line (date · reading time · categories) for the single post template.
 *
 * @package IK2
 */

?>
<!-- wp:group {"tagName":"header","className":"ik-article__head","layout":{"type":"default"},"metadata":{"name":"Article header"}} -->
<header class="wp-block-group ik-article__head">
	<!-- wp:post-title {"level":1,"className":"ik-article__title"} /-->

	<!-- wp:group {"className":"ik-article__meta","layout":{"type":"flex","flexWrap":"wrap"}} -->
	<div class="wp-block-group ik-article__meta">
		<!-- wp:post-date {"format":"F j, Y","className":"ik-article__date"} /-->

		<!-- wp:paragraph {"className":"ik-article__sep"} -->
		<p class="ik-article__sep" aria-hidden="true">·</p>
		<!-- /wp:paragraph -->

		<!-- wp:post-terms {"term":"category","separator":" ","className":"ik-article__tags"} /-->
	</div>
	<!-- /wp:group -->
</header>
<!-- /wp:group -->
