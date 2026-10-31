---
name: project-type-scale-2026-10
description: The 8-step fluid type scale (shipped Oct 2026) and why its slugs have names that never existed before
metadata:
  type: project
---

Shipped on `main` (2026-10-23): an 8-step scale shaped after the Human Made scale: `x-small` 14→15, `small` 16→17, `base` 18→20, `medium` 20→24, `large` 24→32, `x-large` 32→44, `xx-large` 40→60, `xxx-large` 48→76px. Each step is `clamp(min, rem + vw, max)`, fluid between 360 and 1200px. Every media query is in em.

**Why the new slug names:** the stored home page content on prod carries `has-hero-font-size` and `has-xl-font-size`. Those preset classes are `!important`. Reusing `xl` for a 44px step would have blown up the hero blurb, so the steps take names that never existed. The hero and footer sizes live in SCSS, and the old classes match nothing.

**How to apply:** never reuse an old slug (`xl`, `hero`, ...), don't reintroduce pure-vw clamp middles or px breakpoints, and keep reading text at `x-small` (14px) or larger. The topics grid overprint was fixed by the em breakpoints plus a wrapping row, not by an auto-fit grid (its borders depend on the column count). The header wrapping to two lines at 24 to 28px default text size on desktop is accepted.
