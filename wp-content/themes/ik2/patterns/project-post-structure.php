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
<p class="ik-article__lead">Start with the shortest honest version: what this project is, where it runs, and why I built it.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'Context', 'ik2' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'Explain the bottleneck, decision, or repeated task that pushed this from idea to implementation.', 'ik2' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'What shipped', 'ik2' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul>
	<li><?php esc_html_e( 'Call out the core workflow or capability.', 'ik2' ); ?></li>
	<li><?php esc_html_e( 'Note the implementation detail that mattered most.', 'ik2' ); ?></li>
	<li><?php esc_html_e( 'Mention the result, metric, or user-facing win.', 'ik2' ); ?></li>
</ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'Trade-offs', 'ik2' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'Be specific about what stayed intentionally simple, what is still rough, and what you decided not to build yet.', 'ik2' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'What I would change next', 'ik2' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'Close with the next improvement you would make if the project needed another iteration.', 'ik2' ); ?></p>
<!-- /wp:paragraph -->
