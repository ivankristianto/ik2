<?php
/**
 * Title: Project post structure
 * Slug: ik2/project-post-structure
 * Categories: ik2-page
 * Post Types: project
 * Description: Starter structure for Project write-ups.
 *
 * @package IK2
 */

?>
<!-- wp:paragraph {"className":"ik-article__lead"} -->
<p class="ik-article__lead">The shortest honest version of what this is: a one-paragraph statement of what shipped, where it runs, and the reason it exists. Treat the rest of the page as the long-form receipts.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'Why I built it', 'ik2' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'The bottleneck, the repeated chore, or the decision that pushed this from a Notes app idea to a public repo. Be concrete: what were you trying to do, what was slow or wrong, and what was the smallest thing that would have solved it.', 'ik2' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'What shipped', 'ik2' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'The actual surface. The capability that matters. The implementation detail you would mention if a colleague asked. Plain language; reach for a list only if the items are genuinely parallel.', 'ik2' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:list -->
<ul>
	<li><?php esc_html_e( 'Core workflow or capability in one sentence.', 'ik2' ); ?></li>
	<li><?php esc_html_e( 'The implementation choice that mattered most.', 'ik2' ); ?></li>
	<li><?php esc_html_e( 'The measurable result, or the qualitative win.', 'ik2' ); ?></li>
</ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'How it works', 'ik2' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'The interesting bits of the implementation. A code snippet here is fair game if it carries weight. Skip the boilerplate; show the one thing that took longest to get right.', 'ik2' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'Trade-offs', 'ik2' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'Be specific about what stayed intentionally simple, what is still rough, and what you decided not to build yet. Own the gaps; do not pretend the thing is finished if it is not.', 'ik2' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'What I would change next', 'ik2' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'The next improvement you would make if the project needed another iteration. One paragraph, not a roadmap.', 'ik2' ); ?></p>
<!-- /wp:paragraph -->
