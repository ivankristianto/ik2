---
name: project-deferred-analytics
description: gtag.js and the Cloudflare Web Analytics beacon load after window load from the ik2 plugin; Cloudflare edge injection is off (manual snippet)
metadata:
  type: project
---

Since 2026-11-06 (PR https://github.com/ivankristianto/ik2/pull/79), `wp-content/plugins/ik2/inc/deferred-analytics.php` strips Site Kit's gtag.js src and injects it, plus the Cloudflare beacon (`type="module"`, public site token in code), on load + idle. Ivan switched Cloudflare Web Analytics to manual JS snippet installation, so the theme is the only source of the beacon.

**Why:** PSI desktop LCP was 979 ms (24/25 points). Lantern counts every request that finishes before the observed LCP paint, and on PSI's runner gtag (177 KB) and the beacon finished first. Without them the simulated LCP was 331 ms.

**How to apply:** don't re-enable Cloudflare auto-injection or move analytics back into `<head>`. For a new third-party script, load it the same way. To debug a simulated metric, re-run Lantern on a `lighthouse -G` trace with a moved cutoff (scripts lived in a session scratchpad, not the repo). The token line is fingerprinted in `.betterleaksignore`.
