---
name: sync-from-prod
description: Pull content from production (www.ivankristianto.com) into the local Docker dev site over the WP REST API, authenticated with an application password. Syncs categories, tags, media files, posts, pages, projects, synced patterns, navigation, and reading settings (static front page), keeping production post IDs. Use when the user asks to "sync content from prod", "pull production content", "refresh local content", "get the live posts locally", or types /sync-from-prod.
user-invocable: true
---

# Sync production content down to local

`scripts/sync.sh` reads production over REST and writes into the local dev DB and uploads volume. It only sends GET requests to production. It refuses to run unless the local site is `WP_ENVIRONMENT_TYPE=development` and its host differs from production's.

## Credentials

`sync.sh` looks for these, in order:

1. `IK2_PROD_USER` and `IK2_PROD_APP_PASSWORD` in the shell environment or in `.env` (gitignored; see `.env.example`). `IK2_PROD_URL` defaults to `https://www.ivankristianto.com`.
2. Otherwise, `WP_API_USERNAME` / `WP_API_PASSWORD` from the `ik2` server in `.mcp.json` (gitignored), which is the same application password the MCP server uses.

The production user needs `edit_others_posts` (editor or admin) so drafts and raw block markup come back. Never print the password, commit it, or write it into a tracked file.

## Run it

The dev stack must be up (`composer dev`; if `docker info` fails, `open -a OrbStack` first).

1. **Dry run first, always.** It fetches everything and reports what would change, writing nothing:
   ```bash
   .claude/skills/sync-from-prod/scripts/sync.sh dry-run
   ```
   Show the user the summary table. Call out the `displaced` column: those are local rows that will be deleted, moved to a new ID, or renamed (see below). If the local DB holds work the user may want, offer a backup: `docker compose exec -T wp-cli wp db export - > <scratchpad>/local-backup.sql`.
2. **Real run**, once the user agrees. A full media pull is about 1,400 files, so send output to a log and read the tail:
   ```bash
   .claude/skills/sync-from-prod/scripts/sync.sh > <scratchpad>/sync.log 2>&1; tail -40 <scratchpad>/sync.log
   ```
3. If the summary reports failures, read the notes above the table, fix the cause, and re-run. Unchanged items are skipped, so re-runs are cheap.
4. Spot-check: open `http://localhost:8080/`, a recent article, and one image URL (`curl -o /dev/null -w '%{http_code}'`).

## Arguments

Bare words, not `--flags` (`wp eval-file` would claim those):

| Argument | Effect |
| :-- | :-- |
| `dry-run` | Fetch and compare only. Nothing is written or downloaded. |
| `only=<phase,...>` | Run some phases: `terms`, `media`, `posts`, `pages`, `projects`, `blocks`, `navigation`, `settings`. `posts` always syncs terms too, since it needs the term map. |
| `limit=N` | Take the first N items (by ID) of each post-type phase. For testing. Terms are never limited. |
| `force` | Rewrite items even when the production modified date matches, and re-download files that already exist. |
| `prune` | Delete local items of the synced types (and terms) that production doesn't have. Not allowed with `limit`. |

## How it maps content

- **Posts, pages, projects, media, patterns, navigation keep their production IDs**, so featured images, `wp-image-N` classes, and `"id":N` block attributes stay correct with no rewriting.
- **Terms match by slug** per taxonomy (term IDs aren't preserved). Category parents are kept.
- **Authors match by login**, falling back to the first local administrator. Users are never created.
- **Media files** download to the same `uploads/YYYY/MM/` path as production, with every generated size and the `-scaled` original, and the production attachment metadata is copied rather than regenerated. Files already on disk are not fetched again.
- **URLs**: the production home URL in content and excerpts (plain and JSON-escaped) becomes `http://localhost:8080`.
- **Skip check**: an item whose local `post_modified_gmt` equals production's is skipped. The script writes production's modified dates after each save so this holds.
- **Settings**: front page, posts page, posts per page, date/time format, week start, and timezone. The site title, tagline, URLs, and admin email stay local. The front page ID only resolves once `pages` has synced.
- **REST-registered meta** comes across (project `status`/`tech`/`links`/`learned`, `footnotes`). Meta without `show_in_rest`, including Yoast SEO fields, does not.

When a production ID is already taken locally by something else, the local row is displaced:

- a revision, an auto-draft, or a different synced type is deleted;
- anything else (nav menu items, `wp_global_styles`) moves to a new ID, with its meta, terms, and children;
- a local-only post holding the same slug is renamed to `<slug>-local-<id>` so the production post keeps its slug.

## Not synced

Comments, users, other site options, Customizer menus, template and template-part overrides from the Site Editor, global styles, and plugin data. Those come from the theme files or need a DB dump.

## Changing the script

`scripts/sync.php` runs inside the wp-cli container through `wp eval-file -` (stdin), so it has no namespace and uses `ik2_sync_` function prefixes. To test a change, back up the local DB, run with `limit=5`, check the result, then restore with `docker compose exec -T wp-cli wp db import - < backup.sql`. The uploads volume isn't restored by a DB import; stray files there are harmless and get reused by the next sync.
