---
name: project-articles-normalized
description: All 487 legacy posts have been block-converted, shortcode→code, proofread, and recategorized; each is stamped _ik2_normalized=1 and _ik2_topic
metadata:
  type: project
---

On 2026-06-27 every published post (487) was run through a normalization pass
(after the legacy migration in [[project-legacy-migration-specifics]]):

1. Classic HTML → Gutenberg blocks (paragraph/heading/list/image/quote/code/embed).
2. Code shortcodes (`[bash]`/`[php]`/`[csharp]`/`[html]`/`[css]`/`[sql]`/`[code]`)
   → `wp:code`. `[img <attrs>]<url>[/img]` → `wp:image` (`[site_url]` stripped to
   `/uploads/...`). `[youtube]` → `wp:embed`. Un-convertible Flash/Wistia/iframe
   embeds preserved verbatim in `wp:html`.
3. Light prose proofreading (typos/grammar only; voice preserved; code never touched).
4. Recategorized to exactly one of the theme feature topics
   (`wordpress`/`ai`/`performance`/`web-apis`/`tooling`) else new `misc` category.
   Final: misc 200, tooling 173, wordpress 72, web-apis 26, performance 15, ai 1.

Each processed post carries meta `_ik2_normalized=1` and `_ik2_topic=<slug>`.
Tooling was a host file-pipeline under the gitignored `wp-content/plugins/_ik2_work/`
(`_export.php` → sonnet subagents read `in/<id>.json`, write `out/<id>.json` →
`_apply.php` ingests; `_audit.php` + `integrity.py` verify). To re-run a post, pass
its id to `_export.php`, regenerate, and re-apply (idempotent; the stamp skips done posts).

Follow-ups also done same day: (a) deleted 34 now-empty legacy categories — only
the 6 active + `guide`/`note` (theme home sections) + `uncategorized` remain;
(b) media-fix pass rewrote 202 in-content upload URLs to site-relative
`/wp-content/uploads/...`, sideloading 184 missing images from `/legacy/uploads/`
(stamp `_ik2_media_src_fixed`). Remaining gaps: 4 images absent from the backup
(firebug/measureit/fireftp.png in post 4022, sshot…png in 3936) and 1 `.sh`
download link (post 4047, non-sideloadable mime). Featured-image URLs still render
absolute (env-following home_url) — that is correct WP behaviour, not a defect.
See [[feedback-opcache-restart]] if editing the plugin PHP.
