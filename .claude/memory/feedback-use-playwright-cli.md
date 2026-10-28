---
name: feedback-use-playwright-cli
description: For browser checks, use the playwright-cli skill first, not a throwaway npm playwright install
metadata:
  type: feedback
---

Use the `playwright-cli` skill (global `playwright-cli` command) for screenshots, DOM reads and click-throughs on local or prod pages.

**Why:** 2026-10-27, Ivan asked "I should've playwright-cli, no?" after I installed Playwright into the scratchpad instead.

**How to apply:** `playwright-cli open <url>`, then `--raw eval`, `run-code` for element screenshots, `close` at the end. It can't load `file://` pages; serve the folder with `python3 -m http.server` and open it over localhost. The scratchpad install in [[reference-browser-verification]] is only a fallback.
