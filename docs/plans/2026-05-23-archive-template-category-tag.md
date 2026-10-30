# Archive Template for Category and Tag Pages — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a single `archive.html` block template that powers both category and tag archives, reusing the same layout/filter/header DOM as `page-articles.html`, with filter pills driven by pretty URLs and the Interactivity API router (no query strings).

**Architecture:** Topic pills are full-navigation `<a href>` links between archive contexts (`/articles/`, `/category/{slug}/`, `/tag/{slug}/`). Format pills are Interactivity-API-router actions that swap the grid in-place. Rewrite rules translate the pretty `/format/{slug}/` segment into a `format` query var that the existing `query_loop_block_query_vars` filter applies as a category tax_query.

**Tech Stack:** WordPress 6.x block theme, PHP 8.4, `@wordpress/scripts` 30.x, `@wordpress/interactivity` 6.x + `@wordpress/interactivity-router`, Sass.

**Spec reference:** `docs/superpowers/specs/2026-05-23-archive-template-category-tag-design.md`

---

## File Structure

### New files

| Path | Purpose |
|---|---|
| `wp-content/themes/ik2/templates/archive.html` | Block template used for category and tag archives. |
| `wp-content/themes/ik2/patterns/archive-header.php` | Dynamic header pattern — reads `get_queried_object()` and renders eyebrow/title/lede from the queried term. |
| `wp-content/themes/ik2/patterns/archive-grid.php` | Filter block + Query Loop with `inherit:true` (queryId 43) wrapped in a router region. |
| `wp-content/themes/ik2/blocks/articles-filters/view.js` | Interactivity store registration + import of `@wordpress/interactivity-router`. |

### Modified files

| Path | Change |
|---|---|
| `wp-content/themes/ik2/blocks/articles-filters/block.json` | Add `supports.interactivity` and `viewScriptModule`. Update description. |
| `wp-content/themes/ik2/blocks/articles-filters/render.php` | Replace query-string URL building with context-aware pretty URLs; add IAPI directives to format pills only; derive active state from queried object. |
| `wp-content/themes/ik2/blocks/articles-filters/style.css` | No functional changes — placeholder if any new state classes are needed (unlikely). |
| `wp-content/themes/ik2/patterns/articles-archive-grid.php` | Wrap the existing markup in a `data-wp-interactive` + `data-wp-router-region` container so the page-articles archive also gets in-place format swapping. |
| `wp-content/themes/ik2/inc/Blocks.php` | Add `ARCHIVE_QUERY_ID = 43`; drop `topic` handling; apply `format` to both queryIds 42 and 43; register `format` query var; add three rewrite rules; flush on theme switch. |
| `webpack.config.js` | Merge default `@wordpress/scripts` block-discovery entries with the existing `index`/`editor` entries so `view.js` modules get bundled. |

---

## Task 1: Webpack entry merge so IAPI view modules build

**Files:**
- Modify: `webpack.config.js`

The current config sets `entry: { index, editor }` and so suppresses wp-scripts's default block discovery. We need to merge them so `blocks/articles-filters/view.js` (and the existing `src/interactivity/cmd-palette/view.js`) get bundled.

- [ ] **Step 1: Update webpack.config.js**

Replace the contents of `webpack.config.js` with:

```js
const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

const defaultEntries =
	typeof defaultConfig.entry === 'function'
		? defaultConfig.entry()
		: defaultConfig.entry || {};

module.exports = {
	...defaultConfig,
	entry: {
		...defaultEntries,
		index: path.resolve( __dirname, 'wp-content/themes/ik2/src/index.js' ),
		editor: path.resolve( __dirname, 'wp-content/themes/ik2/src/editor.js' ),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'wp-content/themes/ik2/build' ),
	},
};
```

- [ ] **Step 2: Verify the build still produces existing output**

Run: `docker compose --profile tools run --rm pnpm build`
Expected: completes without error; `wp-content/themes/ik2/build/index.js` and `wp-content/themes/ik2/build/editor.js` still exist.

- [ ] **Step 3: Commit**

```bash
git add webpack.config.js
git commit -m "build: merge default block entries into theme webpack config

So viewScriptModule bundles for theme blocks (articles-filters, cmd-palette)
are produced alongside the existing index/editor entries."
```

---

## Task 2: Add `format` query var + rewrite rules in `inc/Blocks.php`

**Files:**
- Modify: `wp-content/themes/ik2/inc/Blocks.php`

Adds the three rewrite rules and registers `format` as a public query var. Does **not** yet touch the `query_loop_block_query_vars` filter (Task 3) — keep changes isolated.

- [ ] **Step 1: Read the current file**

Run: `cat wp-content/themes/ik2/inc/Blocks.php`
Expected: shows the existing `register_block_type` loop and the `query_loop_block_query_vars` filter using `topic`/`format` from `$_GET`.

- [ ] **Step 2: Add the archive query id constant**

In `wp-content/themes/ik2/inc/Blocks.php`, change:

```php
const ARTICLES_QUERY_ID = 42;
```

to:

```php
const ARTICLES_QUERY_ID = 42;
const ARCHIVE_QUERY_ID  = 43;
```

- [ ] **Step 3: Register the `format` public query var**

Append at the bottom of `wp-content/themes/ik2/inc/Blocks.php` (before any closing tag — file has no closing `?>`):

```php
/**
 * Register `format` as a public query var so the rewrite rules below
 * can populate it from the URL path.
 *
 * @param array<int,string> $vars Public query vars.
 * @return array<int,string>
 */
add_filter(
	'query_vars',
	static function ( array $vars ): array {
		$vars[] = 'format';
		return $vars;
	}
);
```

- [ ] **Step 4: Add the three rewrite rules**

Append below the `query_vars` filter:

```php
/**
 * Pretty URLs for the format filter.
 *
 *   /articles/format/{slug}/                 → pagename=articles&format={slug}
 *   /category/{cat}/format/{slug}/           → category_name={cat}&format={slug}
 *   /tag/{tag}/format/{slug}/                → tag={tag}&format={slug}
 *
 * `top` priority ensures these win against WP defaults like
 * `/category/{slug}/page/{n}/` and `/category/{slug}/feed/`.
 */
add_action(
	'init',
	static function (): void {
		add_rewrite_rule(
			'^articles/format/([^/]+)/?$',
			'index.php?pagename=articles&format=$matches[1]',
			'top'
		);
		add_rewrite_rule(
			'^category/([^/]+)/format/([^/]+)/?$',
			'index.php?category_name=$matches[1]&format=$matches[2]',
			'top'
		);
		add_rewrite_rule(
			'^tag/([^/]+)/format/([^/]+)/?$',
			'index.php?tag=$matches[1]&format=$matches[2]',
			'top'
		);
	}
);

/**
 * Flush rewrite rules once when the theme is activated so the new
 * rules above register with the rewrite cache.
 */
add_action(
	'after_switch_theme',
	static function (): void {
		flush_rewrite_rules();
	}
);
```

- [ ] **Step 5: Manually flush rewrite rules in the running stack**

The theme is already active in dev, so `after_switch_theme` won't fire. Flush manually:

Run: `composer dev:wp:cmd -- rewrite flush --hard`
Expected: `Success: Rewrite rules flushed.`

- [ ] **Step 6: Spot-check the rewrite is alive**

Run: `composer dev:wp:cmd -- rewrite list --match=/articles/format/guide/ --format=csv | head`
Expected: a row whose match column contains `articles/format/([^/]+)` and whose query column contains `pagename=articles&format=$matches[1]`.

- [ ] **Step 7: Run PHP quality gate**

Run: `docker compose --profile tools run --rm composer quality`
Expected: PHPCS + PHPStan both pass (exit 0).

- [ ] **Step 8: Commit**

```bash
git add wp-content/themes/ik2/inc/Blocks.php
git commit -m "feat(theme): add /format/{slug}/ rewrites and archive query id

Adds pretty-URL rewrites for the format filter on the articles page,
category archives, and tag archives. Registers \`format\` as a public
query var and flushes rules on theme activation. Introduces the
ARCHIVE_QUERY_ID constant in preparation for the archive Query Loop."
```

---

## Task 3: Apply `format` to both Query Loops, drop `topic` handling

**Files:**
- Modify: `wp-content/themes/ik2/inc/Blocks.php`

The existing `query_loop_block_query_vars` filter reads `$_GET['topic']` and `$_GET['format']`. After this task it reads only the `format` public query var and applies it to both queryIds 42 and 43.

- [ ] **Step 1: Replace the filter body**

In `wp-content/themes/ik2/inc/Blocks.php`, replace the entire `add_filter( 'query_loop_block_query_vars', ... )` block with:

```php
/**
 * Apply the `format` query var to the Articles and Archive Query Loops.
 *
 * Reads `format` (populated by the rewrite rules in this file) and ANDs
 * a category tax_query onto whatever the Query Loop already has — so
 * inherit:true loops keep their category/tag context and get narrowed
 * by format on top.
 *
 * Allowed format slugs match the pills in the articles-filters block.
 *
 * @param array<string,mixed> $query Query vars for the loop.
 * @param \WP_Block           $block Block instance.
 * @return array<string,mixed>
 */
add_filter(
	'query_loop_block_query_vars',
	static function ( array $query, $block ): array {
		$context  = is_object( $block ) && isset( $block->context ) ? $block->context : array();
		$query_id = isset( $context['queryId'] ) ? (int) $context['queryId'] : 0;

		if ( ARTICLES_QUERY_ID !== $query_id && ARCHIVE_QUERY_ID !== $query_id ) {
			return $query;
		}

		$allowed_formats = array( 'guide', 'note', 'experiment' );
		$format          = (string) get_query_var( 'format', '' );

		if ( '' === $format || ! in_array( $format, $allowed_formats, true ) ) {
			return $query;
		}

		$existing_tax_query = isset( $query['tax_query'] ) && is_array( $query['tax_query'] )
			? $query['tax_query']
			: array();

		$existing_tax_query[] = array(
			'taxonomy' => 'category',
			'field'    => 'slug',
			'terms'    => array( $format ),
		);

		if ( count( $existing_tax_query ) > 1 && ! isset( $existing_tax_query['relation'] ) ) {
			$existing_tax_query['relation'] = 'AND';
		}

		$query['tax_query'] = $existing_tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query

		return $query;
	},
	10,
	2
);
```

- [ ] **Step 2: Restart the app container to bust PHP opcache**

Run: `docker compose restart app`
Expected: container restarts cleanly. (Reason: PHP opcache caches the old filter signature.)

- [ ] **Step 3: Smoke test the existing page-articles still works**

Open `http://localhost:8080/articles/` in any browser.
Expected: page renders, all posts shown, no PHP errors in `composer dev:logs`.

- [ ] **Step 4: Smoke test `/articles/format/guide/`**

Open `http://localhost:8080/articles/format/guide/`.
Expected: page renders with only "Guide"-category posts. (Active pill state isn't fixed yet — that's Task 6 — but the query should narrow.)

- [ ] **Step 5: Run PHP quality gate**

Run: `docker compose --profile tools run --rm composer quality`
Expected: PHPCS + PHPStan pass.

- [ ] **Step 6: Commit**

```bash
git add wp-content/themes/ik2/inc/Blocks.php
git commit -m "refactor(theme): switch filter to format-only, applied to both query ids

Drops the old \`topic\` query-string handling (topic is now encoded in
the archive URL itself). Reads \`format\` via get_query_var and ANDs a
category tax_query onto whatever the Query Loop has — so inherit:true
loops on category/tag archives keep their context and get narrowed by
format on top. Applies to both queryId 42 (page-articles) and 43
(archive)."
```

---

## Task 4: Archive header pattern (term-driven)

**Files:**
- Create: `wp-content/themes/ik2/patterns/archive-header.php`

Reads `get_queried_object()` and renders the same DOM classes as `articles-archive-header.php` so existing CSS applies unchanged.

- [ ] **Step 1: Create the pattern file**

Create `wp-content/themes/ik2/patterns/archive-header.php` with:

```php
<?php
/**
 * Title: Archive — Header
 * Slug: ik2/archive-header
 * Categories: ik2-archive
 * Description: Eyebrow, title, and lede built from the queried category or tag term.
 *
 * @package IK2
 */

$ik2_term = get_queried_object();

if ( ! $ik2_term instanceof WP_Term ) {
	return;
}

$ik2_taxonomy_obj   = get_taxonomy( $ik2_term->taxonomy );
$ik2_taxonomy_label = $ik2_taxonomy_obj instanceof WP_Taxonomy
	? $ik2_taxonomy_obj->labels->singular_name
	: ucfirst( $ik2_term->taxonomy );

$ik2_count       = (int) $ik2_term->count;
$ik2_description = trim( (string) term_description( $ik2_term ) );
?>
<!-- wp:group {"className":"ik-articles-archive__head","layout":{"type":"default"}} -->
<header class="wp-block-group ik-articles-archive__head">
	<!-- wp:paragraph {"className":"ik-section__eyebrow"} -->
	<p class="ik-section__eyebrow">
		<?php
		printf(
			/* translators: 1: taxonomy label (e.g. CATEGORY), 2: number of posts, 3: term name */
			esc_html__( '// %1$s  ·  %2$d POSTS  ·  %3$s', 'ik2' ),
			esc_html( strtoupper( $ik2_taxonomy_label ) ),
			$ik2_count, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html( strtoupper( $ik2_term->name ) )
		);
		?>
	</p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":1,"className":"ik-articles-archive__title"} -->
	<h1 class="wp-block-heading ik-articles-archive__title"><?php echo esc_html( $ik2_term->name ); ?></h1>
	<!-- /wp:heading -->

	<?php if ( '' !== $ik2_description ) : ?>
		<!-- wp:paragraph {"className":"ik-articles-archive__lede"} -->
		<p class="ik-articles-archive__lede"><?php echo wp_kses_post( $ik2_description ); ?></p>
		<!-- /wp:paragraph -->
	<?php endif; ?>
</header>
<!-- /wp:group -->
```

- [ ] **Step 2: Restart app container so the new pattern file is discovered**

Run: `docker compose restart app`
Expected: container restarts cleanly.

(Reason — see [feedback-opcache-restart]: WordPress scans patterns on init; for a newly added pattern file we restart so the discovery runs against an empty opcache.)

- [ ] **Step 3: Run PHP quality gate**

Run: `docker compose --profile tools run --rm composer quality`
Expected: PHPCS + PHPStan pass.

- [ ] **Step 4: Commit**

```bash
git add wp-content/themes/ik2/patterns/archive-header.php
git commit -m "feat(theme): add archive-header pattern driven by queried term

Renders the same eyebrow/title/lede DOM as the static articles archive
header, but reads label/name/count/description from the queried term
so it works for both category and tag archives."
```

---

## Task 5: Archive grid pattern (inherit:true query + router region)

**Files:**
- Create: `wp-content/themes/ik2/patterns/archive-grid.php`

Same shape as `articles-archive-grid.php` but with `inherit:true` so the main archive query (category/tag) is preserved. Wraps the markup in a `data-wp-interactive` + `data-wp-router-region` container so the IAPI router swaps filter + grid together.

- [ ] **Step 1: Create the pattern file**

Create `wp-content/themes/ik2/patterns/archive-grid.php` with:

```php
<?php
/**
 * Title: Archive — Grid
 * Slug: ik2/archive-grid
 * Categories: ik2-archive
 * Description: Filter bar plus an inherited Query Loop of posts for category and tag archives.
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

	<!-- wp:ik2/articles-filters {} /-->

	<!-- wp:query {"queryId":43,"query":{"perPage":9,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","inherit":true},"className":"ik-articles-archive__query"} -->
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
```

- [ ] **Step 2: Run PHP quality gate**

Run: `docker compose --profile tools run --rm composer quality`
Expected: PHPCS + PHPStan pass.

- [ ] **Step 3: Commit**

```bash
git add wp-content/themes/ik2/patterns/archive-grid.php
git commit -m "feat(theme): add archive-grid pattern with inherited Query Loop

Filter block + Query Loop (queryId 43, inherit:true) wrapped in an
Interactivity API router region so the format filter can swap the
grid in place. Used by templates/archive.html (next commit)."
```

---

## Task 6: Filter block — context-aware pretty URLs + IAPI directives

**Files:**
- Modify: `wp-content/themes/ik2/blocks/articles-filters/block.json`
- Modify: `wp-content/themes/ik2/blocks/articles-filters/render.php`
- Create: `wp-content/themes/ik2/blocks/articles-filters/view.js`

The render path is rewritten to:
- Detect current context (page-articles / category / tag / unknown) from `get_queried_object()`.
- Build pretty URLs for each pill from context + the other active dimension.
- Mark active pill based on context, not `$_GET`.
- Add `data-wp-on--click="actions.navigate"` to format pills only.

- [ ] **Step 1: Update `block.json`**

Replace the contents of `wp-content/themes/ik2/blocks/articles-filters/block.json` with:

```json
{
	"$schema": "https://schemas.wp.org/trunk/block.json",
	"apiVersion": 3,
	"name": "ik2/articles-filters",
	"version": "2.0.0",
	"title": "Articles Filters",
	"category": "theme",
	"description": "Topic and format filter pills for the Articles, category, and tag archives. Topic pills navigate between archives; format pills filter within the current archive via the Interactivity API router.",
	"textdomain": "ik2",
	"supports": {
		"html": false,
		"reusable": false,
		"inserter": true,
		"align": false,
		"interactivity": true
	},
	"attributes": {
		"showCount": {
			"type": "boolean",
			"default": true
		}
	},
	"render": "file:./render.php",
	"style": "file:./style.css",
	"viewScriptModule": "file:./view.js"
}
```

- [ ] **Step 2: Create the view module**

Create `wp-content/themes/ik2/blocks/articles-filters/view.js` with:

```js
/**
 * IK2 — Articles filters (Interactivity API view module).
 *
 * Registers the `ik2/articles-filters` store namespace and imports
 * the core router so format pills can call `core/router::actions.navigate`
 * via the `data-wp-on--click="actions.navigate"` directive emitted by
 * the server-rendered pill markup.
 */
import { store } from '@wordpress/interactivity';
import '@wordpress/interactivity-router';

store( 'ik2/articles-filters', {} );
```

- [ ] **Step 3: Replace `render.php`**

Replace the contents of `wp-content/themes/ik2/blocks/articles-filters/render.php` with:

```php
<?php
/**
 * Server render for ik2/articles-filters.
 *
 * Detects the current archive context (page-articles, category, tag,
 * or other) from the queried object, then renders two pill rows:
 *
 *   - Topic pills: plain `<a href>` links that switch archive context.
 *   - Format pills: same links plus the IAPI router action so the
 *     grid swaps in place without a full reload.
 *
 * Pretty URLs only — no query strings. URLs are generated to preserve
 * the other active dimension where possible (e.g. clicking a topic
 * pill keeps the current format segment).
 *
 * @package IK2
 * @var array<string,mixed> $attributes
 * @var string              $content
 * @var WP_Block            $block
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$ik2_topics = array(
	'all'         => __( 'all', 'ik2' ),
	'wordpress'   => __( 'wordpress', 'ik2' ),
	'ai'          => __( 'ai', 'ik2' ),
	'performance' => __( 'performance', 'ik2' ),
	'security'    => __( 'security', 'ik2' ),
	'web-apis'    => __( 'web-apis', 'ik2' ),
	'tooling'     => __( 'tooling', 'ik2' ),
);

$ik2_formats = array(
	'all'        => __( 'All', 'ik2' ),
	'guide'      => __( 'Guides', 'ik2' ),
	'note'       => __( 'Notes', 'ik2' ),
	'experiment' => __( 'Experiments', 'ik2' ),
);

/**
 * Detect the current archive context.
 *
 * @return array{kind:string,topic:?string,tag:?string,format:string}
 */
$ik2_detect_context = static function (): array {
	$ctx = array(
		'kind'   => 'page',
		'topic'  => null,
		'tag'    => null,
		'format' => '',
	);

	$queried = get_queried_object();

	if ( $queried instanceof WP_Term && 'category' === $queried->taxonomy ) {
		$ctx['kind']  = 'category';
		$ctx['topic'] = $queried->slug;
	} elseif ( $queried instanceof WP_Term && 'post_tag' === $queried->taxonomy ) {
		$ctx['kind'] = 'tag';
		$ctx['tag']  = $queried->slug;
	}

	$format = (string) get_query_var( 'format', '' );
	if ( '' !== $format && array_key_exists( $format, array( 'guide' => 1, 'note' => 1, 'experiment' => 1 ) ) ) {
		$ctx['format'] = $format;
	}

	return $ctx;
};

$ik2_context = $ik2_detect_context();

/**
 * Build the pretty URL for a given (topic, format) pair from the current context.
 *
 * Rules:
 *  - topic === 'all'  → main archive (or stay on tag if context is tag and tag pill clicked).
 *  - topic === slug   → /category/{slug}/ — overrides tag context.
 *  - format suffix appended when not 'all'.
 *  - From a tag context, the "all" topic preserves the tag (no topic on tag = "all"-equivalent).
 *
 * @param array{kind:string,topic:?string,tag:?string,format:string} $context
 * @param string                                                     $topic   Topic slug or 'all'.
 * @param string                                                     $format  Format slug or 'all'.
 */
$ik2_build_url = static function ( array $context, string $topic, string $format ): string {
	if ( 'all' !== $topic ) {
		$base = home_url( '/category/' . rawurlencode( $topic ) . '/' );
	} elseif ( 'tag' === $context['kind'] && null !== $context['tag'] ) {
		$base = home_url( '/tag/' . rawurlencode( $context['tag'] ) . '/' );
	} else {
		$base = home_url( '/articles/' );
	}

	if ( 'all' !== $format ) {
		$base .= 'format/' . rawurlencode( $format ) . '/';
	}

	return $base;
};

/**
 * Current active topic from context. Categories own their slug; tags and
 * the main archive both show "all" topic.
 */
$ik2_active_topic = 'category' === $ik2_context['kind'] && null !== $ik2_context['topic']
	? $ik2_context['topic']
	: 'all';

$ik2_active_format = '' !== $ik2_context['format'] ? $ik2_context['format'] : 'all';

$ik2_show_count = ! empty( $attributes['showCount'] );

$ik2_total = 0;
$ik2_shown = 0;

if ( $ik2_show_count ) {
	$ik2_total = (int) wp_count_posts( 'post' )->publish;

	$ik2_count_args = array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'no_found_rows'  => false,
		'fields'         => 'ids',
	);

	$ik2_tax_query = array();

	if ( 'category' === $ik2_context['kind'] && null !== $ik2_context['topic'] ) {
		$ik2_tax_query[] = array(
			'taxonomy' => 'category',
			'field'    => 'slug',
			'terms'    => array( $ik2_context['topic'] ),
		);
	}

	if ( 'tag' === $ik2_context['kind'] && null !== $ik2_context['tag'] ) {
		$ik2_tax_query[] = array(
			'taxonomy' => 'post_tag',
			'field'    => 'slug',
			'terms'    => array( $ik2_context['tag'] ),
		);
	}

	if ( 'all' !== $ik2_active_format ) {
		$ik2_tax_query[] = array(
			'taxonomy' => 'category',
			'field'    => 'slug',
			'terms'    => array( $ik2_active_format ),
		);
	}

	if ( count( $ik2_tax_query ) > 1 ) {
		$ik2_tax_query['relation'] = 'AND';
	}

	if ( $ik2_tax_query ) {
		$ik2_count_args['tax_query'] = $ik2_tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
	}

	$ik2_count_query = new WP_Query( $ik2_count_args );
	$ik2_shown       = (int) $ik2_count_query->found_posts;
	wp_reset_postdata();
}

$ik2_wrapper_attrs = get_block_wrapper_attributes(
	array( 'class' => 'ik-articles-filters' )
);
?>
<div <?php echo $ik2_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<span class="ik-articles-filters__label"><?php esc_html_e( 'filter:', 'ik2' ); ?></span>

	<div class="ik-articles-filters__group" role="group" aria-label="<?php esc_attr_e( 'Filter by topic', 'ik2' ); ?>">
		<?php foreach ( $ik2_topics as $slug => $label ) : ?>
			<?php
			$is_current = ( $slug === $ik2_active_topic );
			$href       = $ik2_build_url( $ik2_context, $slug, $ik2_active_format );
			?>
			<a
				class="ik-articles-filters__pill<?php echo $is_current ? ' is-active' : ''; ?>"
				href="<?php echo esc_url( $href ); ?>"
				<?php echo $is_current ? 'aria-current="true"' : ''; ?>
			><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</div>

	<span class="ik-articles-filters__divider" aria-hidden="true"></span>

	<div class="ik-articles-filters__group" role="group" aria-label="<?php esc_attr_e( 'Filter by format', 'ik2' ); ?>">
		<?php foreach ( $ik2_formats as $slug => $label ) : ?>
			<?php
			$is_current = ( $slug === $ik2_active_format );
			$href       = $ik2_build_url( $ik2_context, $ik2_active_topic, $slug );
			?>
			<a
				class="ik-articles-filters__pill<?php echo $is_current ? ' is-active' : ''; ?>"
				href="<?php echo esc_url( $href ); ?>"
				data-wp-on--click="actions.navigate"
				<?php echo $is_current ? 'aria-current="true"' : ''; ?>
			><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</div>

	<?php if ( $ik2_show_count ) : ?>
		<span class="ik-articles-filters__count">
			<?php
			printf(
				/* translators: 1: number of posts matching the current filters, 2: total posts */
				esc_html__( '%1$d of %2$d', 'ik2' ),
				(int) $ik2_shown,
				(int) $ik2_total
			);
			?>
		</span>
	<?php endif; ?>
</div>
```

- [ ] **Step 4: Build the view module**

Run: `docker compose --profile tools run --rm pnpm build`
Expected: completes; `wp-content/themes/ik2/blocks/articles-filters/view.js` is bundled into `wp-content/themes/ik2/blocks/articles-filters/view.js` output (wp-scripts writes the built module back next to `block.json` or under a `view-*.js` output — verify the file exists after build).

- [ ] **Step 5: Restart app container**

Run: `docker compose restart app`
Expected: restarts cleanly.

- [ ] **Step 6: Run PHP and JS quality gates**

Run in parallel:
```bash
docker compose --profile tools run --rm composer quality
docker compose --profile tools run --rm pnpm lint
```
Expected: both pass.

- [ ] **Step 7: Commit**

```bash
git add wp-content/themes/ik2/blocks/articles-filters/block.json \
        wp-content/themes/ik2/blocks/articles-filters/render.php \
        wp-content/themes/ik2/blocks/articles-filters/view.js
git commit -m "feat(blocks): rewrite articles-filters for pretty URLs + IAPI

Detects current archive context (page/category/tag) and builds pretty
filter URLs accordingly. Topic pills are plain links; format pills get
data-wp-on--click=\"actions.navigate\" so the IAPI router swaps the
grid in place. Adds a view.js module that registers the store
namespace and imports the core router."
```

---

## Task 7: Wrap page-articles grid in the same router region

**Files:**
- Modify: `wp-content/themes/ik2/patterns/articles-archive-grid.php`

So the format pills on `/articles/` get the same in-place swap behaviour as on category/tag archives.

- [ ] **Step 1: Edit the file**

Replace the entire contents of `wp-content/themes/ik2/patterns/articles-archive-grid.php` with:

```php
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

	<!-- wp:ik2/articles-filters {} /-->

	<!-- wp:query {"queryId":42,"query":{"perPage":9,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","inherit":false},"className":"ik-articles-archive__query"} -->
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
```

- [ ] **Step 2: Run PHP quality gate**

Run: `docker compose --profile tools run --rm composer quality`
Expected: PHPCS + PHPStan pass.

- [ ] **Step 3: Commit**

```bash
git add wp-content/themes/ik2/patterns/articles-archive-grid.php
git commit -m "refactor(theme): wrap page-articles grid in IAPI router region

Mirrors the archive-grid pattern so the format pills on /articles/
swap the grid in place — same UX as on category and tag archives."
```

---

## Task 8: Add `archive.html` template

**Files:**
- Create: `wp-content/themes/ik2/templates/archive.html`

- [ ] **Step 1: Create the template**

Create `wp-content/themes/ik2/templates/archive.html` with:

```html
<!-- wp:template-part {"slug":"header","tagName":"header"} /-->

<!-- wp:group {"tagName":"main","className":"ik-articles-archive","layout":{"type":"default"}} -->
<main class="wp-block-group ik-articles-archive">
	<div class="container-full">
		<!-- wp:pattern {"slug":"ik2/archive-header"} /-->
		<!-- wp:pattern {"slug":"ik2/archive-grid"} /-->
	</div>
</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
```

- [ ] **Step 2: Restart app container**

Run: `docker compose restart app`
Expected: restarts cleanly.

- [ ] **Step 3: Smoke test**

Open `http://localhost:8080/category/wordpress/` (or any existing category slug — confirm by checking `composer dev:wp:cmd -- term list category --fields=slug,count`).
Expected: page renders using the new template — eyebrow, term name as `<h1>`, filter pills, grid of category posts.

- [ ] **Step 4: Commit**

```bash
git add wp-content/themes/ik2/templates/archive.html
git commit -m "feat(theme): add archive template for category and tag pages

Single archive.html template covering both taxonomies via the standard
WordPress template hierarchy. Reuses the same shell as page-articles
(header pattern + grid pattern inside ik-articles-archive)."
```

---

## Task 9: Browser verification — desktop and iPhone 12 Pro

Use the **agent-browser** skill to drive `http://localhost:8080/` for the nine manual checks in the spec. Run desktop first, then re-run the visual checks on iPhone 12 Pro emulation.

This task has no code changes — it's an explicit verification gate before the final quality run.

- [ ] **Step 1: Pick a real category slug and tag slug for the test URLs**

Run:
```bash
composer dev:wp:cmd -- term list category --fields=slug,count
composer dev:wp:cmd -- term list post_tag --fields=slug,count
```
Expected: at least one category and one tag with `count > 0`. Note them as `$CAT` and `$TAG`. (If the dev DB has no tags, skip the tag-specific checks and note that — Task 9 still passes.)

- [ ] **Step 2: Invoke the agent-browser skill in desktop viewport**

Run the following checks via the agent-browser skill (one navigation per check):

1. `http://localhost:8080/articles/` — confirm header eyebrow shows total post count, all topic pills are inactive except `all`, format `All` is active.
2. `http://localhost:8080/articles/format/guide/` — confirm only guide-category cards visible, `Guides` format pill is active, URL has no `?`.
3. `http://localhost:8080/category/{$CAT}/` — confirm header shows category name as `<h1>`, eyebrow shows `// CATEGORY · N POSTS · {NAME}`, description appears as lede if the term has one. Matching topic pill (`{$CAT}`) is active.
4. `http://localhost:8080/category/{$CAT}/format/guide/` — both filters active, grid narrowed.
5. From check 4, click the `Notes` format pill. Confirm URL becomes `/category/{$CAT}/format/note/`, no full page reload (Network tab: no document load), grid contents update, active format pill becomes `Notes`.
6. From check 5, click the `ai` topic pill. Confirm a full page reload occurs (document request in Network), URL becomes `/category/ai/format/note/`, header changes to "AI".
7. `http://localhost:8080/tag/{$TAG}/` (if a tag exists) — confirm tag archive renders using the new template, description shown if present, all topic pills inactive (none is the active topic on a tag page).
8. `http://localhost:8080/tag/{$TAG}/format/note/` — format pill ANDs on top of tag.
9. `http://localhost:8080/category/{$CAT}/format/guide/page/2/` (only if `$CAT` has > 9 guide-category posts; otherwise skip) — pagination works with combined filter.

- [ ] **Step 3: Re-run the visual checks on iPhone 12 Pro emulation**

Repeat checks 1, 3, 4, 5, and 7 (the layout/visual ones — the URL behaviour is identical across viewports) in iPhone 12 Pro emulation via agent-browser. Confirm the filter bar wraps cleanly at narrow widths (CSS already handles this — `_articles.scss` / `blocks/articles-filters/style.css` have the `max-width: 720px` media query).

- [ ] **Step 4: Capture failures (if any) as new tasks**

If any check fails, do **not** patch silently. Stop and add a follow-up task to this plan documenting:
- Which check failed
- Observed vs expected
- A hypothesis for the fix
Then continue from there.

- [ ] **Step 5: Commit the verification record**

No code change — skip git here. The next task's commit will include any small fixes that came out of verification.

---

## Task 10: Final quality gates

**Files:** none — verification only.

- [ ] **Step 1: PHP quality gate**

Run: `docker compose --profile tools run --rm composer quality`
Expected: PHPCS + PHPStan exit 0.

- [ ] **Step 2: JS/CSS quality gate**

Run: `docker compose --profile tools run --rm pnpm lint`
Expected: ESLint + Stylelint exit 0.

- [ ] **Step 3: Production build**

Run: `docker compose --profile tools run --rm pnpm build`
Expected: builds cleanly; no warnings about the new `view.js`.

- [ ] **Step 4: Final manual smoke (sanity)**

Open `http://localhost:8080/articles/` and one category archive in a fresh browser tab.
Expected: pages render, no PHP notices in `composer dev:logs | tail -50`.

- [ ] **Step 5: Confirm no stray modifications**

Run: `git status`
Expected: working tree shows only the files this plan touched (templates, patterns, blocks, inc/, webpack.config.js, docs/superpowers/{specs,plans}).

This plan does not introduce a final "wrap-up" commit — every task has committed its own work.

---

## Open follow-ups (out of scope for this plan)

- Pagination links inside the router region currently full-reload. Could be enhanced with explicit `data-wp-on--click` on pagination anchors via a `render_block` filter.
- The post-count chip in the filter bar is server-rendered per request. Within the IAPI swap it will refresh because the filter block is inside the router region; if we later move the chip outside, we'd need a client-side store update.
- The design system has not yet codified an empty-state component for archives. The "no posts match" copy is inline — fine for now.
