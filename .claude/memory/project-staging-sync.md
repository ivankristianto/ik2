---
name: project-staging-sync
description: Local->staging content sync via REST API; staging nginx does not serve runtime-uploaded media (no shared uploads volume in prod), so REST-uploaded files 404
metadata:
  type: project
---

On 2026-06-27 all content was synced from the local dev DB to staging
(`https://next.ivankristianto.com`) via the WP REST API using an application
password. Synced: 9 categories, 1114 tags, 461 media, 487 posts, 8 pages,
5 projects. The toolkit lives in the gitignored scratch dir
`wp-content/plugins/_ik2_work/sync/` (run via `wp eval-file` in the wp-cli
container): `_lib.php` (REST client + JSON id-maps in `sync/state/`),
`terms.php`, `media.php`, `posts.php`, `pages.php`, `projects.php`,
`content_lib.php` (media-URL rewriter + upsert-by-slug). Every phase is
idempotent — id-maps in `sync/state/*.json` let re-runs skip done items.

Key behaviours: posts match staging by slug (create-or-update), publish dates
preserved via `date_gmt`, categories/tags/featured remapped through the id-maps,
project meta (status/tech/links/learned, all `show_in_rest`) carried. REST media
upload re-files into the CURRENT month folder (`/uploads/2026/06/...`), NOT the
original date, so in-content `/uploads/` URLs are rewritten by basename to the
returned staging URLs (sized variants fall back to full-size).

**Gotcha — staging does not serve runtime-uploaded media.** All 461 files
uploaded fine via REST (WordPress on the PHP-FPM *app* container stored and
processed them — dimensions/thumbnails confirmed), but their public URLs 404.
Cause: per the prod architecture (see [[project-cli-webroot-non-volume]]), the
`docker/nginx/Dockerfile` image BAKES its own copy of `wp-content` and runs with
**no shared uploads volume** with the app container — nginx serves
`/wp-content/uploads/` from its frozen build-time copy. Dev `compose.yaml` mounts
the shared `uploads` named volume into BOTH app and nginx (`:ro` on nginx), so
media serves locally (verified 200 local vs 404 staging for the same file).
Fix is Dokploy-side: mount a persistent `uploads` volume into the staging nginx
service too (mirror dev), or redeploy. The DB records + URLs are already correct,
so once nginx sees the files no re-sync is needed.

App password used was disposable (user said they'd rotate it after).
