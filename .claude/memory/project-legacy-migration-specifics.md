---
name: project-legacy-migration-specifics
description: Real old-site facts for the legacy article migration — table prefix cfx_, uploads served from /uploads/, dev DB already pre-populated with article text minus images
metadata:
  type: project
---

Facts discovered running `wp ik2 migrate-articles` against the real old
www.ivankristianto.com dump (staged at `./legacy/old-site.sql` +
`./legacy/uploads/`), needed for any further run:

- **Legacy table prefix is `cfx_`** (not `wp_`). Core posts table is
  `cfx_posts`. Always pass `--legacy-prefix=cfx_`.
- **The old site serves uploads from `/uploads/`, not `/wp-content/uploads/`.**
  Always pass `--old-base-url=https://www.ivankristianto.com/uploads`. The
  staged copy is laid out as `/legacy/uploads/<year>/<month>/…` to match.
- **487 published posts** in the dump (plus 14 draft, 5 private).
- **The dev DB was already pre-populated** with all 487 legacy articles as
  *text* (correct dates/authors/bodies, no old-domain URLs) but **without
  images** — only ~64 attachments existed while ~472 posts pointed at dangling
  legacy `_thumbnail_id`s. Because the migration matches by slug, a normal run
  skips all of them; **`--force` is required** to overwrite the existing posts
  and sideload their images. Only 2 posts carried `_ik2_legacy_id` before this.

Working command (latest 10 done 2026-06-27):
`wp ik2 migrate-articles --legacy-db=legacy --legacy-prefix=cfx_ --old-base-url=https://www.ivankristianto.com/uploads --force [--limit=N]`

Two code fixes were needed for the real data and are now in the plugin:
ordering switched to `post_date DESC` so `--limit` takes latest-first, and
`Media_Sideloader::resolve_local_path` now derives the uploads URL marker from
`--old-base-url` instead of hardcoding `/wp-content/uploads/`. See
[[feedback-opcache-restart]] — restart wp-cli after editing these PHP files.
