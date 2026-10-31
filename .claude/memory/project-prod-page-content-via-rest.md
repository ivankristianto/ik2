---
name: project-prod-page-content-via-rest
description: Deploys don't change pattern-backed page content on prod; update it over the REST API, never with regen-page-content.php
metadata:
  type: project
---

Pattern-backed pages (home, about, contact, speaking, resume) store their blocks in post_content, so a release ships the CSS and patterns but leaves prod content unchanged. The prod resume header has drifted from `ik2/resume-page-header` ("Updated <month> 2026", no availability badge), so `bin/regen-page-content.php` would overwrite real edits.

**Why:** 2026-10-02, the certifications section had to be added to prod by hand after v0.5.x deployed.

**For blog posts, use `.claude/skills/blog-wp-draft/scripts/wp_draft.py`** (`preflight`, `push`, `seo`) instead of raw curl: it handles the masking, the block conversion, tags, media and Yoast meta, and verifies every write.

**Since 2026-10-31, use REST through 1Password, not Chrome** (Ivan stopped a Chrome attempt and refreshed the app password instead): `op run --env-file=.env.op.local -- <cmd>` gives `IK2_PROD_USER` / `IK2_PROD_APP_PASSWORD` from `op://Agents/ik2 prod/...`, and they work on prod. `op run` masks secret strings in its stdout (the username "ivan" shows as `<concealed by 1Password>` inside URLs), so write responses to a file from inside the child (`curl -o`). Yoast's `_yoast_wpseo_focuskw`, `_yoast_wpseo_title` and `_yoast_wpseo_metadesc` are writable as post `meta` over REST, so a draft plus its Yoast fields can be created without the editor; only Yoast's computed scores need the editor. The `yoast-seo/*` abilities at `/wp-json/wp-abilities/v1/abilities/<name>/run` read SEO data (GET, `input[post_id]=`) but can't set title, description or keyphrase.

**Older notes:** As of 2026-10-23 there is no `.env` in the checkout, only `.env.example`. Without it, use Ivan's logged-in Chrome session (claude-in-chrome): open any wp-admin page and call `wp.apiFetch` from the page, which sends the REST nonce for you (how the /accessibility page, ID 2001, was created). With `.env`, fetch `/wp-json/wp/v2/pages?slug=<slug>&context=edit` with `IK2_PROD_USER` / `IK2_PROD_APP_PASSWORD` from `.env` (the `.mcp.json` password belongs to the old staging host and gets `rest_not_logged_in` on prod; so does any bad credential, which doesn't mean the header is being stripped). The `.env` value has unquoted spaces, so parse it as key=value instead of `source`-ing it. Cloudflare returns 403 to Python's default `Python-urllib` user agent, so set one. Splice in the serialized pattern block at a unique anchor, back up the raw content, dry-run the diff, confirm with the user, then POST. The `ik2` MCP abilities can't edit content (site info and Yoast only).
