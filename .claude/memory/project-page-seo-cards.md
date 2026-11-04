---
name: project-page-seo-cards
description: Static pages (home, articles, about, projects, resume, speaking) have Yoast fields + per-page social cards set over REST with wp_draft.py --page since v0.26.0
metadata:
  type: project
---

Since v0.26.0 (2026-11-04) the ik2 plugin's `inc/yoast-page-meta.php` exposes Yoast's focuskw, title, metadesc and opengraph-image(+id) on pages over REST; Yoast itself only exposes the first three, on posts. `wp_draft.py seo|feature ... --page` writes them. On pages, `feature` sets Yoast's social image, not featured_media: Yoast drives og:image, Twitter and the oEmbed `thumbnail_url` from it, and page templates never print a featured image.

Card sources, seo.txt and the pre-change backups live in `.claude/blog/pages/<slug>/` (gitignored; `build.py` regenerates the HTML). Media IDs 2081-2086 are page-card-<slug>.png, uploaded as PNG on purpose (same format as the default card, safe for LinkedIn).

Decided 2026-11-04: no portrait on the cards (Ivan declined; the 500px theme portrait was too small anyway), and PNG over JPEG. JPEG q85 at 2400x1260 came out 10-30% larger on these flat text cards; revisit only if a card ever carries a photo.

**Why:** home ranked ~6.5 for "ivan kristianto" with a title that said only "Ivan"; every page shared the default card.

**How to apply:** when a page's copy or facts change (new talk, new project, resume numbers), edit build.py, re-render, re-run `feature --page`. Related: [[project-prod-page-content-via-rest]], [[reference-yoast-og-image-after-rest-featured-media]].
