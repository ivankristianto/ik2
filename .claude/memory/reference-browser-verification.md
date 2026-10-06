---
name: reference-browser-verification
description: how to verify front-end changes in real engines here, and the state of tests/ (not in CI, two pre-existing failures)
metadata:
  type: reference
---

- The chrome-devtools MCP can fail with "browser is already running" when another session holds its profile. A throwaway `npm i playwright` in the scratchpad works instead: Chromium and WebKit launch fine. Firefox fails in the sandbox ("Could not find profile folder" headless, "Operation not permitted" headed), so Firefox needs a manual check.
- Good local fixtures for article images: post 503 (large photos with captions), 1981 (images linked to their media file), 494 (old `<ul>` gallery), 716 (14 small screenshots).
- `tests/*.test.mjs` run with `node --test tests/`. Nothing in CI runs them. `about-template-structure` and `home-patterns-structure` were already failing on main as of 2026-10-06.

Related: [[project-docker-is-orbstack]]
