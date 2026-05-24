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
				<!-- wp:site-title {"level":0,"className":"ik-wordmark"} /-->
				<!-- wp:paragraph {"className":"ik-footer__tagline","fontSize":"sm"} -->
				<p class="ik-footer__tagline has-sm-font-size"><?php esc_html_e( 'Exploring WordPress, AI, performance, and developer tooling.', 'ik2' ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"className":"ik-footer__handle"} -->
				<p class="ik-footer__handle">// @ivankristianto on the web</p>
				<!-- /wp:paragraph -->
				<!-- wp:html -->
				<nav class="ik-footer__social" aria-label="<?php esc_attr_e( 'Social', 'ik2' ); ?>">
					<a href="https://github.com/ivankristianto" aria-label="GitHub" rel="me noopener noreferrer" target="_blank"><svg viewBox="0 0 16 16" width="18" height="18" aria-hidden="true" focusable="false"><path fill="currentColor" fill-rule="evenodd" d="M8 0C3.58 0 0 3.58 0 8a8 8 0 0 0 5.47 7.59c.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.01 8.01 0 0 0 16 8c0-4.42-3.58-8-8-8z"></path></svg></a>
					<a href="https://linkedin.com/in/ivankristianto" aria-label="LinkedIn" rel="me noopener noreferrer" target="_blank"><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="currentColor" d="M4.98 3.5C4.98 4.88 3.87 6 2.5 6S0 4.88 0 3.5 1.12 1 2.5 1s2.48 1.12 2.48 2.5zM.22 8h4.56v15.5H.22V8zm7.62 0h4.38v2.12h.06c.61-1.15 2.1-2.36 4.32-2.36 4.62 0 5.47 3.04 5.47 7v8.74h-4.56v-7.75c0-1.85-.04-4.24-2.58-4.24-2.59 0-2.98 2.02-2.98 4.1v7.89H7.84V8z"></path></svg></a>
					<a href="https://x.com/ivankristianto" aria-label="X (formerly Twitter)" rel="me noopener noreferrer" target="_blank"><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="currentColor" d="M18.244 2H21.5l-7.55 8.63L23 22h-6.93l-5.42-7.09L4.4 22H1.14l8.08-9.22L1 2h7.07l4.9 6.48L18.24 2zm-2.43 18h1.88L7.27 4H5.25l10.56 16z"></path></svg></a>
					<a href="https://profiles.wordpress.org/ivankristianto/" aria-label="WordPress.org" rel="me noopener noreferrer" target="_blank"><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 .5C5.65.5.5 5.65.5 12S5.65 23.5 12 23.5 23.5 18.35 23.5 12 18.35.5 12 .5zM1.92 12c0-1.46.31-2.85.87-4.1l4.79 13.13A10.08 10.08 0 0 1 1.92 12zm10.08 10.08c-.99 0-1.95-.14-2.86-.41l3.04-8.83 3.11 8.52c.02.05.04.1.07.15a10.04 10.04 0 0 1-3.36.57zm1.4-14.83c.61-.03 1.16-.1 1.16-.1.55-.06.49-.87-.06-.84 0 0-1.65.13-2.71.13-1 0-2.68-.13-2.68-.13-.55-.03-.61.81-.06.84 0 0 .52.06 1.07.1l1.59 4.35-2.23 6.69-3.71-11.04c.61-.03 1.16-.1 1.16-.1.55-.06.49-.87-.06-.84 0 0-1.65.13-2.71.13l-.41-.01A10.06 10.06 0 0 1 12 1.92c2.61 0 4.99.99 6.78 2.61h-.18c-.99 0-1.69.86-1.69 1.79 0 .83.48 1.53.99 2.36.39.67.84 1.53.84 2.77 0 .86-.33 1.86-.77 3.25l-1.01 3.37-3.66-10.82zm7.18 9.62l3.01-8.7c.56-1.41.75-2.53.75-3.53 0-.36-.02-.7-.07-1.01a10.05 10.05 0 0 1-3.69 13.24z"></path></svg></a>
				</nav>
				<!-- /wp:html -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"className":"ik-footer__col"} -->
			<div class="wp-block-column ik-footer__col">
				<!-- wp:html -->
				<nav aria-labelledby="ik-footer-site">
					<h3 id="ik-footer-site" class="wp-block-heading"><?php esc_html_e( 'Site', 'ik2' ); ?></h3>
					<ul class="wp-block-list">
						<li><a href="/"><?php esc_html_e( 'Home', 'ik2' ); ?></a></li>
						<li><a href="/articles/"><?php esc_html_e( 'Articles', 'ik2' ); ?></a></li>
						<li><a href="/projects/"><?php esc_html_e( 'Projects', 'ik2' ); ?></a></li>
						<li><a href="/speaking/"><?php esc_html_e( 'Speaking', 'ik2' ); ?></a></li>
						<li><a href="/about/"><?php esc_html_e( 'About', 'ik2' ); ?></a></li>
						<li><a href="/contact/"><?php esc_html_e( 'Contact', 'ik2' ); ?></a></li>
						<li><a href="/resume/"><?php esc_html_e( 'Resume', 'ik2' ); ?></a></li>
					</ul>
				</nav>
				<!-- /wp:html -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"className":"ik-footer__col"} -->
			<div class="wp-block-column ik-footer__col">
				<!-- wp:html -->
				<nav aria-labelledby="ik-footer-topics">
					<h3 id="ik-footer-topics" class="wp-block-heading"><?php esc_html_e( 'Topics', 'ik2' ); ?></h3>
					<ul class="wp-block-list">
						<li><a href="/category/wordpress/">WordPress</a></li>
						<li><a href="/category/ai/">AI</a></li>
						<li><a href="/category/performance/"><?php esc_html_e( 'Performance', 'ik2' ); ?></a></li>
						<li><a href="/category/web-apis/"><?php esc_html_e( 'Web APIs', 'ik2' ); ?></a></li>
						<li><a href="/category/tooling/"><?php esc_html_e( 'Tooling', 'ik2' ); ?></a></li>
					</ul>
				</nav>
				<!-- /wp:html -->
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
				<a href="/feed/" rel="alternate"><?php esc_html_e( 'RSS feed', 'ik2' ); ?></a>
				<a href="/privacy-policy/"><?php esc_html_e( 'Privacy', 'ik2' ); ?></a>
			</nav>
			<!-- /wp:html -->
		</div>
		<!-- /wp:group -->

	</div>
	<!-- /wp:group -->
</footer>
<!-- /wp:group -->
