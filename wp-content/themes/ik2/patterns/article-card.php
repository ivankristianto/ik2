<?php
/**
 * Title: Article card
 * Slug: ik2/article-card
 * Categories: ik2-archive
 * Description: Card used inside the Articles archive Query Loop — coloured cover, meta line, title, excerpt, and categories.
 *
 * @package IK2
 */

?>
<!-- wp:group {"tagName":"article","className":"ik-article-card","layout":{"type":"default"}} -->
<article class="wp-block-group ik-article-card">

	<!-- wp:group {"className":"ik-article-card__cover","layout":{"type":"default"}} -->
	<div class="wp-block-group ik-article-card__cover">
		<!-- wp:post-terms {"term":"category","prefix":"","separator":" · ","className":"ik-article-card__cover-label"} /-->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"ik-article-card__meta","layout":{"type":"flex","flexWrap":"wrap"}} -->
	<div class="wp-block-group ik-article-card__meta">
		<!-- wp:post-date {"format":"F j, Y","className":"ik-article-card__date"} /-->
	</div>
	<!-- /wp:group -->

	<!-- wp:post-title {"isLink":true,"level":2,"className":"ik-article-card__title"} /-->

	<!-- wp:post-excerpt {"excerptLength":26,"className":"ik-article-card__excerpt","moreText":""} /-->

	<!-- wp:post-terms {"term":"post_tag","prefix":"","separator":" ","className":"ik-article-card__tags"} /-->
</article>
<!-- /wp:group -->
