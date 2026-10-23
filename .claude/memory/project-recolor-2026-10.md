---
name: project-recolor-2026-10
description: Oct 2026 recolor to cut the AI-slop look; palette options A/B/C, scope, and which terminal motifs Ivan wants kept
metadata:
  type: project
---

On 2026-10-23 an impeccable critique found that color is about a third of the site's "AI slop" read. The tells are Tailwind orange-700/100 as the accent, nine Tailwind pastel category tints, macOS window dots, and cool greys sitting on warm paper. The rest comes from chrome and copy.

- Comparison page: https://claude.ai/artifact/S911Fc9RcGcTEazWheV6J7. It shows real screenshots of the site under each palette, applied by overriding `--wp--preset--color--*` at runtime.
- Palette options:
  - A. Blue-black, accent `#1F4E8C`. Brings back blue, which the brief retired.
  - B. Red pencil on green pad, accent `#B3261E`.
  - C. Bookcloth green, accent `#2F5D50`.
- Exact values for every option are in the critique snapshot under `.impeccable/critique/`.
- Scope chosen: color plus chrome, with the layout unchanged.
- **Keep the `~ $ Ivan` prompt wordmark and the `./ivan.jpg` portrait frame.** Ivan marked both off-limits. Greying the frame's window dots is fine.
- The `//` eyebrows and the 404 terminal are not protected. The mono eyebrows themselves are pinned in PRODUCT.md principle 5, so changing them means editing the brief.

**Why:** Ivan felt the design looked AI-generated and wanted to see how much a recolor would change that.
**How to apply:** Change only theme.json values; there are no color literals anywhere else except favicon.svg. Then update the docs and the 26 preview HTMLs, run `pnpm tokens`, and refresh `.impeccable/design.json`. Shipping it is a minor release.

**Status 2026-10-23:** Bookcloth (C) implemented on branch `recolor-bookcloth-green`, not yet committed. Removed `code-ink`, `categoryTint` and `windowDot` tokens. Eyebrows are now lowercase, and their `//` prefix is hidden by CSS (`.ik-section__eyebrow > span[aria-hidden]:first-child`) because stored page content still has the span, so no REST content edit was needed. Still open, waiting on Ivan: hero copy, surname, em dashes, stat strip, and the hero `//` quicklinks line.

**Ground, decided 2026-10-23:** Ivan rejected the cream paper because it looked like a friend's site (jericoaragon.com uses `#F9F7F3`) and picked "Drafting white" in the artifact's db (`decision/ground` = `w`). Paper is `#FCFCFA`, surface `#FFFFFF`, soft `#EFEFEB`, code `#F1F1ED`, ink `#1C1D1B`, graphite `#494A46`, dust `#5C5D58`, line `#DCDCD5`, rule `#B6B6AE`. The accent stays bookcloth `#2F5D50`. Don't drift back to warm off-white.
