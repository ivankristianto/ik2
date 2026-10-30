<?php
/**
 * Title: Single article, related
 * Slug: ik2/single-article-related
 * Categories: ik2-page
 * Inserter: no
 * Description: Four related posts, a lead with its category cover and excerpt, then three rows. Filled by the queryId 44 filter in inc/blocks.php. Hidden when there are none.
 *
 * @package IK2
 */

?>
<!-- wp:query {"queryId":44,"query":{"perPage":4,"pages":1,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"tagName":"aside","align":"wide","className":"ik-related"} -->
<aside class="wp-block-query alignwide ik-related">
	<!-- wp:group {"className":"ik-related__head","layout":{"type":"default"}} -->
	<div class="wp-block-group ik-related__head">
		<!-- wp:heading {"level":2,"className":"ik-related__title"} -->
		<h2 class="wp-block-heading ik-related__title"><?php esc_html_e( 'Keep reading', 'ik2' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"ik-related__note"} -->
		<p class="ik-related__note"><?php esc_html_e( 'closest to this post', 'ik2' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:post-template {"className":"ik-related__list","layout":{"type":"default"}} -->
	<!-- wp:group {"className":"ik-related__item","layout":{"type":"default"}} -->
	<div class="wp-block-group ik-related__item">
		<!-- wp:group {"className":"ik-related__cover","layout":{"type":"default"}} -->
		<div class="wp-block-group ik-related__cover">
			<!-- wp:post-terms {"term":"category","prefix":"","separator":" · ","className":"ik-related__cover-label"} /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"ik-related__meta","layout":{"type":"default"}} -->
		<div class="wp-block-group ik-related__meta">
			<!-- wp:post-date {"className":"ik-related__date"} /-->

			<!-- wp:post-time-to-read {"displayAsRange":false,"className":"ik-related__reading"} /-->

			<!-- wp:post-terms {"term":"category","prefix":"","separator":", ","className":"ik-related__category"} /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:post-title {"level":3,"isLink":true,"className":"ik-related__name"} /-->

		<!-- wp:post-excerpt {"excerptLength":26,"moreText":"","className":"ik-related__excerpt"} /-->
	</div>
	<!-- /wp:group -->
	<!-- /wp:post-template -->
</aside>
<!-- /wp:query -->
