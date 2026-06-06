---
name: project-cli-webroot-non-volume
description: All prod images bake the webroot at non-volume /var/www/app (not /var/www/html) — VOLUME shadowing on Dokploy serves stale code across redeploys
metadata:
  type: project
---

The official `wordpress:cli`, `wordpress:*-fpm` images all declare `VOLUME /var/www/html`. Any code baked at that path becomes an **anonymous volume** at runtime that Dokploy/Compose **preserves across redeploys**, shadowing the image with stale, often root-owned content. This caused (1) a wp-cli restart loop (`cp: can't create directory ... Permission denied` — old entrypoint writing into a persisted root-owned volume as uid 82) and later (2) the app container silently serving the FIRST deploy's code forever: new images (with `wp ik2` CLI, mcp-adapter) were pulled but never executed. Build-time seeding/chown can't fix an already-persisted volume.

**Fix (June 2026, unified):** all three images bake the full webroot at the NON-volume path `/var/www/app` (WORKDIR). The official wordpress entrypoint operates on the current directory, so changing WORKDIR is sufficient — it sees index.php + wp-config.php and skips its copy/generation. nginx's `default.conf` sets `root /var/www/app`; the path string must match the app container's filesystem because `SCRIPT_FILENAME = $document_root + script` is resolved by php-fpm on *its* filesystem. Only `uploads` is mounted, at `/var/www/app/wp-content/uploads`. The inherited `/var/www/html` anonymous volume is left empty/unused (upstream's empty skeleton dirs only). Dev compose mounts the `wp-html` named volume and all bind mounts at `/var/www/app`; the dev wp-cli service needs explicit `working_dir: /var/www/app` (stock image's WORKDIR is /var/www/html).

**One-time remediation after path moves:** drop-ins with absolute paths go stale — Query Monitor's `wp-content/db.php` symlink pointed at `/var/www/html/...` and caused a fatal "Cannot redeclare ComposerAutoloaderInit" (old + new autoloader both loaded). Fix: `rm wp-content/db.php`, then deactivate/activate query-monitor to regenerate it. On hosts with stale anonymous volumes: `docker compose up -d --force-recreate --renew-anon-volumes` (or `docker compose rm -sfv <svc>` — `-v` removes only anonymous volumes, named ones like `uploads` are safe).
