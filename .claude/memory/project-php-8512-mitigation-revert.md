---
name: project-php-8512-mitigation-revert
description: PHP CVE stopgap (PR #22) reverted locally on branch revert-php-cve-stopgap on 2026-10-22; prod still runs v0.12.2 with the stopgap until a release ships it
metadata:
  type: project
---

On 2026-10-21, PR https://github.com/ivankristianto/ik2/pull/22 (commit 73f1ca6) added stopgap hardening for CVE-2026-109618 / CVE-2026-109619 while `wordpress:7.1.3-php8.5-fpm-alpine` and `wordpress:cli-php8.5` still shipped PHP 8.5.11. It shipped to prod in v0.12.2.

On 2026-10-22 both base images reported PHP 8.5.12, so the stopgap was reverted on branch `revert-php-cve-stopgap` (64M upload limits, session upload progress and fatal backtraces back on). Local app and wp-cli rebuilt and confirmed on 8.5.12.

**Why:** Ivan wanted the lowered upload cap gone once the patched base image existed.

**How to apply:** Remaining steps are a PR to main and a patch release, both only when Ivan asks. After the release deploys, check `php -v` in the prod app container, since the `build.yml` GHA layer cache can reuse the old 8.5.11 base. Once prod reports 8.5.12, delete this memory.
