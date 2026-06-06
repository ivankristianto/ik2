# CLI

Custom WP-CLI commands shipped by the **ik2 plugin** (`wp-content/plugins/ik2/inc/cli/`), all under the `wp ik2` namespace.

Run them through the dev stack:

```bash
composer dev:wp:cmd -- ik2 setup        # one-shot (note the `--`)
composer dev:wp                          # or shell in, then `wp ik2 setup`
```

## `wp ik2 setup`

Provisions the site to match the theme's expectations. Idempotent — safe to run repeatedly; it only changes what differs and reports everything as a ✓/✗ checklist.

```bash
wp ik2 setup            # skip pages that already exist
wp ik2 setup --force    # also re-apply title/slug/status on existing pages
```

What it does, in order:

| Step         | Action                                                                                                                                       |
| :----------- | :------------------------------------------------------------------------------------------------------------------------------------------ |
| Pages        | Creates the pages the theme templates hardcode links to: `articles`, `projects`, `speaking`, `about`, `contact`, `resume`, `privacy`. Created pages are published with empty content (the block templates render the layout). Existing pages are skipped — any status — unless `--force`. |
| Permalinks   | Sets the permalink structure to `/%postname%/` and flushes rewrite rules.                                                                     |
| Timezone     | Sets the site timezone to `Asia/Jakarta` (and clears any manual GMT offset).                                                                  |
| Registration | Turns off open user registration (`users_can_register = 0`).                                                                                  |

`--force` only affects the Pages step: it re-applies title, slug, and published status **in place** on the existing page ID, so menus and internal links keep working. It never deletes or recreates pages, and it does overwrite a customized page title with the manifest title.

Sample output:

```
Pages
  ✓ articles — exists, skipped
  ✓ privacy — created
Permalinks
  ✓ /%postname%/ — already set
Timezone
  ✓ Asia/Jakarta — already set
Registration
  ✓ users_can_register — already off
Success: Setup complete: 10 ok, 0 failed.
```

Failed checks print as `✗` lines but don't abort the run; the command finishes all steps, then exits non-zero if anything failed.

### Adding a setup step

Steps are small classes implementing `Setup_Step` (`label(): string`, `run( bool $force ): Check_Result[]`) in `wp-content/plugins/ik2/inc/cli/setup/`. To add one:

1. Create `class-<name>-step.php` in `inc/cli/setup/` implementing `IK2\Plugin\CLI\Setup\Setup_Step`. Catch your own failures and return them as failed `Check_Result`s — never let an error escape `run()`.
2. Add a `require_once` for it in `inc/cli/namespace.php` (after the interface and `Check_Result` requires).
3. Append an instance to the registry in `Setup_Command::steps()` (`inc/cli/class-setup-command.php`).

Keep steps idempotent: a re-run without `--force` must not change state it reports as already correct.

## `wp ik2 stats`

Snapshot of site health: published posts/pages, tag and category counts, Redis object cache status, active plugin count, upcoming cron events, and database size.

```bash
wp ik2 stats                  # table (default)
wp ik2 stats --format=json    # also: csv, yaml
```

## Where the code lives

```
wp-content/plugins/ik2/inc/cli/
├── namespace.php                      # registers all `wp ik2 <command>` commands
├── class-stats-command.php            # wp ik2 stats
├── class-setup-command.php            # wp ik2 setup — step registry + runner
└── setup/
    ├── interface-setup-step.php       # Setup_Step contract
    ├── class-check-result.php         # Check_Result value object
    ├── class-pages-step.php
    ├── class-permalinks-step.php
    ├── class-timezone-step.php
    └── class-registration-step.php
```
