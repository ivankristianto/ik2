---
name: project-cli-webroot-non-volume
description: Why the prod wp-cli image bakes its webroot at /var/www/cli (not /var/www/html) — VOLUME shadowing on Dokploy
metadata:
  type: project
---

The official `wordpress:cli`, `wordpress:*-fpm` images all declare `VOLUME /var/www/html`. Any code baked at that path becomes an **anonymous volume** at runtime that Dokploy/Compose **preserves across redeploys**, shadowing the image with stale, often root-owned content. This caused a wp-cli restart loop: `cp: can't create directory '/var/www/html/wp-content/themes': Permission denied` — the old entrypoint tried to refresh code *inside* that persisted root-owned volume as www-data (uid 82). Build-time seeding/chown can't fix an *already-persisted* volume; running the copy as root + su-exec failed because `wordpress:cli` has no gosu/su-exec.

**Fix (commit after 629069a4):** the `cli` Dockerfile stage bakes the full webroot at the NON-volume path `/var/www/cli` (WORKDIR), runs `wp` from there as uid 82, and `docker-compose.prod.yml` mounts only `uploads` at `/var/www/cli/wp-content/uploads`. Nothing is written into a volume at startup, so there's no permission problem and the cli always runs the deployed image's code, fresh per deploy. The inherited `/var/www/html` anonymous volume is left empty/unused. No startup mirror script — `docker/cli/docker-entrypoint.sh` was deleted; the stock `wordpress:cli` entrypoint is reused.

**Latent related risk (NOT yet fixed):** the `app` (php-fpm) image bakes code at `/var/www/html`, which is *also* a VOLUME. App only reads, so it never crashed, but a preserved anonymous volume could serve stale PHP across Dokploy redeploys. nginx is safe (bakes `/var/www/html` at build time via `COPY --from=app`). If app staleness ever appears, apply the same non-volume-path pattern or force `down -v` on deploy.
