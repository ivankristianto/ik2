# SEO, AEO and GEO improvements

## Goal

Fix what the 2026-10-30 audit of the live site found, in the order that
moves search, answer-engine and AI-citation visibility the most for the
least work. The audit report, with evidence for every item and a checklist
to tick as fixes ship, is at
https://claude.ai/artifact/LZFRnrr3XbkG28YMfYFMQE. Item IDs below
(`TECH-06`, `ONPAGE-X1`, ...) match that report.

Two findings from the research shape the scope:

- Google's AI optimization guide (2026) says AI Overviews and AI Mode use
  the same index and signals as Search. llms.txt, AI-specific markup and
  Markdown copies "neither harm nor help". So most of this spec is plain
  technical SEO and entity hygiene, and that work also serves AEO and GEO.
- Bing feeds ChatGPT search and Copilot, and it relies on accurate
  `lastmod` and IndexNow. That makes the migration's fake modified dates
  more costly than they look.

## Summary of findings

| Area | Fail | Partial | Manual | Pass |
| --- | --- | --- | --- | --- |
| Technical | 6 | 4 | 1 | 4 |
| On-page and entity | 8 | 5 | 0 | 1 |
| Answer engines | 1 | 6 | 2 | 3 |
| Generative engines | 2 | 2 | 6 | 3 |

Root causes, most items trace back to one of these:

1. **Stale staging data in Yoast.** `/articles/`, `/articles/page/N/` and
   `/projects/` declare `https://next.ivankristianto.com/...` as canonical,
   `og:url`, `rel=next/prev` and breadcrumb `@id`. That host returns 404.
2. **The site's name is "ivan".** The WP Site Title, the user display name
   and the Yoast website name disagree, so `<title>` suffixes, `og:site_name`,
   the Person schema, RSS `dc:creator` and `meta author` all say "ivan".
   "Kristianto" appears in no visible text on home, /about/ or a post.
3. **Migration side effects.** 482 of 492 posts carry `dateModified`
   2026-06-27 (import day). Old `/YYYY/MM/slug/` permalinks and `/about-me/`
   404. 16 body images point at `ivankristianto.local` or the dead
   `cdn.ivankristianto.com`. 1,129 tags exist for 492 posts, 925 of them
   with one post, all `index,follow` and in the sitemap.
4. **Template gaps.** Every page has two `<title>` elements, posts have no
   byline, and the canonical logic for paginated Articles is hooked where
   Yoast never reads it.

## Phase 1: settings and edge (no code)

Each item is a dashboard change. Do these first: they unblock the code
work and several are one-minute fixes.

### 1.1 One name everywhere (`ONPAGE-X1`, `GEO-06`, part of `ONPAGE-09/10`)

- Settings > General > Site Title: `Ivan Kristianto`.
- Users > Profile > Display name publicly as: `Ivan Kristianto`.
- Users > Profile > Yoast contact fields: add
  `https://github.com/ivankristianto` and
  `https://profiles.wordpress.org/ivankristianto/`; remove the site's own
  URL from the list (it lands in `sameAs`).
- Yoast > Settings > Site basics > Website name: `Ivan Kristianto`. Keep
  the tagline as the alternate name if wanted.

Check: `curl -s https://www.ivankristianto.com/about/ | rg -o '<title>[^<]+|og:site_name" content="[^"]+'`
ends in `Ivan Kristianto` and shows no `ivan` on its own.

### 1.2 Clear the staging canonicals (`TECH-06`, `TECH-04`)

The value is stored in Yoast indexables (`yoast_head_json.canonical` on
pages 13 and 14 shows it), not computed per request. Reset and rebuild:
`wp yoast index --reindex` on production, or Yoast > Tools > Optimize SEO
data after a reset. If the Articles or Projects page has a value in the
Yoast meta box > Advanced > Canonical URL, clear it first.

Check: `rg -c next.ivankristianto.com` on `/articles/`, `/articles/page/2/`
and `/projects/` HTML returns 0. Every `next.` reference found in the audit
came from Yoast output (canonical, `og:url`, rel next/prev, breadcrumb
`@id`); nothing in the repo or in page content hardcodes it.

### 1.3 Apex domain and HSTS (`TECH-02`)

`https://ivankristianto.com/` returns a 404 from the proxy, and
`http://ivankristianto.com/` redirects to that 404.

- Cloudflare > Rules > Redirect Rules: hostname equals
  `ivankristianto.com` > 301 to
  `concat("https://www.ivankristianto.com", http.request.uri.path)`,
  preserve query string. Make it match `http` too so it is one hop.
- SSL/TLS > Edge Certificates > HSTS: `max-age` 6 months, include
  subdomains only after checking `next.` and any other subdomain serve
  HTTPS, no preload yet.

Check: `curl -sI http://ivankristianto.com/regular-expressions-cheat-sheet/`
gives one 301 straight to the www https URL.

### 1.4 Tag archives out of the index (`ONPAGE-01`)

Yoast > Settings > Categories & tags > Tags > Show tags in search results:
off. This drops both tag sitemaps and sets `noindex,follow`. Pruning the
925 one-post tags is content work (3.6); noindexing first stops the damage
now. Category archives stay indexed.

Author archive: leave Yoast > Settings > Advanced > Author archives
**enabled**. Disabling them makes Yoast redirect `/author/ivan/` to the home
page on the `wp` action, before the theme's `template_redirect` 301 to
`/about/` in 2.4 can run. Turn off "Show author archives in search results"
so it also leaves the sitemap until 2.4 ships.

### 1.5 One Google tag (`TECH-X2`, `TECH-08`)

Every page loads `gtag.js?id=GT-TB7G3CH` twice plus `GTM-5X6FPZ`, and the
GTM container still fires Universal Analytics (`UA-5721705-1`,
`analytics.js`, `dc.js`). That is about 500KB of JS and 237ms of main
thread on the home page.

- In GTM, delete the UA tag and its triggers, publish the container.
- In Site Kit > Settings, keep one of Analytics or Tag Manager connected,
  not both.

Check: Lighthouse network list on home shows one `googletagmanager.com`
script and no `analytics.js`, `dc.js` or `__utm.gif`. Re-measure LCP
afterwards, before doing any CSS work for `TECH-08`.

### 1.6 Feeds and crawler hints (`GEO-X2`, `TECH-12`)

- Settings > Reading > For each post in a feed, include: Full text.
- Cloudflare > Caching > Configuration > Crawler Hints: on. It sends
  IndexNow pings, but only from cache activity, so its value depends on
  the HTML edge cache in Phase 4. If it stays quiet after that, add the IndexNow plugin via composer.

### 1.7 Manual checks to record

These need a signed-in dashboard. Record each result and the date in a
short note at the end of this spec, then tick the item in the report.

- Search Console: Core Web Vitals field data (lab LCP was 4.8 to 5.6s on
  mobile, but field data decides), Settings > Search generative AI set to
  include, Generative AI report (`AEO-08`, `TECH-08`).
- Bing Webmaster Tools: verified, sitemap submitted, indexed count against
  492 posts, AI Performance report, IndexNow submissions, backlinks
  (`GEO-05`, `GEO-09`).
- Cloudflare AI Crawl Control and Bot Fight Mode: ChatGPT-User,
  Claude-User, Perplexity-User and Google's user-triggered fetchers allowed
  with no blocks in the last 30 days (`GEO-03`). Spoofed user agents got
  200 from the audit machine, which is only indicative.
- Training crawlers (`GEO-02`): decide and write down the policy. Today
  everything is allowed by default. If blocking, add one robots group per
  training token (GPTBot, ClaudeBot, CCBot, Applebot-Extended,
  Google-Extended) and leave OAI-SearchBot, Claude-SearchBot, PerplexityBot
  and the user fetchers alone. Blocking Google-Extended also removes Gemini
  app grounding.
- GA4: a saved segment for AI referrers (`chatgpt.com`, `perplexity.ai`,
  `gemini.google.com`, `copilot.microsoft.com`, `claude.ai`) (`GEO-11`).

## Phase 2: theme and plugin code

One PR per numbered item. All PHP hook callbacks are named functions in
the module's namespace, registered in its `bootstrap()`. Run
`composer quality` and, for CSS, `pnpm lint`.

### 2.1 Remove the duplicate title tag (`ONPAGE-03`)

Yoast removes `_block_template_render_title_tag` in its `register_hooks`,
but core's `locate_block_template()` adds it back at `template_include`,
which runs later. Remove it again on `wp_head` priority 0.

File: `wp-content/themes/ik2/inc/setup.php`.

```php
add_action( 'wp_head', __NAMESPACE__ . '\\remove_core_block_title_tag', 0 );

/**
 * Drop core's block-template title when Yoast prints its own <title>.
 */
function remove_core_block_title_tag(): void {
	if ( defined( 'WPSEO_VERSION' ) ) {
		remove_action( 'wp_head', '_block_template_render_title_tag', 1 );
	}
}
```

Check: `curl -s <url> | rg -c '<title>'` is 1 on home, a post, a page,
/articles/ and a tag.

### 2.2 Articles pagination canonical and rel next/prev (`TECH-06`)

`filter_articles_canonical_url()` in `wp-content/themes/ik2/inc/blocks.php`
is hooked to `get_canonical_url`, which Yoast's canonical presenter does
not call. So `/articles/page/2/` canonicalizes to page 1, and Yoast's
`rel=next` uses `?query-42-page=2` while the visible links use
`/articles/page/2/`.

- Hook the same logic to `wpseo_canonical` and `wpseo_opengraph_url`.
  Keep the `get_canonical_url` hook for when Yoast is off.
- Filter `wpseo_adjacent_rel_url` (Yoast passes `$url`, `$rel`) to return
  `ik2_build_archive_pagination_url( $page ± 1 )` on the Articles page.
- Add the page number to the paginated title (`wpseo_title`, append
  ` - Page N`).

Check: `/articles/page/2/` has `canonical` and `og:url` equal to itself,
`rel=prev` `/articles/` and `rel=next` `/articles/page/3/`, all on www.

### 2.3 Byline and updated date (`ONPAGE-12`, `GEO-06`, `AEO-X2`)

File: `wp-content/themes/ik2/patterns/single-article-header.php`.

- Add a byline to the meta line: `by <a href="/about/" rel="author">Ivan Kristianto</a>`.
  Use a static link, not `core/post-author-name`, so it does not depend on
  the display name or link to the author archive.
- Add `core/post-date` with `displayType: "modified"`, shown only when the
  modified date is a different day from the published date, labelled
  `updated`. Dates stay monospace ISO 8601 per the design rules. **Ship
  this only after 2.6 has restored real modified dates**, or 482 posts will
  show "updated 2026-06-27".
- Replace the `core/paragraph` separator (`<p class="ik-article__sep">·</p>`)
  with a CSS `::before` on the next item in the meta group, so the first
  `<p>` after the H1 is article text.

Styles go in the block or section CSS that already styles
`.ik-article__meta`, using `var(--wp--preset--*)` tokens. No new values.

### 2.4 Person and profile schema, author archive (`ONPAGE-09`, `ONPAGE-10`, `ONPAGE-01`)

New module `wp-content/themes/ik2/inc/schema.php` (namespace
`IK2\Theme\Schema`), or extend an existing SEO module if one exists by
then:

- `wpseo_schema_person`: set `url` to `https://www.ivankristianto.com/about/`.
- `wpseo_schema_webpage`: on the About page, set `@type` to
  `['WebPage', 'ProfilePage']` and `mainEntity` to the Person `@id`.
- `template_redirect`: `is_author()` > `wp_safe_redirect( home_url( '/about/' ), 301 )`.

Also fix the About page's Yoast title (currently "Web Engineer, WordPress,
Performance, AI Insights - ivan") and description in the meta box, and
put the full name in its H1 or first sentence.

Check: the Article `author` on a post resolves to a Person named
`Ivan Kristianto` with `url` /about/ and `sameAs` including GitHub, and
the Rich Results Test passes.

### 2.5 Legacy redirects (`TECH-11`)

New module `wp-content/plugins/ik2/inc/legacy-redirects.php` (namespace
`IK2\Plugin\LegacyRedirects`), booted from `IK2\Plugin\bootstrap()`.

On `template_redirect`, only when `is_404()`:

1. Path matches `^/\d{4}/\d{2}/([^/]+)/?$` > look up a published post with
   that `post_name` > 301 to its permalink. No match > let it 404. The
   audit found 55 archived old permalinks with a live slug and 21 without.
2. A small constant map for renamed pages: `/about-me/` > `/about/`.
   Add others as Search Console reports them.

Core's `redirect_guess_404_permalink()` misses these because the path
parses as `pagename`, and Yoast free has no redirect manager.

Old upload paths (`/wp-content/uploads/2010/12/<file>` now living under
`2026/06/`) are served by nginx without touching PHP, so the PHP handler
cannot catch them. Generate an nginx `map` from the importer's media map
and include it in `docker/nginx/default.conf`. Lower priority than the
post redirects; do it only if Search Console shows image 404s.

Check: `curl -sI https://www.ivankristianto.com/2009/04/howto-install-curl-in-php-apache/`
gives one 301 to `/howto-install-curl-in-php-apache/`; `/about-me/` 301s to
`/about/`; an unknown `/2009/04/nope/` still 404s.

### 2.6 Restore real modified dates (`TECH-05`)

Cause: `class-post-importer.php` calls `wp_update_post()` after the media
URL rewrite (around line 151) and on re-import (around line 230). Core
stamps `post_modified` with the current time on every update and ignores
the passed value.

- Importer fix: after the last `wp_update_post()`, write
  `post_modified` and `post_modified_gmt` from the legacy row with
  `$wpdb->update()`, then `clean_post_cache()`.
- One-off command, `wp ik2 migrate restore-modified-dates` (sibling of the
  existing migrate commands in `wp-content/plugins/ik2/inc/cli/migrate/`):
  for each post stamped `_ik2_normalized` or with a legacy ID, set the
  modified dates from the legacy DB. `--dry-run` prints the changes. Skip
  any post edited after 2026-06-28 (the 10 posts touched since, such as
  `cloudflare-api-cli-tool`), since their modified date is real.
- After running it on production, rebuild Yoast indexables so the sitemap
  `lastmod`, JSON-LD `dateModified` and `article:modified_time` follow.

The normalization pass (proofreading, block conversion, recategorizing)
changed content but not what the post says. Keeping the legacy modified
date is the honest choice. Google lists date bumps without real changes
as a warning sign.

Check: fewer than 5% of `post-sitemap.xml` `lastmod` values share one day.

### 2.7 Footnote label out of the extracted text (`AEO-X1`)

`wp-content/themes/ik2/inc/footnotes.php` (around line 285) prints
`<span class="ik-cite__label">Source </span>N`, visually hidden. Text
extractors that ignore CSS read "v0.14.0.Source 8,Source 9". Put
`aria-label="Source N"` on the link, drop the span, and remove the
`.ik-cite__label` rule from `_article-single.scss`. Screen readers still
announce "Source N".

### 2.8 Related posts (`ONPAGE-06`)

Superseded by `docs/specs/2026-10-30-embedding-corpus-design.md`, which ranks
related posts by content instead of by category.

79% of posts have no internal link in the body. Add a "More on <category>"
list of 3 posts from the primary category under the article in
`templates/single.html`, using a `core/query` with `taxQuery` on the
current category and excluding the current post. Plain list, no cards,
following the existing archive list styles.

## Phase 3: content

Editorial work, no deploy needed. Any WP-CLI step runs in the production
app container (Dokploy), only after a `--dry-run` and a database backup.
Try each one on the local stack first after `/sync-from-prod`.

### 3.1 Internal links to https (`ONPAGE-06`)

112 body links in 79 posts use `http://www.ivankristianto.com/`, each a
301 hop.

```bash
wp search-replace 'http://www.ivankristianto.com' 'https://www.ivankristianto.com' wp_posts --include-columns=post_content --dry-run
```

### 3.2 Broken and off-site images (`ONPAGE-07`, `TECH-04`)

- 12 `<img>` on `http://www.ivankristianto.local/images/...` (for example
  `/git-introduction/`) and 4 on `http://cdn.ivankristianto.com/static/...`
  (the MaxCDN benchmark post). Re-upload from the legacy files if they
  exist, otherwise remove the image and any claim that depends on it.
- 232 of 257 body images lack `width`/`height` and `srcset`. Extend
  `class-fix-image-ids-command.php`, or add a sibling, to resolve
  classic-markup `<img>` tags (it only handles block markup today) to
  attachments and add the `wp-image-ID` class, so core adds dimensions and
  `srcset`.
- Alt text: 92 images use the filename as alt. Fix them on posts that are
  kept (3.4); leave retired posts alone.
- Featured images (`ONPAGE-X2`): the regex cheat sheet uses an unrelated
  550x250 CSS image. List `_thumbnail_id` against the first body image for
  all migrated posts and fix the mismatches.

### 3.3 Meta descriptions (`ONPAGE-04`)

Write first-person descriptions for home, /about/, /resume/, /projects/,
/articles/, /speaking/, /contact/, /accessibility/ and the 20 to 30 old
posts with the most Search Console clicks. Leave the long tail empty so
Google writes its own snippet. Clean leftovers like `:sadpanda:` from old
excerpts.

### 3.4 Thin migrated posts (`ONPAGE-02`, `GEO-07`)

228 posts are under 250 words and 14 under 50 (full list:
`docs/superpowers/specs/2026-10-30-thin-posts.tsv`, published date, modified
date, word count, URL) (for example
`/speaking-wordcamp-ubud-2017/` at 13 words, `/wordpress-4-2-1-security-release/`
at 15, `/holiday-is-coming/` at 17). For each one, record one decision:

- **Keep as dated**: still accurate for its version. Add a one-line note
  naming the version it was written for.
- **Update**: still gets traffic. Lead with the answer, add H2s, turn
  screenshots into text, then restore a real modified date by saving it.
- **Merge**: overlaps a better post. Move anything useful, 301 the old
  slug through the 2.5 map.
- **Retire**: news blips and release notes with no lasting value. Noindex
  (Yoast meta box) or delete with a 301 to the closest topic.

Start from the posts with clicks in Search Console. To regenerate the
list, page through `/wp-json/wp/v2/posts?per_page=100&_fields=link,date,modified,content`
and count words in `content.rendered` with tags stripped. Don't mass-delete:
Google flags removing old content just for rankings.

### 3.5 Answer-first edits on recent posts (`AEO-02`, `AEO-03`, `AEO-05`, `AEO-07`, `AEO-X3`)

- `/split-webfont-text-layout/`: move "The short version" from paragraph 3
  to paragraph 1, and turn the three timing comparisons in `<pre>` into
  `core/table` blocks with a `Setup | Layout time` header.
- `/read-only-webmcp-wordpress-blog/`: add a one-sentence "WebMCP lets a
  page..." definition in the first two paragraphs, and phrase the testing
  H2 the way people search ("How do I test WebMCP tools in Chrome?").
- `/regular-expressions-cheat-sheet/`: rebuild the cheat sheet as HTML
  tables (token, meaning, example) grouped by anchors, classes,
  quantifiers, groups and flags. Restore or remove the 404 PDF link.
- `/why-you-should-always-site-with-https/`: put the injected script in a
  code block and lead with the claim.
- `/how-to-code-with-ai-agents/`: add a five-item `<ol>` summary of the
  practices, or accept it as a link post. Don't canonicalize it to the
  Human Made article, since the page is a teaser, not a copy.

Going forward, the `blog-draft` and `blog-seo` skills should check for an
answer in the first paragraph and tables for any comparison.

### 3.6 Tag pruning (`ONPAGE-01`)

After 1.4, merge or delete the 925 one-post tags. Keep tags with 3 or more
posts. This shortens the tag list printed on every post and leaves the
remaining tags worth re-indexing later.

## Phase 4: optional

- **HTML edge cache** (`TECH-X1`). Every HTML response is
  `cf-cache-status: DYNAMIC`, with TTFB 0.4 to 0.8s from Singapore. A
  Cloudflare Cache Rule for `www`, bypassing `/wp-admin*`, `/wp-login.php`,
  `/wp-json*`, `?s=` and cookies `wordpress_logged_in_`, `wp-postpass_`,
  `comment_author_`, with edge TTL 1 to 4 hours and a purge on publish.
  `wp-super-cache` is in `composer.json` but cannot serve from nginx
  without a shared volume; remove it if the edge cache ships.
- **llms.txt** (`GEO-X1`). No vendor states it reads llms.txt, and Google
  says it has no effect. If you want it anyway, Yoast 28.6 can generate it,
  but it writes to `get_home_path()` in the app container, which nginx
  cannot see. Point `wpseo_llmstxt_filesystem_path` at the uploads volume
  and add `location = /llms.txt` in `docker/nginx/default.conf`.
- **Security headers on static assets**. Not a search issue, found on the
  way: the static `location` in `docker/nginx/default.conf` sets its own
  `add_header`, so CSS/JS/images lose `nosniff`, `X-Frame-Options` and
  `Referrer-Policy`. Move the headers into an include used by both blocks.
- **Markdown alternate**. Server-rendered HTML is already complete, so this
  is a convenience for agents. Skip unless 1.6's full-text feed proves
  insufficient.

## Out of scope

- FAQPage, HowTo, QAPage and speakable markup. Google stopped showing FAQ
  results on 2026-05-07 and HowTo results in 2023.
- Allowing `/wp-json/` in robots.txt (`GEO-X3`). Crawlers index the HTML,
  and WebMCP runs in the reader's browser, which robots.txt does not
  govern.
- Chasing Yoast readability scores, keyword density or word counts.

## Order and dependencies

1. Phase 1 settings (1.1 to 1.5) in one sitting.
2. 2.1, 2.2, 2.4 and 2.7 can ship in any order.
3. 2.6 before the "updated" date in 2.3.
4. 2.5 before 3.4, so merged and retired posts have a redirect path.
5. The HTML edge cache (Phase 4) before judging 1.6's Crawler Hints.
6. Re-run the audit 90 days after Phase 2 ships, using the method in the
   report, and compare against this baseline.
