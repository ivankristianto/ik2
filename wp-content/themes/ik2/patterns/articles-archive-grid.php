<?php
/**
 * Title: Articles — Archive grid
 * Slug: ik2/articles-archive-grid
 * Categories: ik2-archive
 * Description: Filters bar, 3-column Query Loop of posts, and pagination.
 *
 * @package IK2
 */

?>
<!-- wp:html -->
<div
	class="ik-articles-archive__interactive"
	data-wp-interactive="ik2/articles-filters"
	data-wp-router-region="ik-articles"
>
<!-- /wp:html -->

	<!-- wp:pattern {"slug":"ik2/articles-archive-header"} /-->

	<!-- wp:ik2/articles-filters {} /-->

	<!-- wp:query {"queryId":42,"query":{"perPage":9,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","inherit":false},"enhancedPagination":true,"className":"ik-articles-archive__query"} -->
	<div class="wp-block-query ik-articles-archive__query">

		<!-- wp:post-template {"className":"ik-articles-grid"} -->
			<!-- wp:pattern {"slug":"ik2/article-card"} /-->
		<!-- /wp:post-template -->

		<!-- wp:query-pagination {"className":"ik-articles-pagination","layout":{"type":"flex","justifyContent":"space-between"}} -->
			<!-- wp:query-pagination-previous {"label":"← Prev"} /-->
			<!-- wp:query-pagination-numbers /-->
			<!-- wp:query-pagination-next {"label":"Next →"} /-->
		<!-- /wp:query-pagination -->

		<!-- wp:query-no-results -->
			<!-- wp:paragraph {"className":"ik-articles-empty"} -->
			<p class="ik-articles-empty"><?php esc_html_e( 'No posts match these filters yet — try widening.', 'ik2' ); ?></p>
			<!-- /wp:paragraph -->
		<!-- /wp:query-no-results -->

	</div>
	<!-- /wp:query -->

<!-- wp:html -->
</div>
<!-- /wp:html -->
