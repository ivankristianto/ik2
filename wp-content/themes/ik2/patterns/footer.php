<?php
/**
 * Title: Footer
 * Slug: ik2/footer
 * Categories: footer
 * Block Types: core/template-part/footer
 * Description: Site footer with brand column, site/topic link columns, social icons and bottom bar.
 *
 * @package IK2
 */

?>
<!-- wp:group {"tagName":"footer","className":"ik-footer","backgroundColor":"soft-paper","style":{"spacing":{"padding":{"top":"var:preset|spacing|9","bottom":"var:preset|spacing|6"}}},"layout":{"type":"default"}} -->
<footer class="wp-block-group ik-footer has-soft-paper-background-color has-background" style="padding-top:var(--wp--preset--spacing--9);padding-bottom:var(--wp--preset--spacing--6)">
	<!-- wp:group {"className":"container-full","layout":{"type":"default"}} -->
	<div class="wp-block-group container-full">

		<!-- wp:columns {"className":"ik-footer__columns"} -->
		<div class="wp-block-columns ik-footer__columns">

			<!-- wp:column {"className":"ik-footer__brand"} -->
			<div class="wp-block-column ik-footer__brand">
				<!-- wp:html -->
				<div class="ik-wordmark" style="font-size:1.0625rem">ivan</div>
				<!-- /wp:html -->
				<!-- wp:paragraph {"className":"ik-footer__tagline","fontSize":"sm"} -->
				<p class="ik-footer__tagline has-sm-font-size"><?php esc_html_e( 'Exploring WordPress, AI, performance, and developer tooling.', 'ik2' ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"className":"ik-footer__handle"} -->
				<p class="ik-footer__handle">// @ivankristianto on the web</p>
				<!-- /wp:paragraph -->
				<!-- wp:html -->
				<nav class="ik-footer__social" aria-label="<?php esc_attr_e( 'Social', 'ik2' ); ?>">
					<a href="https://github.com/ivankristianto" aria-label="GitHub" rel="me noopener" target="_blank"><svg viewBox="0 0 16 16" width="18" height="18" aria-hidden="true" focusable="false"><path fill="currentColor" fill-rule="evenodd" d="M8 0C3.58 0 0 3.58 0 8a8 8 0 0 0 5.47 7.59c.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.01 8.01 0 0 0 16 8c0-4.42-3.58-8-8-8z"></path></svg></a>
					<a href="https://linkedin.com/in/ivankristianto" aria-label="LinkedIn" rel="me noopener" target="_blank"><span class="dashicons dashicons-linkedin" aria-hidden="true"></span></a>
					<a href="https://twitter.com/ivankristianto" aria-label="Twitter / X" rel="me noopener" target="_blank"><span class="dashicons dashicons-twitter" aria-hidden="true"></span></a>
					<a href="https://profiles.wordpress.org/ivankristianto/" aria-label="WordPress.org" rel="me noopener" target="_blank"><span class="dashicons dashicons-wordpress" aria-hidden="true"></span></a>
				</nav>
				<!-- /wp:html -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"className":"ik-footer__col"} -->
			<div class="wp-block-column ik-footer__col">
				<!-- wp:heading {"level":4} --><h4 class="wp-block-heading"><?php esc_html_e( 'Site', 'ik2' ); ?></h4><!-- /wp:heading -->
				<!-- wp:list -->
				<ul class="wp-block-list">
					<li><a href="/"><?php esc_html_e( 'Home', 'ik2' ); ?></a></li>
					<li><a href="/articles"><?php esc_html_e( 'Articles', 'ik2' ); ?></a></li>
					<li><a href="/projects"><?php esc_html_e( 'Projects', 'ik2' ); ?></a></li>
					<li><a href="/speaking"><?php esc_html_e( 'Speaking', 'ik2' ); ?></a></li>
					<li><a href="/about"><?php esc_html_e( 'About', 'ik2' ); ?></a></li>
					<li><a href="/contact"><?php esc_html_e( 'Contact', 'ik2' ); ?></a></li>
					<li><a href="/resume"><?php esc_html_e( 'Resume', 'ik2' ); ?></a></li>
				</ul>
				<!-- /wp:list -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"className":"ik-footer__col"} -->
			<div class="wp-block-column ik-footer__col">
				<!-- wp:heading {"level":4} --><h4 class="wp-block-heading"><?php esc_html_e( 'Topics', 'ik2' ); ?></h4><!-- /wp:heading -->
				<!-- wp:list -->
				<ul class="wp-block-list">
					<li><a href="/category/wordpress">WordPress</a></li>
					<li><a href="/category/ai">AI</a></li>
					<li><a href="/category/performance"><?php esc_html_e( 'Performance', 'ik2' ); ?></a></li>
					<li><a href="/category/web-apis"><?php esc_html_e( 'Web APIs', 'ik2' ); ?></a></li>
					<li><a href="/category/tooling"><?php esc_html_e( 'Tooling', 'ik2' ); ?></a></li>
				</ul>
				<!-- /wp:list -->
			</div>
			<!-- /wp:column -->

		</div>
		<!-- /wp:columns -->

		<!-- wp:group {"className":"ik-footer__bottom","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
		<div class="wp-block-group ik-footer__bottom">
			<!-- wp:paragraph {"fontSize":"xs"} -->
			<p class="has-xs-font-size">&copy; 2010&ndash;<?php echo esc_html( gmdate( 'Y' ) ); ?> Ivan Kristianto. <?php esc_html_e( 'All thoughts my own.', 'ik2' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:html -->
			<nav class="ik-footer__meta" aria-label="<?php esc_attr_e( 'Site', 'ik2' ); ?>">
				<a href="/feed"><?php esc_html_e( 'RSS feed', 'ik2' ); ?></a>
				<a href="/privacy-policy"><?php esc_html_e( 'Privacy', 'ik2' ); ?></a>
			</nav>
			<!-- /wp:html -->
		</div>
		<!-- /wp:group -->

	</div>
	<!-- /wp:group -->
</footer>
<!-- /wp:group -->
