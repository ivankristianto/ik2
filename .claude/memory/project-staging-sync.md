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

**Gotcha — staging media 404s because the prod compose mounts the uploads
volume at the WRONG path.** All 461 files uploaded fine via REST (WordPress on
the *app* container stored + processed them — dimensions/thumbnails confirmed),
but their public URLs 404. Root cause: all three runtime images (app, nginx, cli)
bake their webroot at `/var/www/app` (see [[project-cli-webroot-non-volume]];
Dockerfile WORKDIR /var/www/app, nginx `root /var/www/app`). The Dokploy prod
compose, however, mounts the shared `uploads` named volume at
`/var/www/html/wp-content/uploads` (app + nginx) and `/var/www/cli/...` (wp-cli)
— none is the real webroot. So WP writes uploads to the app container's EPHEMERAL
layer at `/var/www/app/wp-content/uploads` (volume unused → also lost on every
redeploy), and nginx serves its empty baked copy → 404. Dev `compose.yaml` gets
it right (`uploads:/var/www/app/wp-content/uploads:ro` on nginx), which is why
media serves 200 locally vs 404 staging for the same file.

Fix (DONE 2026-06-27): mounted the volume at `/var/www/app/wp-content/uploads`
on all three prod services (matches the Dockerfile comment at line 229) and
redeployed. The 461 ephemeral files were gone after redeploy, so Option B was
run: `media_reset.php` deleted the 461 fileless staging attachments + cleared
`media.json`; re-ran `media.php` (fresh upload into the now-shared volume); cleared
`post/page/project.json` and re-ran those phases (upsert-by-slug UPDATES existing
entries). Verified: content + featured images now serve HTTP 200 on staging.
Posts 3406/3800 carry `http://localhost:8080` home links in LOCAL content, so any
posts.php re-run re-introduces them on staging — re-patch after
(str_replace localhost:8080 -> staging domain via the lib). Final staging: 487
posts, 8 pages, 5 projects, 9 cats, 1114 tags, 462 media (461 + 1 pre-existing).

App password used was disposable (user said they'd rotate it after).
