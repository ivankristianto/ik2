# Strict CSP

## Goal

Send a strict `Content-Security-Policy` on every front-end HTML page, so a
script that gets into the markup without WordPress printing it does not run.
That clears the PSI "Ensure CSP is effective against XSS attacks" finding, and
the policy has to block injected scripts for real. A header that only turns
the audit green doesn't count.

PSI run that prompted this:
<https://pagespeed.web.dev/analysis/https-www-ivankristianto-com/mtj7d01avv?form_factor=mobile>.
The four Trust and Safety items are unscored. COOP and HSTS are handled
separately (nginx header, Cloudflare HSTS max-age), so this spec covers CSP
only.

## Decisions

| # | Decision |
| --- | --- |
| Q1 | Nonce, added as WordPress prints each script, and cached by APO along with the header. Not hashes, and not an edge Worker for now. See **Why a nonce on a cached site**. |
| Q2 | A new ik2 plugin module, `IK2\Plugin\Csp`, with a `bootstrap()`. Security headers are site behaviour, so they belong in the plugin, and the theme stays presentation. A pure policy builder sits beside it with no WordPress calls, and PHPUnit covers it. |
| Q3 | Front-end HTML only. The header is sent from `template_redirect`, so wp-admin, `wp-login.php` and REST never get it (none of them reach that hook). Skip feeds, `robots.txt`, the Customizer preview and the Yoast sitemaps. |
| Q4 | Policy: `script-src 'nonce-{n}' 'strict-dynamic' 'wasm-unsafe-eval' https: 'unsafe-inline'; object-src 'none'; base-uri 'none'; frame-ancestors 'self'`. `'wasm-unsafe-eval'` was added after the build: core's Interactivity router (`interactivity-router/index.js`) bundles a module lexer that calls `WebAssembly.compile`, which raised a `wasm-eval` violation on `/articles/` filter navigation. It allows WebAssembly compilation only, not JS `eval`, and Lighthouse `csp-xss` reported no findings with it. `https:` and `'unsafe-inline'` are fallbacks for older browsers. A browser with `strict-dynamic` ignores both. A browser with nonces but no `strict-dynamic` ignores `'unsafe-inline'` and falls back to `https:`. `frame-ancestors 'self'` matches the `X-Frame-Options: SAMEORIGIN` nginx already sends, and both stay. No `report-to` or `Reporting-Endpoints` (Q7). If a collector is added later, add `'report-sample'` with it. |
| Q5 | `style-src` is out of scope. Pages print 13 to 24 inline `<style>` blocks (critical CSS, block styles, section styles). Lighthouse's CSP audit checks only `script-src`, `object-src` and `base-uri`. |
| Q6 | Trusted Types is out of scope, and the PSI Trusted Types item stays flagged. It has been Baseline since 2026-02-24 (Firefox 148, Safari 26), so it's worth a follow-up spec. Known sinks: `wp-content/themes/ik2/src/lightbox.js:33` and `wp-content/themes/ik2/src/palette.js:474` (`innerHTML`), plus whatever gtag and the Interactivity router do. The lightbox one is a static SVG string and can become `createElementNS` calls. The palette one wraps search matches and can use `setHTML()` or built nodes. The follow-up starts with `require-trusted-types-for 'script'` in Report-Only to find the rest. A pass-through `default` policy doesn't count as a fix. |
| Q7 | No live report collection. Report-Only violations are found by a Playwright crawl of the local stack and of prod that listens for `securitypolicyviolation`, plus the DevTools Issues tab. Rejected: a REST collector in the ik2 plugin (a public write endpoint to guard) and a third-party collector (another service holding visitor data). |
| Q8 | Rollout: Report-Only for one release, then enforce. One constant (or option) switches the header name, so enforcing is a one-line release. |
| Q9 | Cloudflare's injected scripts get their own handling. See **Cloudflare injections**. |
| Q10 | The three legacy posts with raw `<script>` tags (544, 929, 939) load `http://` scripts that browsers already block as mixed content on an `https` page. CSP changes nothing for them. Cleaning that markup up is separate content work. |

## Why a nonce on a cached site

web.dev's strict CSP guide (<https://web.dev/strict-csp/>) says to use a nonce
when the server renders each response and a hash when HTML is cached or
static. This site is cached: Cloudflare APO serves the homepage as
`cf-cache-status: HIT`. So the guide's default would be hashes. It isn't
here, for these reasons.

**What a cached nonce still protects.** The nonce comes from `random_bytes(16)`
in the same render that prints the page. Any server-side payload that reaches
the page (post content, a GitHub repo name in the lately card, a reflected
query string) is part of that render. The attacker can't know the nonce before
the render, and the payload can't carry it. Editing content triggers a
Cloudflare purge (`wp-content/plugins/ik2/inc/cloudflare-purge.php` plus the
Cloudflare plugin), and the next render gets a new nonce. A query string is a
new cache key, so it also gets a new render.

**What it doesn't protect.** The nonce lasts as long as the cache entry, and
anyone can read it from the cached HTML. A DOM-based injection that doesn't
change the cache key could reuse it: a URL fragment that ends up in
`document.write`, or any other sink that inserts a parser-inserted
`<script nonce>`. The site's own JS has no such sink (`innerHTML` doesn't run
scripts it inserts, and inline event handlers stay blocked whatever the
nonce). Third-party code (gtag, Site Kit) is not audited.

**Options considered.**

| Option | What it costs | Verdict |
| --- | --- | --- |
| A. Nonce at print time (this spec) | A filter on two core hooks and one header. External scripts keep `defer`, `async`, `type="module"`, `fetchpriority` and preload-scanner discovery. | Chosen. |
| B. Hashes | Inline scripts are easy to hash at print time. External scripts are the problem: under `'strict-dynamic'` a parser-inserted `<script src>` needs a nonce, or a hash matched through its `integrity` attribute. Browser support for that hash matching outside Chrome is unverified. The fallback is a hashed inline loader that creates each script element, which loses `defer` ordering and hides every script from the preload scanner. | Rejected for the loader's cost to load order and LCP. Browser support was never the deciding factor. |
| C. Edge Worker | Origin prints a per-render placeholder nonce, and a Cloudflare Worker swaps that exact value for a fresh one on every response, in the HTML and the header. It closes the cached-nonce gap above. It needs a Worker on the APO route, a deploy outside the two Docker images, and a check that APO and the Worker cooperate. | Upgrade path if third-party code ever gets a DOM sink, or the threat model changes (comments, user accounts). |

Option B only becomes attractive if the external scripts go away. Option A's
mechanism carries over unchanged to option C.

The tempting shortcut, an output buffer that stamps a nonce or computes a hash
for every `<script>` in the finished HTML, is ruled out for all three options.
The `security` guide in modern-web-guidance names it as an anti-pattern:
"post-processing rendered HTML cannot distinguish developer-authored `<script>`
tags from untrusted `<script>` tags introduced via reflected or stored HTML
injection". That is why the nonce goes on at print time, inside WordPress's
own tag builders.

## SRI is a different tool

Subresource Integrity (`<script src integrity="sha384-…" crossorigin>`) and a
CSP hash both use a SHA digest, and they answer different questions.

- SRI is per file. It answers "is this the file I meant?" The browser hashes
  the fetched bytes, and if they don't match, it refuses to run the file. That
  protects against a tampered CDN copy. It does nothing about an injected
  `<script src="https://evil.example/x.js">`, which has no `integrity`
  attribute and runs.
- CSP is per page. It answers "is this script allowed to run here at all?"
  An injected tag without the nonce doesn't run.

SRI doesn't fit this site. gtag updates in place, and the guide says to "omit
`integrity` on that URL and restrict it with Content Security Policy" for such
scripts. Theme and plugin files are same-origin: anyone who can change them on
the server can also change the HTML that would carry the hash. So no `integrity`
attributes are planned.

## Mechanism

WordPress 7.1 builds every enqueued `<script>` tag through two functions in
`wp-includes/script-loader.php`. External tags go through `wp_get_script_tag()`
(filter `wp_script_attributes`), including script modules and the plain
`WP_Scripts` output at `wp-includes/class-wp-scripts.php:484`. Inline tags go
through `wp_get_inline_script_tag()` (filter `wp_inline_script_attributes`),
including `before`/`after` blocks, translations, localize data, the import map
and speculation rules. The module:

1. On `template_redirect` (priority 0), decides whether the request is in
   scope (Q3), creates the nonce with `random_bytes(16)` encoded as base64,
   keeps it in a static for the request, and calls `header()` with the policy.
   The nonce exists before any output, so no output buffer is needed.
2. Hooks both attribute filters and adds `nonce` to every tag. Data blocks
   (`application/ld+json`, `application/json`) don't need one. Adding it is
   harmless, and skipping it would mean keeping a type list.
3. Never stores the nonce: not in Redis, a transient, post meta or a block
   render cache. Only APO's cached copy of the full response, header and body
   together, may hold it.

Scripts that trusted scripts load at runtime pass under `'strict-dynamic'` with
no nonce: webpack chunks (palette, lightbox, highlighter, WebMCP), gtag's own
loads, and `import()` calls from modules, including the Interactivity router's
modules on client navigation.

One core tag skips both filters: `WP_Script_Modules::print_script_module_preloads()`
prints `<link rel="modulepreload">` with a bare `printf`
(`wp-includes/class-wp-script-modules.php:600`), so it gets no nonce. Under
`'strict-dynamic'` the preload may be refused. The module still loads when a
trusted module imports it, so only the preload
is lost and the feature still works. The Report-Only crawl on `/articles/` (which preloads the
Interactivity modules) settles it. If it does fire, unhook core's printer and
print the same tags with the nonce from the module. Settled on 2026-11-05: with
the policy enforced locally, `/articles/` raised no violation and every preload
was fetched. `pnpm test:csp` keeps checking that each `modulepreload` has a
resource timing entry.

Anything printed as a raw `<script>` outside these two functions has no nonce
and is blocked under enforcement. Today that's only the three legacy posts in
Q10. Yoast's JSON-LD is a data block and is unaffected.

Callbacks are named functions in the module namespace, per the repo's PHP
conventions.

## Cloudflare injections

Checked against the live site on 2026-11-05 with the read-only zone token and a
real browser load. curl with a custom User-Agent misses these.

| Injection | State | Handling |
| --- | --- | --- |
| Web Analytics beacon, `https://static.cloudflareinsights.com/beacon.min.js` | Off at the edge since 2026-11-06 (manual JS snippet installation) | **Settled 2026-11-06:** `IK2\Plugin\DeferredAnalytics` injects the beacon from a nonced inline script after window load, in production only, so `'strict-dynamic'` admits it (PR #79, done for LCP). Earlier notes: The edge inserts it as a parser-inserted script with no nonce, so `'strict-dynamic'` blocks it. Some reports say Cloudflare copies the nonce from the CSP header onto its own injected scripts, but that is undocumented, so don't rely on it. Preferred fix: turn off automatic injection in the Cloudflare dashboard and enqueue the beacon through `wp_enqueue_script` with its site token, so it gets the nonce like everything else. Alternative: keep auto-injection and confirm in Report-Only whether it gets stamped. Checked on prod after v0.29.0 (2026-11-05): the beacon is injected for browser loads only (not in `fetch()` or curl HTML), it carries the header's nonce, and the crawl saw no violation. So auto-injection works today, on undocumented behaviour. |
| Email Address Obfuscation | On | When a page contains an email address, Cloudflare rewrites it and injects `/cdn-cgi/scripts/.../email-decode.min.js`, which has no nonce. **Deferred.** Report-Only doesn't block it, so it can wait, but it must be settled before enforcement: turn it off, or confirm no published page shows an address. Checked on prod after v0.29.0 (2026-11-05): `/contact/` and `/resume/` show an address and get `email-decode.min.js` with no nonce, and Cloudflare does not stamp it. Handled 2026-11-05, pending the prod check: `IK2\Plugin\Email` runs every address in `the_content` through `antispambot()`, so the HTML holds no plain address for Cloudflare to rewrite. That covers the two pages, the accessibility report block and 15 older posts with addresses in code samples. The setting stays on. Checked on prod after v0.29.1: encoded address text is left alone, but Cloudflare decodes entities in an `a` tag's `href` and still rewrote `mailto:` links on `/contact/`, `/resume/` and `/accessibility/`, injecting its decode script. So the filter also wraps each `mailto:` link in Cloudflare's documented `<!--email_off-->` and `<!--/email_off-->` comments (<https://developers.cloudflare.com/waf/tools/scrape-shield/email-address-obfuscation/#prevent-cloudflare-from-obfuscating-email>). With both, Cloudflare rewrites nothing on this site, and the address is protected only by the entities. Enforcement waits for a prod crawl that shows no `email-decode.min.js`. A header allowlist can't admit the script: `'strict-dynamic'` ignores host and path sources, and dropping it would bring back the `'self'` bypass that the REST API's `?_jsonp=` gives. After the release, `pnpm test:csp` on prod without `CSP_ALLOW_EDGE` must show no `email-decode.min.js`. It crawls `/contact/`, `/resume/`, `/accessibility/` and `/send-bulk-email-with-php/`. |
| Rocket Loader | Off | Must stay off: it rewrites every script tag and breaks nonce matching. |
| Zaraz (Cloudflare's edge tag manager) | Unknown (token lacks scope) | Check in the dashboard. If it's on, it injects a loader script, and Cloudflare's docs say it edits the origin's CSP header to allow it. GA runs through Site Kit, so it's most likely off. |

## Shape

```
wp-content/plugins/ik2/
├── plugin.php                # + require_once inc/csp/rules.php, inc/csp/namespace.php
└── inc/csp/
    ├── namespace.php         # IK2\Plugin\Csp: bootstrap(), template_redirect handler, attribute filters, scope check
    └── rules.php             # IK2\Plugin\Csp\Rules, pure: build_policy( string $nonce, bool $enforce ): array{name, value}
tests/php/CspRulesTest.php
scripts/csp-smoke.mjs         # host-run headless Chrome crawl behind `pnpm test:csp`, like scripts/webmcp-smoke.mjs
```

This follows `inc/activity/` (a `namespace.php` plus a pure `rules.php`).
`IK2\Plugin\bootstrap()` in `inc/namespace.php` gains one `Csp\bootstrap()`
call, next to `Activity\bootstrap()`.

## Rollout

1. Release N ships `Content-Security-Policy-Report-Only`. Report-Only does not
   clear the PSI finding. That's expected for this release.
2. On prod, after a purge, fetch the homepage twice with
   `curl -s -D headers.txt -o page.html https://www.ivankristianto.com/` (a GET,
   since `-I` sends HEAD and returns no body). The second response must be
   `cf-cache-status: HIT`, and the nonce in `headers.txt` must equal the
   `nonce` attributes in `page.html`. If APO strips or regenerates the header, stop: option A
   doesn't work under APO, and option C is the path.
3. Run the Q7 crawl against prod, and browse the site for a week with the
   DevTools Issues tab open. Fix each violation at its source (enqueue it
   properly), never by loosening the policy. Settle the beacon and email
   obfuscation rows in **Cloudflare injections**.
4. Release N+1 flips the header to `Content-Security-Policy`.

To roll back, flip the constant back to Report-Only and release a patch. No
data changes.

## Acceptance

- No violations on these flows, locally and on prod in Report-Only. On prod
  this applies once the beacon and email obfuscation rows in **Cloudflare
  injections** are settled, since both inject scripts without a nonce:
  - home
  - a single post with code blocks and images (highlighter, lightbox)
  - the command palette opened and searched
  - `/articles/` with filters clicked, so the Interactivity router does client
    navigation (the riskiest flow)
  - a speculation-rules prerender of an article link (manual: Chrome reports
    `PrerenderingDisabledByDevTools` for any tab a DevTools client drives, so the
    crawl can't prerender. Hover an article link with DevTools closed, then check
    the Issues tab after it activates. The crawl still checks the
    `speculationrules` script's nonce on the source page.)
  - 404
  - `/contact/`, `/resume/`, `/accessibility/` and an old post with addresses in code (`/send-bulk-email-with-php/`): each shows an email address, so Cloudflare's email obfuscation would run there if one reached the HTML unencoded
  - `/projects/` (projects are non-public with no rewrite, so there is no single project page)
  - the logged-in front end with the admin bar
- Site Kit / GA4 events still arrive in GA4 Realtime from a test visit.
- Every executable `<script>` in the **raw HTML response** carries the
  header's nonce. Check the response text. After parsing, the
  browser hides the value, and `getAttribute('nonce')` returns `""`.
- With enforcement on, Lighthouse's `csp-xss` audit reports no findings on the
  homepage and a single post.
- `docker compose --profile tools run --rm composer quality` (PHPCS and
  PHPStan) and `... composer test` (PHPUnit) pass, and the PHPUnit test covers: the nonce appears once in `script-src`, and the
  Report-Only and enforce header names.
- `pnpm test:csp` (`scripts/csp-smoke.mjs`) reports zero
  `securitypolicyviolation` events on the flows above, except the manual
  prerender check. Locally it fetches a logged-in cookie from wp-cli; elsewhere
  pass `CSP_COOKIE`. `CSP_ALLOW_EDGE=1` downgrades the two Cloudflare injections
  to warnings until their rows are settled.

## Out of scope

- `style-src`, `img-src`, `connect-src`, `frame-src`. Locking those down needs
  its own inventory and can follow once `script-src` is enforced.
- Trusted Types (Q6).
- wp-admin and the block editor.
- Moving security headers from nginx to PHP. `X-Frame-Options`, `nosniff`,
  `Referrer-Policy` and COOP stay in `docker/nginx/default.conf`.
