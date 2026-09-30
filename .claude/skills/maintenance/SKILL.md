---
name: maintenance
description: Dependency maintenance for this repo. Bumps WordPress core (the Docker base image), every wpackagist plugin, all Composer packages, and all pnpm packages to their latest versions, reviews breaking changes before each major bump, then builds and verifies everything, fixes what broke, and opens a PR using the repo's PR template with version tables. Use when the user asks to "update dependencies", "run maintenance", "bump WordPress", "update plugins", "upgrade packages", or types /maintenance.
user-invocable: true
---

# Maintenance: update everything, then prove it still builds

Four update tracks, one verify-and-fix loop, then a pull request. Work through them in order. Each track ends in its own atomic commit so a bad bump can be reverted alone.

Everything runs through Docker (OrbStack, start it with `open -a OrbStack` if the daemon is down). Shorthand used below:

```bash
C="docker compose --profile tools run --rm composer"   # composer in a container
P="docker compose --profile tools run --rm pnpm"       # pnpm in a container
WP="docker compose exec -T wp-cli wp"                  # wp-cli against the dev stack
```

## 0. Preflight

1. `git status` must be clean. If it isn't, stop and ask.
2. Branch off `main` in place (no worktrees; the containers bind-mount the main checkout): `git switch -c chore-deps-YYYY-MM-DD`. No `/` in branch names.
3. Record a baseline **before touching anything**, so pre-existing failures don't get blamed on an update:
   ```bash
   $C install && $P install --frozen-lockfile
   $C quality; $P lint; $P build; node --test tests/
   ```
   Save the pass/fail of each to the scratchpad. A gate that already failed on `main` is reported, not fixed as part of this task, unless the user says otherwise.
4. Snapshot current versions for the final report: the `FROM wordpress:` line in `Dockerfile`, `$C show --direct`, and `$P list --depth 0`.

## 1. WordPress core

Core is **not** a Composer package here. It comes from the base image tag in `Dockerfile`:

```dockerfile
FROM wordpress:7.0.2-php8.5-fpm-alpine AS base
FROM wordpress:cli-php8.5 AS cli
```

1. Latest core: `curl -s https://api.wordpress.org/core/version-check/1.7/ | jq -r '.offers[0].version'`.
2. Confirm the matching image exists before editing: `docker manifest inspect wordpress:<ver>-php8.5-fpm-alpine >/dev/null && echo ok`. Official images lag core releases by a day or two. If the tag is missing, skip this track and say so.
3. Keep the PHP variant (`php8.5`) unless the user asks to change it. If a newer PHP variant exists, mention it in the report. `composer.json` pins `config.platform.php` to `8.4` and CI runs 8.4, so a PHP bump touches three places and is its own decision.
4. For a **major or minor** core bump (e.g. 7.0 → 7.1), read the release's Field Guide on make.wordpress.org/core and scan for: block API changes (`block.json` schema, `apiVersion`), theme.json schema version, Interactivity API changes, removed or deprecated functions used in `wp-content/themes/ik2`, `wp-content/plugins/ik2`, or `wp-content/mu-plugins`. Grep for any function named in a deprecation note.
5. Edit the `FROM wordpress:` tag. `wordpress:cli-php8.5` floats, leave it.
6. Bump the PHPStan stubs to the same core line so static analysis sees the new API: `$C update php-stubs/wordpress-stubs -W`. If `szepeviktor/phpstan-wordpress` caps the stubs below the new core line, note it and move on.

Commit: `chore(docker): bump wordpress core to <ver>` (include `composer.lock` if the stubs moved).

## 2. WordPress plugins

All third-party plugins are `wpackagist-plugin/*` entries in `composer.json`, plus `wordpress/mcp-adapter` from Packagist. Never download a zip into `wp-content/plugins/`.

1. List what's behind: `$C outdated --direct 'wpackagist-plugin/*' wordpress/mcp-adapter`.
2. For each plugin whose **major** version changes (or any `0.x` minor change, which is breaking under semver), pull its metadata:
   ```bash
   curl -s "https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&slug=<slug>&fields[sections]=1" \
     | jq '{version, requires, requires_php, tested, changelog: .sections.changelog}'
   ```
   Check `requires` ≤ the core version from track 1, `requires_php` ≤ 8.4, and read the changelog entries between the locked and the new version for removed hooks, settings migrations, or dropped features.
3. Grep our code for anything the changelog says was removed or renamed. The Dockerfile has plugin-specific build steps that depend on file paths inside plugins. Check they still hold:
   - `wp-redis/object-cache.php` (symlinked as the object-cache drop-in)
   - `wp-super-cache/advanced-cache.php` and `wp-super-cache/wp-cache-config-sample.php` (copied as drop-ins)
   - `mcp-adapter/composer.json` (nested `composer install` in the build stage, which fails loudly if `vendor/autoload.php` is missing)
4. `wordpress/mcp-adapter` is pre-1.0 with its own composer deps. Read its GitHub releases (github.com/WordPress/mcp-adapter) before crossing a minor version.
5. Widen constraints for majors with `$C require wpackagist-plugin/<slug>:^<new-major>` (one per plugin, or several in one call). Then `$C update 'wpackagist-plugin/*' wordpress/mcp-adapter -W` to pick up everything within range.

Commit: `chore(deps): update wordpress plugins`. Put each plugin's old → new version in the commit body.

## 3. Composer packages

Remaining packages: `composer/installers` plus the dev tooling (PHPCS, WPCS, PHPCSExtra, PHPStan, phpstan-wordpress, the phpcs installer).

1. `$C outdated --direct --major-only` and `$C outdated --direct --minor-only`.
2. For each major bump, before changing anything:
   - Read the package's `CHANGELOG.md` / `UPGRADE.md` / GitHub release notes for the versions in between.
   - Check peer compatibility with `$C why-not <package> <version>`. The PHPCS stack moves as a unit: `squizlabs/php_codesniffer`, `wp-coding-standards/wpcs`, `phpcsstandards/phpcsextra`, and `phpcsstandards/phpcsutils` must all support the same PHPCS major. If WPCS doesn't support the new PHPCS major yet, hold PHPCS back and say why.
   - For PHPStan, check whether `phpstan.neon.dist` uses options that were renamed or removed.
3. Widen constraints with `$C require --dev <pkg>:^<new-major>` (drop `--dev` for `composer/installers`), then `$C update -W`.
4. Run `$C quality` right away. New PHPCS sniffs or a stricter PHPStan release often flag existing code. Fix the code, don't loosen the ruleset. Check `phpcs.xml.dist` before adding any `// phpcs:ignore`, and never add a PHPStan baseline to hide new errors without asking.

Commit: `chore(deps): update composer packages`, with the fixes folded in if the bump doesn't pass `quality` without them.

## 4. Node packages (pnpm)

1. `$P outdated` to see current / wanted / latest.
2. `@wordpress/*` packages are released together from the Gutenberg monorepo and must stay on the same release line. Update them as a set with the repo's script: `$P packages-update`. Then check `@wordpress/scripts` major changes in its CHANGELOG (github.com/WordPress/gutenberg/blob/trunk/packages/scripts/CHANGELOG.md). Things that have broken this repo's setup before or are likely to: webpack config shape (`wp-content/themes/ik2/webpack.config.js` extends the default config), the ESLint flat config in `eslint.config.js`, and Stylelint rule renames in `@wordpress/stylelint-config`.
3. Everything else (`sass`, `postcss-scss`, `webpack-remove-empty-scripts`, `block-runner`): `$P up --latest <pkg>`. For a major bump read the changelog first. For `sass` in particular, look for newly removed deprecations (`@import`, global built-in functions, slash division) and grep `wp-content/themes/ik2/src` for them.
4. pnpm 10+ blocks dependency build scripts. If install warns about an ignored build for a new package, decide whether it needs its script and add it to `allowBuilds` in `pnpm-workspace.yaml` with a one-line reason comment, matching the existing entries.
5. pnpm itself: check `npm view pnpm version`. The version lives in **two** places that must match: `packageManager` in `package.json` and `corepack prepare pnpm@<ver>` in the `node-build` stage of `Dockerfile`. Update both (the `packageManager` hash can be regenerated with `$P self-update <ver>` or `corepack use pnpm@<ver>`).
6. Node itself (`node:24-alpine`, `engines.node`, CI `node-version`) is out of scope. Report a newer LTS if there is one, but don't bump it.

Commit: `chore(deps): update node packages`, with required fixes folded in.

## 5. Build and verify everything

Run all of it, even if an earlier step looked fine.

1. **Front-end build**, with the same output checks the Dockerfile enforces:
   ```bash
   $P build \
     && test -s wp-content/themes/ik2/build/critical.css \
     && test -s wp-content/themes/ik2/build/section-home.css \
     && test -s wp-content/themes/ik2/build/index.js \
     && test -f wp-content/themes/ik2/build/editor.css
   ```
2. **Gates and tests**: `$C quality`, `$P lint`, `node --test tests/`.
3. **Every image target**, the way CI builds them: `docker build --target production -t ik2-app:maint .` and `docker build --target cli -t ik2-cli:maint .`. This exercises the composer no-dev install, the mcp-adapter nested install, the pnpm frozen-lockfile install, and the drop-in steps.
4. **Running dev stack**. The `wp-html` named volume holds WordPress core, and Docker only seeds a named volume when it is empty, so a rebuilt image alone keeps serving the **old** core. Refresh it without touching the database or uploads:
   ```bash
   docker compose down                       # never `down -v`: that wipes db-data and uploads
   docker volume rm ik2org_wp-html           # core + wp-config only, regenerated from the image
   $C install                                # host wp-content/plugins is bind-mounted into the stack
   docker compose up -d --build
   ```
   Then:
   ```bash
   $WP core version                          # matches track 1
   $WP core update-db
   $WP plugin list --fields=name,status,version,update
   $WP eval 'echo PHP_VERSION;'
   curl -s -o /dev/null -w '%{http_code}\n' http://localhost:8080/          # 200
   curl -s -o /dev/null -w '%{http_code}\n' http://localhost:8080/wp-login.php
   docker compose exec -T app sh -c 'tail -n 100 /var/www/app/wp-content/debug.log 2>/dev/null'
   ```
   A plugin that was active before and is now inactive or missing is a failure. Any new fatal, warning, or deprecation in `debug.log` that points at our code (`themes/ik2`, `plugins/ik2`, `mu-plugins`) is a failure. Deprecations from third-party plugins are reported, not fixed.
5. Load the front page, one article, the projects archive, and the block editor in the browser (use the `agent-browser` skill) and check the console for new errors. The editor canvas is a blob iframe that screenshots blank; check it through `wp.data` selects on the main window instead.

## 6. Fix loop

For each failure from step 5:

1. Compare with the baseline from step 0. Already failing on `main`: report, don't fix.
2. Find the bump that caused it (changelog, stack trace, or `git stash` / revert one track and rerun).
3. Fix our code to the new API. Follow the repo rules while fixing: tokens instead of hardcoded values, no `!important`, named callables instead of closures in PHP, no new `phpcs:disable` without checking `phpcs.xml.dist`.
4. Re-run the failing gate, then the whole of step 5 once everything is green.
5. If the fix is large, touches design decisions, or the upstream break looks like a bug that will be patched, **hold that one package back** to its previous major (restore the old constraint, `update` it back) instead of forcing it through. Never hold a package back silently.

Fixes go in the commit of the bump that required them, so every commit passes the gates on its own. If a fix was only found after committing, fixup that track's commit before pushing in step 7. After the PR is open, add new fixes as normal commits instead of rewriting history.

## 7. Open the pull request

Only open the PR once every check in step 5 is green, or every remaining failure is a baseline failure or a documented hold-back. If something is still broken and unexplained, stop and report instead.

### Use the repo's PR template

The template is `.github/pull_request_template.md`. Read it fresh each run, since it may have changed since this skill was written.

If it's missing (deleted, or this skill was copied into another repo), check `.github/PULL_REQUEST_TEMPLATE.md`, `.github/PULL_REQUEST_TEMPLATE/`, and `docs/pull_request_template.md` first. If none exist, create `.github/pull_request_template.md` with these sections: Summary (with `Closes #`), Type of change (checkboxes), Changes, How to test, Screenshots, Risk and rollback, and a Checklist of this repo's gates and design rules from `CLAUDE.md`. Commit it on its own as `docs(github): add pull request template` before the PR is created.

Fill the template like this. Keep every heading and checkbox in order and never delete a section; write "N/A" plus a few words when one doesn't apply. Tick a box only when this run actually did the thing.

- **Summary**: two or three sentences on what moved and whether anything was held back. Remove the `Closes #` line if there's no issue.
- **Type of change**: tick "Dependency or infrastructure update", plus "Bug fix" if code was fixed for a breaking change.
- **Changes**: the tables below, in this order.
- **How to test**: the step 5 commands a reviewer needs, including the `wp-html` volume refresh (without it they'll see the old core).
- **Screenshots**: "N/A" unless a fix changed something visible.
- **Risk and rollback**: majors that went in, the core bump (prod images get rebuilt on merge), whether `wp core update-db` is needed after deploy, and "revert the merge and redeploy" as the rollback.
- **Checklist**: tick from the step 5 results.

Tables for the Changes section:

```markdown
### WordPress core
| Component | From | To | Major |
|---|---|---|---|

### WordPress plugins
| Plugin | From | To | Major | Notes |
|---|---|---|---|---|

### Composer packages
| Package | From | To | Major | Notes |
|---|---|---|---|---|

### Node packages
| Package | From | To | Major | Notes |
|---|---|---|---|---|

### Breaking changes reviewed
| Package | Change | Impact on this repo | Fix |
|---|---|---|---|

### Held back
| Package | Stayed on | Latest | Reason | Unblocked by |
|---|---|---|---|---|

### Verification
| Check | Result |
|---|---|
| `pnpm build` + output checks | |
| `composer quality` | |
| `pnpm lint` | |
| `node --test tests/` | |
| `docker build --target production` | |
| `docker build --target cli` | |
| Dev stack: core version, `update-db`, plugin list | |
| Front page / wp-login HTTP status | |
| `debug.log` (our code) | |
| Browser console: front page, article, projects, editor | |

### Pre-existing failures on main
<baseline failures from step 0, or "None">
```

### Table rules

- One row per package that changed. Packages already on latest are left out; add a one-line "Already current: a, b, c" under the table instead.
- Versions come from the lock files and `Dockerfile`, not from constraints: compare the step 0 snapshot against `$C show --direct` and `$P list --depth 0` after the update.
- "Major" is `yes` for a major bump, or a minor bump on a `0.x` package. Link the changelog in Notes for every `yes` row.
- The `@wordpress/*` set can go in one row (`@wordpress/* (8 packages)`) when they all moved to the same release line; list them individually if they didn't.
- Drop the Breaking changes and Held back tables if they would be empty, and say "None" under the heading instead.

### Create it

1. `git push -u origin <branch>`.
2. Write the body to the scratchpad (`pr-body.md`) and create the PR:
   ```bash
   gh pr create --base main --title "chore(deps): dependency maintenance YYYY-MM-DD" --body-file <scratchpad>/pr-body.md
   ```
   Title follows Conventional Commits, lowercase, no trailing period.
3. Watch the Quality and Build workflows on the PR (`gh pr checks --watch`). If CI fails on something the local run didn't catch, fix it on the branch, push, and update the Verification table in the PR body with `gh pr edit --body-file`.
4. Don't merge. Hand the PR link back to the user with a two-line summary: how many packages moved, and anything held back.

Remove the `ik2-app:maint` / `ik2-cli:maint` images when done (`docker rmi`).
