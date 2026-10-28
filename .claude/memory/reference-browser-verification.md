---
name: reference-browser-verification
description: how to verify front-end changes in real engines here, and the state of tests/ (not in CI, two pre-existing failures)
metadata:
  type: reference
---

- Use the playwright-cli skill first ([[feedback-use-playwright-cli]]). The chrome-devtools MCP can fail with "browser is already running" when another session holds its profile. A throwaway `npm i playwright` in the scratchpad is the last fallback: Chromium and WebKit launch fine. Firefox fails in the sandbox ("Could not find profile folder" headless, "Operation not permitted" headed), so Firefox needs a manual check.
- Good local fixtures for article images: post 503 (large photos with captions), 1981 (images linked to their media file), 494 (old `<ul>` gallery), 716 (14 small screenshots).
- `tests/*.test.mjs` run with `node --test tests/`. Nothing in CI runs them. `about-template-structure` and `home-patterns-structure` were already failing on main as of 2026-10-06.
- Playwright WebKit follows Safari's default "Tab skips links", so Tab-to-link checks only mean something in Chromium; use arrows/Enter (or Option+Tab) for WebKit.
- Lightbox triggers only appear when enlarging adds real size: at 1280 wide none show on post 1981. Open at 1920x1200, then resize.
- Local pattern-backed pages store markup in the DB; `wp post update <id> -` (stdin) restores a backup, a host file path is invisible to the container.

Related: [[project-docker-is-orbstack]]
