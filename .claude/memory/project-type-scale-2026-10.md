---
name: project-type-scale-2026-10
description: Oct 2026 type scale rebuild on branch type-scale-a11y; 8 HM-shaped fluid steps, em breakpoints, why slugs were renamed
metadata:
  type: project
---

On 2026-10-23 Ivan approved (in the artifact https://claude.ai/artifact/4CKRwGEcKetiKLypfHdCvG) an 8-step scale shaped after the Human Made scale: `x-small` 14→15, `small` 16→17, `base` 18→20, `medium` 20→24, `large` 24→32, `x-large` 32→44, `xx-large` 40→60, `xxx-large` 48→76px. Each step is `clamp(min, rem + vw, max)`, fluid between 360 and 1200px. The 19 old tokens are gone. All 54 media queries are in em. Built on branch `type-scale-a11y` off `recolor-bookcloth-green`.

**Why the new slug names:** the stored home page content on prod carries `has-hero-font-size` and `has-xl-font-size`. Those preset classes are `!important`. Reusing `xl` for a 44px step would have blown up the hero blurb, so the steps take names that never existed. The hero and footer sizes now live in SCSS, and the old classes match nothing.

**How to apply:** don't reintroduce pure-vw clamp middles or px breakpoints, and keep reading text at `x-small` (14px) or larger. The topics grid overprint was fixed by the em breakpoints plus a wrapping row, not by an auto-fit grid (its borders depend on the column count). The header still wraps to two lines at 24 to 28px defaults on desktop; that's accepted.
