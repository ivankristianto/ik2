---
name: project-php-8512-mitigation-revert
description: Temporary PHP CVE mitigations (PR #22, 2026-10-21) must be reverted once wordpress:*-php8.5-* images ship PHP 8.5.12
metadata:
  type: project
---

On 2026-10-21, PR https://github.com/ivankristianto/ik2/pull/22 (commit 73f1ca6) added stopgap hardening for CVE-2026-109618 / CVE-2026-109619, because PHP 8.5.12 was out on php.net but `wordpress:7.1.3-php8.5-fpm-alpine` and `wordpress:cli-php8.5` still shipped 8.5.11.

Temporary settings to undo:
- `docker/php/php.ini`: `session.upload_progress.enabled = 0`, `fatal_error_backtraces = 0`, `post_max_size` 32M (was 64M), `upload_max_filesize` 30M (was 64M), plus their CVE comments
- `docker/nginx/default.conf`: `client_max_body_size` 32M (was 64M), plus its comment

**Why:** Ivan wants these reverted once the patched base image exists. They lower the upload cap and were only meant to cover the gap.

**How to apply:** Check that a fresh `docker pull wordpress:7.1.3-php8.5-fpm-alpine` (and `cli-php8.5`) reports PHP >= 8.5.12 in `php -v`. Then revert 73f1ca6 (`git revert 73f1ca6`, or restore the values by hand if the files changed since), rebuild, confirm `php -v`, and ask Ivan before cutting the patch release. The `build.yml` GHA layer cache can reuse the old base, so check `php -v` in the deployed container too. The stopgap shipped to prod in v0.12.2 (2026-10-21), still on PHP 8.5.11.
