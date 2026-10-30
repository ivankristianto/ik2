# Archive Template for Category and Tag Pages — Design

**Date:** 2026-05-23
**Status:** Draft (awaiting review)

## Summary

Add a single `archive.html` block template that powers both category and tag archives, reusing the same layout, filter bar, and header DOM as the existing `page-articles.html`. The header pulls its eyebrow, title, and lede from the queried term. The filter bar moves off query-string navigation onto pretty URLs driven by the Interactivity API router, so format changes refresh the grid without a full page load.

## Goals

- Category (`/category/{slug}/`) and tag (`/tag/{slug}/`) archives share the look-and-feel of the main Articles page.
- The filter pills work in every context (main archive, category, tag) and produce pretty URLs — no `?topic=` / `?format=` query strings.
- Format filtering feels interactive: clicking a format pill swaps the grid in place via the Interactivity API router, no full reload.
- Topic pills are simple navigation (full page load is honest — the archive context itself changes).
- The category/tag header reads its content from the queried term description.

## Non-goals

- Combined tag-and-category intersection URLs (e.g. `/tag/cache/topic/wordpress/`). Topic pills on a tag page navigate to `/category/{slug}/` and drop the tag.
- A separate archive template per taxonomy. One `archive.html` covers both.
- Migrating existing inbound links from `?topic=` / `?format=`. We replace the current behaviour outright — these URLs were only ever produced by the filter block itself.

## URL design

All filter state lives in the path. No query strings.

| Context              | URL                                        |
| :------------------- | :----------------------------------------- |
| Main archive         | `/articles/`                               |
| Main + format        | `/articles/format/guide/`                  |
| Category             | `/category/wordpress/`                     |
| Category + format    | `/category/wordpress/format/guide/`        |
| Tag                  | `/tag/cache/`                              |
| Tag + format         | `/tag/cache/format/note/`                  |

"Topic" is encoded as the archive itself — there is no separate `/articles/topic/{slug}/` URL. Clicking a topic pill from `/articles/` navigates to `/category/{slug}/`. The `format` segment is preserved when changing topic: `/articles/format/guide/` → `/category/wordpress/format/guide/`. Clicking the `all` topic returns to `/articles/format/{current-format}/` (or `/articles/` if no format).

On a tag page, topic pills behave exactly like on the main archive — clicking one navigates to `/category/{slug}/`, dropping the tag. The "all" topic pill stays on the tag (the tag has no topic intersection to drop).

## Interaction model

| Pill type | Mechanism           | Reason                                                                            |
| :-------- | :------------------ | :-------------------------------------------------------------------------------- |
| Topic     | Plain `<a href>`    | Always changes archive (and header). A real navigation is honest and simpler.     |
| Format    | IAPI router action  | Within-archive narrowing. Grid and active pill state swap in place, no reload.    |

Format navigation uses `actions.navigate` from the `@wordpress/interactivity-router` module. The filter block + the Query Loop are wrapped together in one `data-wp-router-region` so the router swaps both — active pill class updates with the new grid.

Pagination is intentionally **not** marked as `enhancedPagination` on the Query Loop, to avoid two nested router regions. Pagination anchors inside the same wrapper region pick up router behaviour automatically because the router intercepts in-region link clicks marked with `data-wp-on--click`; pagination links remain plain `<a>` and trigger a full reload — acceptable, and we can revisit later.

## Architecture

### New files

```
wp-content/themes/ik2/
├── templates/
│   └── archive.html                    # category + tag archive layout
├── patterns/
│   ├── archive-header.php              # dynamic header from queried term
│   └── archive-grid.php                # filter + Query Loop (inherit:true)
```

### Modified files

```
wp-content/themes/ik2/
├── blocks/articles-filters/
│   ├── render.php                      # context-aware pill URLs, IAPI directives
│   ├── block.json                      # add viewScriptModule for router
│   └── view.js                         # NEW — registers the IAPI store namespace
├── inc/
│   └── Blocks.php                      # drop topic handling, add rewrite rules + flush
```

### Template shell

`archive.html` mirrors `page-articles.html`:

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

WordPress's template hierarchy picks this up for `category.html` and `tag.html` requests automatically. No `category.html` / `tag.html` are added — both fall through to `archive.html`.

### Header pattern (`archive-header.php`)

Reads `get_queried_object()` (a `WP_Term`), then renders the same DOM and classes used by `articles-archive-header.php` so the existing CSS applies unchanged:

- **Eyebrow:** `// {TAXONOMY-LABEL} · {COUNT} POSTS · {TERM-NAME-UPPER}` (e.g. `// CATEGORY · 12 POSTS · WORDPRESS`). Uses `get_taxonomy( $term->taxonomy )->labels->singular_name`.
- **`<h1>`:** the term name (`single_term_title()` style, but uses the queried object directly).
- **Lede paragraph:** `term_description()` — rendered as-is if non-empty, omitted if empty (per Q3 answer the description is the source; we don't fall back).

Falls back gracefully if `get_queried_object()` somehow isn't a term (e.g. preview in editor) — renders nothing rather than fatalling.

### Grid pattern (`archive-grid.php`)

Same shape as `articles-archive-grid.php`. The Query Loop uses `inherit:true` so the main archive query (category/tag) is preserved automatically:

```html
<!-- wp:ik2/articles-filters {} /-->

<!-- wp:query {"queryId":43,"query":{"inherit":true,"perPage":9},"className":"ik-articles-archive__query"} -->
<div class="wp-block-query ik-articles-archive__query">
    <!-- wp:post-template {"className":"ik-articles-grid"} -->
        <!-- wp:pattern {"slug":"ik2/article-card"} /-->
    <!-- /wp:post-template -->

    <!-- wp:query-pagination ... -->...<!-- /wp:query-pagination -->
    <!-- wp:query-no-results ... -->...<!-- /wp:query-no-results -->
</div>
<!-- /wp:query -->
```

`queryId: 43` distinguishes it from page-articles' `queryId: 42`. Both are recognised in the `query_loop_block_query_vars` filter and both get the `format` filter applied.

The `data-wp-router-region` wrapper goes around filter + query. Two options for placing it:

1. Wrap the pattern markup in a plain `<div data-wp-interactive="ik2/articles-filters" data-wp-router-region="ik-articles">` (and matching close) directly in the pattern PHP.
2. Use a Group block with `metadata.name` and emit the directive via a render filter.

We use option 1 — simpler and keeps the directive next to the markup it controls. Same wrapping applies inside `articles-archive-grid.php` for the page-articles archive.

### Filter block (`blocks/articles-filters/render.php`)

Rewrite the URL-building logic:

```php
$context = ik2_detect_archive_context();
// returns ['kind' => 'page'|'category'|'tag', 'topic' => ?slug, 'tag' => ?slug, 'format' => ?slug]
```

For each topic pill, the destination URL is computed from the context:

- `topic = all` from any context → `/articles/[format/{f}/]`
- `topic = X` from any context → `/category/X/[format/{f}/]`

For each format pill, the destination URL is computed from the context:

- On `/articles/`: `/articles/format/{f}/` (or `/articles/` for `all`)
- On `/category/{c}/`: `/category/{c}/format/{f}/` (or `/category/{c}/` for `all`)
- On `/tag/{t}/`: `/tag/{t}/format/{f}/` (or `/tag/{t}/` for `all`)

The "active" state is derived from `$context`, not from `$_GET`. Format pills get IAPI directives, topic pills don't:

```php
<a
    class="ik-articles-filters__pill ..."
    href="<?php echo esc_url( $href ); ?>"
    data-wp-on--click="actions.navigate"   <!-- only on format pills -->
>
```

The wrapper `<div>` already exists; add `data-wp-interactive="ik2/articles-filters"` to it so the router action resolves. (The router action namespace is `core/router`; we reference it via `core/router::actions.navigate`.)

The match-count pill in the bottom-right uses the queried object's `count` minus filter narrowing where applicable — same logic as today but driven by `$context`, not query string.

### Block module (`blocks/articles-filters/view.js`)

Tiny module — registers the `ik2/articles-filters` interactivity namespace and imports the core router so `core/router::actions.navigate` is available in the page:

```js
import { store } from '@wordpress/interactivity';
import '@wordpress/interactivity-router';

store( 'ik2/articles-filters', {} );
```

Loaded via `viewScriptModule` in `block.json`. No custom state or actions — we delegate entirely to the core router action.

The current `webpack.config.js` hard-codes `entry: { index, editor }` only, which suppresses the default block-discovery entry points from `@wordpress/scripts`. We extend it to also bundle the filter view module:

```js
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
    ...defaultConfig,
    entry: {
        ...( typeof defaultConfig.entry === 'function' ? defaultConfig.entry() : defaultConfig.entry ),
        index: path.resolve( __dirname, 'wp-content/themes/ik2/src/index.js' ),
        editor: path.resolve( __dirname, 'wp-content/themes/ik2/src/editor.js' ),
    },
    output: { ...defaultConfig.output, path: path.resolve( __dirname, 'wp-content/themes/ik2/build' ) },
};
```

With `defaultConfig.entry()` invoked, wp-scripts auto-detects every `block.json` whose `viewScriptModule` is `file:./view.js` and emits a bundled module — covering both the existing `src/interactivity/cmd-palette/` and our new `blocks/articles-filters/view.js`. The `viewScriptModule` path in `block.json` resolves relative to the block, so the source `view.js` and the generated module land in the same folder as the block (or, for `src/interactivity/*`, get copied to `build/interactivity/*` by wp-scripts).

Run `docker compose --profile tools run --rm pnpm build` (or the watcher via `pnpm start`) to produce the bundle.

### Routing (`inc/Blocks.php`)

Add three rewrite rules, registered on `init` at priority 10, then a one-shot flush on theme switch:

```php
add_action( 'init', function () {
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
} );

add_filter( 'query_vars', function ( array $vars ): array {
    $vars[] = 'format';
    return $vars;
} );

add_action( 'after_switch_theme', static function (): void {
    flush_rewrite_rules();
} );
```

Site categories are flat (`wordpress`, `ai`, `performance`, …), so `([^/]+)` is sufficient. The `top` priority ensures these rules win against the default `/category/{slug}/page/{n}/` and `/category/{slug}/feed/` rules.

Update the existing `query_loop_block_query_vars` filter to:

- **Drop** the `topic` handling (no longer used — topic lives in the URL path itself).
- **Accept** `queryId === 42` (page-articles) or `queryId === 43` (archive).
- **Apply** the `format` query var as a category tax_query (formats are categories with slugs `guide`, `note`, `experiment`).
- **Preserve** any existing `tax_query` the Query Loop already has from inheritance (so we AND the format on top of the queried category/tag).

Constants: rename `ARTICLES_QUERY_ID` to keep both IDs visible:

```php
const ARTICLES_QUERY_ID = 42;
const ARCHIVE_QUERY_ID  = 43;
```

### Styles

No new SCSS. `_articles.scss` already covers the markup classes (`.ik-articles-archive`, `.ik-articles-archive__head`, `.ik-articles-archive__title`, `.ik-articles-archive__lede`, `.ik-articles-archive__query`, `.ik-articles-grid`, `.ik-articles-empty`). The header pattern uses the same DOM/classes so it inherits styling for free.

The filter block's CSS (`blocks/articles-filters/style.css`) is unchanged.

## Edge cases

- **Empty term description.** Header omits the lede paragraph. The header still has eyebrow + title so vertical rhythm with the main archive is "title-on-its-own" — acceptable.
- **No posts in term + format combo.** The existing `<!-- wp:query-no-results -->` block renders "No posts match these filters yet — try widening."
- **`/articles/format/{slug}/` with an unknown format slug.** Rewrite rule still matches → `format=$slug` becomes a public query var, but the format isn't in `$ik2_formats`. We sanitise/whitelist in `render.php` (active state stays on "all") and in the `query_loop_block_query_vars` filter (no tax_query added if not whitelisted).
- **Pagination + format filter together.** Inherited query handles pagination, format filter ANDs on top. URL form: `/category/wordpress/format/guide/page/2/` — works because the rewrite endpoint applies before WP's default pagination rule.

  Verify with manual test in the implementation plan.
- **Editor preview.** `get_queried_object()` is null in the editor — patterns must guard with `instanceof WP_Term`.
- **No JS / IAPI fails to load.** Format pill links are real `<a href>` — they degrade to full page navigation. No broken UX.

## Testing & verification

Use the `agent-browser` skill against `http://localhost:8080/` (the running dev stack) in both desktop and iPhone 12 Pro viewports.

Manual checks:

1. `/articles/` renders unchanged from current main.
2. `/articles/format/guide/` renders only guides; "Guides" pill active; URL has no query string.
3. `/category/wordpress/` renders the archive template, header shows category name + description, "wordpress" topic pill active.
4. `/category/wordpress/format/guide/` — both filters active, grid narrowed.
5. Click "Notes" from `/category/wordpress/format/guide/` → URL becomes `/category/wordpress/format/note/`, grid swaps **without full reload** (IAPI router), active pill updates.
6. Click "ai" topic pill from `/category/wordpress/format/guide/` → full reload, `/category/ai/format/guide/`, header changes to "AI".
7. `/tag/{slug}/` renders archive template, term description shown if present.
8. `/tag/{slug}/format/note/` — format filter ANDs on top of tag.
9. Pagination on combined filter works: `/category/wordpress/format/guide/page/2/`.

Manual browser verification uses the `agent-browser` skill against `http://localhost:8080/` — both desktop (default viewport) and iPhone 12 Pro emulation — for each of the nine checks above.

Quality gates (must pass before merge):

```bash
docker compose --profile tools run --rm composer quality   # PHPCS + PHPStan
docker compose --profile tools run --rm pnpm lint          # ESLint + Stylelint
docker compose --profile tools run --rm pnpm build         # bundles view.js for IAPI
```

## Open questions

None — the four upstream questions are resolved in the brainstorming transcript and reflected above.

## Migration / rollout

- After deploying, flush rewrite rules once. The `after_switch_theme` hook handles activation; for already-active theme, manual flush via Settings → Permalinks or `wp rewrite flush`.
- No data migration. Existing posts and terms are untouched.
- No redirects from old query-string URLs — the old filter block was the only thing that produced them, and it's being replaced atomically.
