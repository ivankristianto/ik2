---
name: project-accessibility-page
description: /accessibility WCAG 2.2 AA report is a dynamic block fed by blocks/accessibility-report/report.php; how to update it and what's still open
metadata:
  type: project
---

`/accessibility` renders `ik2/accessibility-report`. The results (55 criteria, summary rows, next steps) live in `wp-content/themes/ik2/blocks/accessibility-report/report.php`, so each release updates the page. The stored page content is just `<!-- wp:ik2/accessibility-report /-->`. The breakdown counts are computed from the data. The page itself has to pass what it claims (320px reflow, 20 to 32px text, text spacing, axe), so retest it after any change.

**Why:** pattern-seeded page content never updates on release (see [[project-prod-page-content-via-rest]]), and the report changes after every test.

**How to apply:** after fixing something, change its status and note in report.php and the `tested` date, then rerun axe and the reflow checks. The first test (2026-10-23) found two failures in old post content: 31 images with no alt text in 14 posts plus six Flash embeds, and one table without header cells. Ten criteria are Not tested (embedded video captions, GIFs, link text in old posts, screen reader). 50 old posts skip from the title to h3; `bin/a11y-content-fixes.php apply` was evidently never run on prod. Keep the page's wording and class names (`ik-a11y-*`) original.
