---
name: project-recolor-2026-10
description: Constraints left over from the Oct 2026 recolor (shipped) that CLAUDE.md doesn't carry - protected chrome, no cream ground, hidden eyebrow prefix
metadata:
  type: project
---

The bookcloth green on drafting white recolor shipped on `main` (2026-10-23). CLAUDE.md now holds the palette rules. What it doesn't hold:

- **Keep the `~ $ Ivan` prompt wordmark and the `./ivan.jpg` portrait frame.** Ivan marked both off-limits. Greying the frame's window dots is fine.
- **Never drift back to a warm off-white ground.** Ivan rejected cream (`#F9F7F3`) because it looked like a friend's site.
- The eyebrows' `//` prefix is hidden by CSS (`.ik-section__eyebrow > span[aria-hidden]:first-child`) because stored page content still has the span. Remove the rule only after the content is edited over REST.
- The mono eyebrows are pinned in PRODUCT.md principle 5, so changing them means editing the brief.
- Copy items Ivan hadn't decided on 2026-10-23: hero copy, surname, em dashes, stat strip, the hero `//` quicklinks line. Ask before touching them.

**Why:** Ivan wanted the site to stop reading as AI-generated, and colour was about a third of that.
**How to apply:** colour changes go in theme.json only (favicon.svg is the one other literal), then `pnpm tokens`.
