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

## `wp ik2 fix-image-ids`

Points inline images back at their real attachments. The legacy migration (see `MIGRATE.md`) rewrote image URLs but kept the old site's attachment IDs in the markup, so a post can say `wp-image-2921` when the file is attachment 1012. WordPress looks images up by that ID, so a stale one renders with no `width`, `height`, `srcset`, or `loading="lazy"`.

```bash
wp ik2 fix-image-ids --dry-run               # report only
wp ik2 fix-image-ids --dry-run --format=csv  # same, for a spreadsheet
wp ik2 fix-image-ids --post=503,596          # only these posts
wp ik2 fix-image-ids                         # fix everything
```

| Flag                  | Effect                                                     |
| :-------------------- | :--------------------------------------------------------- |
| `--dry-run`           | Report what would change; write nothing.                   |
| `--post=<ids>`        | Only these post IDs, comma-separated.                      |
| `--post-type=<types>` | Post types to scan, comma-separated. Default `post,page`.  |
| `--format=<format>`   | `table` (default), `csv`, `json`, or `yaml`. Only `table` adds the summary and hints, so the others pipe cleanly. |

For every `<img>` with a `wp-image-N` class, it finds the attachment the `src` file belongs to. A resized copy (`photo-300x200.jpg`) or a big upload stored as `photo-scaled.jpg` resolves to its original. When the ID differs, it rewrites:

- the `wp-image-N` class, and `data-id` on old-style gallery images
- the ID attribute of the block around the image: `id` on image and cover, `mediaId` on media-text, `ids` on old-style galleries, so the editor doesn't flag the block as invalid
- `id="attachment_N"` on the classic `[caption]` around the image

Each block or caption only takes IDs from the images inside it, so a video cover or a correct image elsewhere in the post is never touched. `data-link` stays as it was, because the old gallery rebuilds its link `href` from it and the two must match.

Rows come back with one of these statuses:

| Status       | Meaning                                                                                   |
| :----------- | :---------------------------------------------------------------------------------------- |
| `fixed`      | Rewritten (`would fix` on a dry run).                                                     |
| `unresolved` | The `src` matches no attachment: the migration never imported it. Re-run `migrate-articles`, then this command. |
| `conflict`   | One block holds two images with the same old ID but different real files. The block is left alone; fix it in the editor. |
| `failed`     | The database write failed. The command exits non-zero.                                    |

It writes `post_content` directly, not through `wp_update_post`, so `post_modified` doesn't change, no revision is created, and kses (which WP-CLI would apply, having no user) doesn't touch the rest of the body. Re-running is safe: a fixed image matches its `src` and is skipped.

Sample output (local run, trimmed):

```
post  old   new   status      src
492   2989  998   fixed       http://localhost:8080/wp-content/uploads/2026/06/Screen-Shot-2018-01-10-at-14.21.51.png
…
596   2048  0     unresolved  http://www.ivankristianto.local/images/2011/04/screenshot-oneclick.jpg
Scanned 5 post(s): 12 stale image(s), 9 unresolved, 0 conflict(s); 2 post(s) updated.
Delete the WP Super Cache page cache in wp-admin (or restart the app container), then purge the CDN.
Success: Done.
```

On production, run it in the `wp-cli` container: `wp db export` first, then the dry run, then the real run. The object cache is Redis, shared with the app, so it updates on its own. The WP Super Cache pages live on the app container, where the wp-cli container can't reach them: delete them from Settings → WP Super Cache (or restart the app container), then purge the CDN. Yoast's internal link index still holds the old IDs until `wp yoast index --reindex`; that's optional, since Yoast finds social images by URL.

## Where the code lives

```
wp-content/plugins/ik2/inc/cli/
├── namespace.php                      # registers all `wp ik2 <command>` commands
├── class-stats-command.php            # wp ik2 stats
├── class-setup-command.php            # wp ik2 setup — step registry + runner
├── class-fix-image-ids-command.php    # wp ik2 fix-image-ids
└── setup/
    ├── interface-setup-step.php       # Setup_Step contract
    ├── class-check-result.php         # Check_Result value object
    ├── class-pages-step.php
    ├── class-permalinks-step.php
    ├── class-timezone-step.php
    └── class-registration-step.php
```
