<?php
/**
 * Title: Home — Latest notes
 * Slug: ik2/home-latest-notes
 * Categories: ik2-home
 * Viewport Width: 1400
 * Description: Two-column section — a Query Loop of the latest note posts beside the /now card.
 *
 * @package IK2
 */

$ik2_note_cat = get_category_by_slug( 'note' );
$ik2_note_id  = $ik2_note_cat instanceof WP_Term ? (int) $ik2_note_cat->term_id : 0;

?>
<!-- wp:group {"tagName":"section","align":"full","className":"ik-section","layout":{"type":"constrained","contentSize":"var(--wp--custom--width--full)"}} -->
<section class="wp-block-group alignfull ik-section">
	<!-- wp:group {"className":"ik-section__head","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
	<div class="wp-block-group ik-section__head">
		<!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
		<div class="wp-block-group">
			<!-- wp:paragraph {"className":"ik-section__eyebrow"} -->
			<p class="ik-section__eyebrow">// LATEST NOTES · TIL · EXPERIMENTS · LINKS</p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"level":2,"className":"ik-section__title"} -->
			<h2 class="wp-block-heading ik-section__title">What I've been working on</h2>
			<!-- /wp:heading -->
		</div>
		<!-- /wp:group -->

		<!-- wp:paragraph {"className":"ik-section__more"} -->
		<p class="ik-section__more"><a href="<?php echo esc_url( home_url( '/articles' ) ); ?>">All articles →</a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:columns {"className":"ik-notes-layout"} -->
	<div class="wp-block-columns ik-notes-layout">
		<!-- wp:column {"className":"ik-notes-layout__main"} -->
		<div class="wp-block-column ik-notes-layout__main">
			<!-- wp:query {"queryId":11,"query":{"perPage":6,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false,"taxQuery":{"category":[<?php echo (int) $ik2_note_id; ?>]}}} -->
			<div class="wp-block-query">
				<!-- wp:post-template {"className":"ik-notes-list","layout":{"type":"default"}} -->
				<!-- wp:group {"className":"ik-note","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
				<div class="wp-block-group ik-note">
					<!-- wp:post-date {"format":"F j, Y","className":"ik-note__date"} /-->

					<!-- wp:group {"className":"ik-note__body","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
					<div class="wp-block-group ik-note__body">
						<!-- wp:post-title {"level":3,"isLink":true,"className":"ik-note__title"} /-->

						<!-- wp:post-excerpt {"className":"ik-note__excerpt"} /-->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->
				<!-- /wp:post-template -->

				<!-- wp:query-no-results -->
				<!-- wp:paragraph -->
				<p>No notes yet.</p>
				<!-- /wp:paragraph -->
				<!-- /wp:query-no-results -->
			</div>
			<!-- /wp:query -->

			<!-- wp:paragraph {"className":"ik-notes-layout__more"} -->
			<p class="ik-notes-layout__more"><a href="<?php echo esc_url( home_url( '/articles' ) ); ?>">Read every note →</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"className":"ik-notes-layout__aside"} -->
		<div class="wp-block-column ik-notes-layout__aside">
			<!-- wp:group {"tagName":"aside","className":"ik-now","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
			<aside class="wp-block-group ik-now">
				<!-- wp:paragraph {"className":"ik-now__head"} -->
				<p class="ik-now__head"><span class="ik-now__dot" aria-hidden="true"></span><span class="ik-now__label">// /now</span><span class="ik-now__date">May 12, 2026</span></p>
				<!-- /wp:paragraph -->

				<!-- wp:group {"className":"ik-now__group","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
				<div class="wp-block-group ik-now__group">
					<!-- wp:paragraph {"className":"ik-now__group-title"} -->
					<p class="ik-now__group-title">Currently building</p>
					<!-- /wp:paragraph -->

					<!-- wp:paragraph {"className":"ik-now__item"} -->
					<p class="ik-now__item"><code>ivankristianto-theme</code> — rebuilding this site as a block theme.</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"className":"ik-now__group","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
				<div class="wp-block-group ik-now__group">
					<!-- wp:paragraph {"className":"ik-now__group-title"} -->
					<p class="ik-now__group-title">Currently reading</p>
					<!-- /wp:paragraph -->

					<!-- wp:paragraph {"className":"ik-now__item"} -->
					<p class="ik-now__item"><em>Designing Data-Intensive Applications</em>, Kleppmann — for the third time.</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"className":"ik-now__group","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
				<div class="wp-block-group ik-now__group">
					<!-- wp:paragraph {"className":"ik-now__group-title"} -->
					<p class="ik-now__group-title">Currently learning</p>
					<!-- /wp:paragraph -->

					<!-- wp:paragraph {"className":"ik-now__item"} -->
					<p class="ik-now__item">The WordPress Interactivity API — instant search + a real command palette.</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"className":"ik-now__group","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
				<div class="wp-block-group ik-now__group">
					<!-- wp:paragraph {"className":"ik-now__group-title"} -->
					<p class="ik-now__group-title">Listening</p>
					<!-- /wp:paragraph -->

					<!-- wp:paragraph {"className":"ik-now__item"} -->
					<p class="ik-now__item">The Changelog · Syntax.fm · WP Tavern Jukebox</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

				<!-- wp:paragraph {"className":"ik-now__foot"} -->
				<p class="ik-now__foot">Inspired by <a href="https://nownownow.com" target="_blank" rel="noreferrer">/now</a>. Updated when the world changes — not on a schedule.</p>
				<!-- /wp:paragraph -->
			</aside>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</section>
<!-- /wp:group -->
