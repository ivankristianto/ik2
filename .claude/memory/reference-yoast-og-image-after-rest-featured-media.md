---
name: reference-yoast-og-image-after-rest-featured-media
description: Setting featured_media over REST leaves Yoast's og:image stale until a second save; wp_draft.py feature handles it
metadata:
  type: reference
---

When `featured_media` is set over REST, WordPress saves the post first and attaches the thumbnail after. Yoast rebuilds its indexable only on `wp_insert_post` and on changes to its own `_yoast_wpseo_*` meta, never on `_thumbnail_id`. So og:image keeps pointing to the first image in the body, or stays empty. Seen on post 1985 on 2026-10-31. The refurbished posts were fine only because blog-seo wrote Yoast meta afterwards.

**Why:** a live post's link previews show the wrong image until something saves it again.

**How to apply:** set featured images with `wp_draft.py feature <post-id> <assets-dir>` (blog-wp-draft, "Featured image on its own"). It saves a second time when og:image doesn't match and has an `og_image` check. We chose that over a site-side hook in the ik2 plugin, which would call Yoast internals for an edge case that hit once. Drafts from `push` fix themselves when Ivan publishes from the editor. Related: [[project-prod-page-content-via-rest]], [[project-old-post-refresh]].
