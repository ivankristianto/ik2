---
name: feedback-opcache-restart
description: "When editing theme PHP files (functions.php, inc/*.php) in this WordPress repo, restart the app container to bust PHP-FPM opcache - in-place edits to `require_once` lines or newly-added files don't take effect until restart"
metadata: 
  node_type: memory
  type: feedback
  originSessionId: 065318b4-4442-4f87-9531-733c1d99c21f
---

When editing theme PHP files in `wp-content/themes/ik2/` (functions.php, inc/*.php, blocks/*/render.php, patterns/*.php), restart the app container after touching the file layout — `docker compose restart app`.

**Why:** The dev image runs PHP-FPM with opcache, and opcache does not auto-revalidate fast enough for changes like:
- Adding a new `require_once` to `functions.php`
- Creating a new file under `inc/` or `blocks/`
- Adding a new `register_block_type` call

Without a restart you get phantom failures: `error_log()` calls in newly-required files never appear, `register_block_type()` silently fails, custom blocks render as empty whitespace, and you waste an hour thinking it's a pattern-parsing or render_callback bug. Verified during the articles archive build.

**How to apply:**
- Editing *existing* block markup, theme.json, CSS, or pattern bodies: no restart needed (bind-mounted, opcache picks up content changes within a few seconds).
- Editing *function bodies* or *adding/removing files in the autoload tree*: `docker compose restart app` before testing in the browser.
- If you suspect opcache: `composer dev:wp:cmd -- eval 'opcache_reset();'` does NOT help — CLI and FPM have separate opcache instances. Only the container restart works.
