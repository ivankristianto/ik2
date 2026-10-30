<?php
/**
 * Title: Single article — related
 * Slug: ik2/single-article-related
 * Categories: ik2-page
 * Inserter: no
 * Description: Three more posts from the current post's category, filled by the queryId 44 filter in inc/blocks.php. Hidden when there are none.
 *
 * @package IK2
 */

?>
<!-- wp:query {"queryId":44,"query":{"perPage":3,"pages":1,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"tagName":"aside","className":"ik-related"} -->
<aside class="wp-block-query ik-related">
	<!-- wp:heading {"level":2,"className":"ik-related__title"} -->
	<h2 class="wp-block-heading ik-related__title">Keep reading</h2>
	<!-- /wp:heading -->

	<!-- wp:post-template {"className":"ik-related__list","layout":{"type":"default"}} -->
	<!-- wp:group {"className":"ik-related__item","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
	<div class="wp-block-group ik-related__item">
		<!-- wp:post-title {"level":3,"isLink":true,"className":"ik-related__name"} /-->

		<!-- wp:post-date {"className":"ik-related__date"} /-->
	</div>
	<!-- /wp:group -->
	<!-- /wp:post-template -->
</aside>
<!-- /wp:query -->
