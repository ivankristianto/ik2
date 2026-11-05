---
name: project-security-headers
description: where each security header comes from (nginx vs Cloudflare), COOP value, CSP spec status, nginx bind-mount gotcha
metadata:
  type: project
---

- HSTS is a Cloudflare zone setting (SSL/TLS → Edge Certificates), not nginx. Set to 12 months on 2026-11-05. Never add it in nginx too, or responses get two headers. The Agents vault Cloudflare token is read-only, so Ivan changes zone settings and purges the cache.
- nosniff, X-Frame-Options, Referrer-Policy and COOP live in `docker/nginx/default.conf`, repeated in the two static `location` blocks. COOP is `same-origin-allow-popups`, not `same-origin`, because Site Kit's wp-admin screens use `window.open` (v0.28.1).
- CSP: design in `docs/specs/2026-11-05-strict-csp-design.md` (nonce at print time, Report-Only first, no report collector). Report-Only shipped in v0.29.0 (PR #73, 2026-11-05) as `IK2\Plugin\Csp` (`wp-content/plugins/ik2/inc/csp/`); `const ENFORCE` in `namespace.php` switches Report-Only to enforce. `pnpm test:csp` crawls the flows (`CSP_COOKIE` adds a logged-in pass). On prod (checked 2026-11-05), Cloudflare copies the header's nonce onto the Web Analytics beacon, which is undocumented but works. Email obfuscation is different: `/contact/` and `/resume/` get `email-decode.min.js` with no nonce, so turn it off or drop the addresses before enforcing. Local and prod post IDs differ, so the crawl uses slugs. There's no prod shell, so the prod crawl's logged-in pass needs Ivan's cookie in `CSP_COOKIE`.
- Core's Interactivity router bundles a WebAssembly module lexer (`WebAssembly.compile`), so `/articles/` filter navigation raises a `script-src` `wasm-eval` violation. Under enforcement, filter clicks still navigate client-side. Ivan approved `'wasm-unsafe-eval'` and it is now in Q4 and the policy. Chrome reports `PrerenderingDisabledByDevTools` for any tab a DevTools client drives, so prerender checks are manual.
- Core already sends `Content-Security-Policy: frame-ancestors 'self'` on `wp-login.php`, on prod too. That isn't ours, and it isn't the strict CSP.
- APO caches HTML with its headers, so header changes show only on a `cf-cache-status: MISS` until Ivan purges.
- Edit `default.conf` with Edit, not `sed -i`: macOS `sed -i` swaps the inode, the single-file bind mount loses it, and `nginx -t` fails until `docker compose restart nginx`.
