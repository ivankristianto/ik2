<?php
/**
 * Title: Article card
 * Slug: ik2/article-card
 * Categories: ik2-archive
 * Inserter: no
 * Description: Row used inside the Articles archive Query Loop: date and category pills in a margin column, then title, excerpt, and tags. Renders only inside a post Query Loop, so it is hidden from the manual inserter.
 *
 * @package IK2
 */

?>
<!-- wp:group {"tagName":"article","className":"ik-article-card","layout":{"type":"default"}} -->
<article class="wp-block-group ik-article-card">

	<!-- wp:group {"className":"ik-article-card__side","layout":{"type":"default"}} -->
	<div class="wp-block-group ik-article-card__side">
		<!-- wp:post-date {"className":"ik-article-card__date"} /-->

		<!-- wp:post-terms {"term":"category","prefix":"","separator":" ","className":"ik-article-card__category"} /-->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"ik-article-card__main","layout":{"type":"default"}} -->
	<div class="wp-block-group ik-article-card__main">
		<!-- wp:post-title {"isLink":true,"level":2,"className":"ik-article-card__title"} /-->

		<!-- wp:post-excerpt {"excerptLength":26,"className":"ik-article-card__excerpt","moreText":""} /-->

		<!-- wp:post-terms {"term":"post_tag","prefix":"","separator":" ","className":"ik-article-card__tags"} /-->
	</div>
	<!-- /wp:group -->
</article>
<!-- /wp:group -->
