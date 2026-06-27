# Legacy Article Migration — Design

**Date:** 2026-06-27
**Status:** Approved (brainstorming)
**Topic:** Migrate articles and their images from the old www.ivankristianto.com WordPress site into the current ik2 site, via a re-runnable WP-CLI command.

## Problem

The new ik2 site is nearly complete but empty of historical content. The old site holds years of articles the user wrote, with inline and featured images. We need to bring the articles over, sideload every referenced image into the new media library, and rewrite content to point at the new media — all through a command the user can run repeatedly until the migration reports zero failures.

## Goals

- Import all articles (`post_type=post`) from the old site into the new site.
- Sideload every featured and inline image into the new media library from a local copy of the old uploads folder.
- Rewrite article content so old upload URLs point at the new attachments.
- Preserve slugs, dates, categories, tags, post formats, and Yoast SEO meta.
- Be idempotent: re-running updates in place, never duplicates, and skips work already done.
- End each run with a summary that surfaces failures, so the user can re-run until clean.

## Non-Goals

- Migrating projects, speaking entries, pages, menus, or widgets.
- Migrating comments.
- Installing URL redirect rules (Yoast free has no redirect manager). Old URLs continue to work only if the new permalink structure matches the old one; matching that setting is a manual step, flagged in the runbook.
- Migrating users beyond assigning imported posts to one target author.

## Decisions (from brainstorming)

| Question | Decision |
| :-- | :-- |
| Data source | Import the old MySQL dump into a **separate legacy database** on the same MySQL server; read it directly via a second `wpdb` connection. |
| Which posts | Solo site — **all** `post_type=post`. Status: **published only**. |
| Image source | A **local copy of the old `wp-content/uploads/`** mounted into the container. |
| What to carry over | Slugs + dates, categories + tags, post formats, Yoast SEO meta. |
| Target author | All imported posts assigned to the current site's user (default: `ik2` admin / user ID 1). |
| Comments | Skipped. |

## Architecture

### Packaging

A self-contained WP-CLI command, **not** part of the deployed app image. It lives in the repo under `scripts/migration/` and is loaded on demand with `--require`:

```bash
composer dev:wp:cmd -- --require=scripts/migration/cli.php ik2 migrate-articles --dry-run
```

Rationale: the migration is a one-off operation that should not ship in the theme, mu-plugins, or production runtime, but should be version-controlled and easy to iterate on. Loading via `--require` keeps it out of normal WordPress bootstrap while making it runnable inside the existing wp-cli container.

Files:

- `scripts/migration/cli.php` — registers the `wp ik2 migrate-articles` command and wires dependencies.
- A command class (e.g. `IK2\Migration\Migrate_Articles_Command`) exposing the command callback.
- Supporting classes for the discrete units below, each with one responsibility:
  - **Legacy DB reader** — opens the second `wpdb`, fetches posts, meta, terms.
  - **Post upserter** — matches/inserts/updates a post on the new site.
  - **Media sideloader** — resolves an old URL to a local file, sideloads it, returns the new attachment + URL.
  - **Content rewriter** — replaces old upload URLs in content using the sideloader's map.
  - **Reporter** — accumulates per-post outcomes and prints the run summary.

Functions and callbacks are named (per project PHP conventions), not closures.

### Legacy database access

The command opens a second connection with `new wpdb( $user, $pass, $legacy_db, $host )` pointed at the imported legacy database, configured via CLI flags or environment. All reads of old data go through this connection; all writes go through the normal site APIs (`wp_insert_post`, `wp_set_object_terms`, `media_handle_sideload`, `update_post_meta`). The legacy DB is read-only from the script's perspective.

The old **table prefix** is a required input (often `wp_`, but may be randomized).

### Per-article pipeline

For each legacy published post, ordered by ID:

1. **Select** the post row and its postmeta from the legacy DB.
2. **Upsert** — look up an existing new-site post by `_ik2_legacy_id == <old ID>`. Insert if absent, otherwise update in place. Set title, content (pre-rewrite), excerpt, slug (`post_name`), publish date, modified date, status, and author.
3. **Featured image** — resolve the old thumbnail to a local file, sideload it (or reuse if already imported), set as `_thumbnail_id`.
4. **Inline images** — scan `post_content` for old upload URLs, sideload each (or reuse), building an old-URL → new-URL map. Resized variants (e.g. `-1024x768.jpg`) resolve back to the originally-uploaded file so WordPress regenerates its own sizes.
5. **Rewrite** `post_content` with the URL map and save the updated content.
6. **Terms** — recreate categories and tags (by slug) and assign them; set the post format.
7. **Yoast meta** — copy `_yoast_wpseo_*` rows from legacy postmeta onto the new post.
8. **Stamp** `_ik2_legacy_id` on the post and record the outcome with the reporter.

## Idempotency

The "run until fixed" requirement drives every matching decision:

- **Posts** are keyed by the `_ik2_legacy_id` post meta. Re-runs update; they never duplicate.
- **Attachments** are stamped with `_ik2_legacy_src` (the old URL or path). Before sideloading, the script checks for an attachment with that stamp and reuses it, so re-runs do not re-download or duplicate media.
- **Terms** are matched by slug via `wp_insert_term` / existing-term lookup, which is naturally idempotent.
- A failed post leaves prior successful posts untouched; the next run retries only what failed.

### CLI flags

| Flag | Effect |
| :-- | :-- |
| `--dry-run` | Report what would happen; write nothing. |
| `--limit=N` | Process at most N posts (for incremental testing). |
| `--post=<old_id>` | Process a single legacy post by its old ID. |
| `--force-media` | Re-sideload media even if a stamped attachment exists. |
| `--reset` | Delete previously-imported posts and media (by their stamps) for a clean redo. |
| `--legacy-db=` `--legacy-prefix=` `--uploads-path=` `--author=` | Connection, prefix, local uploads location, and target author overrides. |

### Run summary

Every run prints: created, updated, skipped, media added, and **failures with reasons** (post ID + error). The user re-runs until the failure count is zero.

## Data flow

```
legacy MySQL DB ──(2nd wpdb, read-only)──> command
local uploads copy ──(filesystem)─────────> media sideloader
                                              │
                  per post: upsert ──> sideload featured ──> sideload inline
                            ──> rewrite content ──> terms + format ──> Yoast meta
                            ──> stamp _ik2_legacy_id ──> reporter
                                              │
                                              └─> new-site DB + media library
```

## Error handling

- Each post is processed in isolation; an exception on one post is caught, logged to the failure list, and processing continues.
- A missing local image file is a per-image failure (recorded, post still imported with remaining images); it is not fatal.
- Legacy DB connection failure or missing required inputs (prefix, uploads path) abort before any writes, with a clear message.
- `--dry-run` exercises selection, matching, and resolution without writes, so the user can preview safely.

## Testing strategy

- **Dry run first** on the full set to validate selection counts and surface unresolved images before any writes.
- **`--limit=1` / `--post=<id>`** to verify a single article end to end: body, featured image, inline images, slug, date, terms, format, Yoast meta.
- **Re-run** the same scope to confirm idempotency: second run reports updates/skips, adds no duplicate posts or media.
- **`--reset` then full run** to confirm a clean redo path.
- Spot-check rewritten content URLs resolve to new attachments, and that no old-domain URLs remain.

## Inputs required from the user

1. MySQL dump of the old site (at minimum `posts`, `postmeta`, `terms`, `term_taxonomy`, `term_relationships`), imported into a separate legacy database.
2. A copy of the old `wp-content/uploads/` folder, mounted into the wp-cli container.
3. The old site's table prefix.
4. Confirmation of assumptions: published-only, target author, comments skipped.

## Open questions / risks

- **Permalink structure** on the new site must match the old one for URLs to survive; this is a manual settings step, not handled by the script.
- **Yoast meta keys** may differ between the old Yoast version and current; the copy step should pass through known `_yoast_wpseo_*` keys and log unknowns rather than fail.
- **Shortcodes / legacy blocks** in old content are migrated verbatim; rendering parity is out of scope for this pass and can be addressed separately if needed.
