# Migrating legacy articles from www.ivankristianto.com

How to import published articles and their images from the old site into this
one, using the `wp ik2 migrate-articles` WP-CLI command.

The command is **idempotent**: re-run it until the summary reports `0 failed`.
Posts are matched by slug — already-imported posts are skipped, only failures
are retried. `--force` re-imports and overwrites.

> **Status:** the command is specified in
> `docs/superpowers/plans/2026-06-27-legacy-article-migration.md` and is built
> in the `ik2` plugin under `wp-content/plugins/ik2/inc/cli/`. If you have not
> implemented the plan yet, do that first.

---

## What you need

1. A **mysqldump** of the old database — at minimum the `posts`, `postmeta`,
   `terms`, `term_taxonomy`, and `term_relationships` tables.
2. A **copy of the old `wp-content/uploads/`** directory.
3. The old site's **table prefix** (look at the dump's table names, e.g.
   `wp_posts` → prefix `wp_`).

The migration reads the old data from a **separate database** and sideloads
images from a **local copy of the uploads folder** — it never touches the
live old site.

---

## 1. Stage the inputs

Put both inputs under the gitignored `./legacy/` directory (bind-mounted
read-only into the wp-cli container at `/legacy`):

```
./legacy/old-site.sql      # the mysqldump
./legacy/uploads/          # a copy of the old wp-content/uploads/
```

```bash
mkdir -p legacy/uploads
# copy your dump in:
cp /path/to/old-site.sql legacy/old-site.sql
# copy the old uploads in (rsync or unzip):
rsync -a /path/to/old/wp-content/uploads/ legacy/uploads/
```

Files must be world-readable — the wp-cli container runs as uid 82:

```bash
chmod -R a+rX legacy
```

Make sure the stack is running and the `wp-cli` container has the mount:

```bash
composer dev
docker compose up -d --force-recreate wp-cli   # only needed the first time, to pick up the ./legacy mount
composer dev:wp:cmd -- eval 'echo is_dir("/legacy/uploads") ? "MOUNT OK\n" : "MISSING\n";'
```

Expected: `MOUNT OK`.

---

## 2. Import the dump into a separate database

```bash
docker compose exec -T db mariadb -uroot -proot -e \
  "CREATE DATABASE IF NOT EXISTS legacy; \
   GRANT ALL ON legacy.* TO 'wordpress'@'%'; FLUSH PRIVILEGES;"

docker compose exec -T db sh -c 'mariadb -uroot -proot legacy < /legacy/old-site.sql'
```

(The db container does not mount `./legacy`; if the second command can't find
the file, pipe it from the host instead:
`docker compose exec -T db mariadb -uroot -proot legacy < legacy/old-site.sql`.)

---

## 3. Match the permalink structure (SEO)

Old URLs keep working only if this site's permalink structure matches the old
one. The command preserves slugs and dates but does **not** install redirects.
Set the structure before announcing the migration:

```bash
composer dev:wp:cmd -- option update permalink_structure '/%postname%/'
composer dev:wp:cmd -- rewrite flush
```

(Use whatever structure the old site used.)

---

## 4. Preview (dry run)

```bash
composer dev:wp:cmd -- ik2 migrate-articles --legacy-db=legacy --dry-run --verbose
```

Check the post count and per-post inline-image counts look right. Nothing is
written. Add `--legacy-prefix=<prefix>` if the old prefix is not `wp_`.

---

## 5. Run it

```bash
composer dev:wp:cmd -- ik2 migrate-articles --legacy-db=legacy
```

Re-run until the summary reports `0 failed`. Successful posts are skipped on
re-run; only failures are retried.

Useful flags:

| Flag                  | Effect                                                         |
| :-------------------- | :------------------------------------------------------------- |
| `--dry-run`           | Report what would happen; write nothing.                       |
| `--verbose`           | Log one line per post (status, media added, errors).           |
| `--force`             | Re-import and overwrite posts/media even when the slug exists. |
| `--limit=<n>`         | Import at most N posts (incremental testing).                  |
| `--post=<old_id>`     | Import only the single legacy post with that old ID.           |
| `--legacy-prefix=<p>` | Old table prefix (default `wp_`).                              |
| `--uploads-path=<p>`  | Local uploads copy (default `/legacy/uploads`).                |
| `--author=<id>`       | Target author (default: lowest-ID administrator).              |

Example — verify one post end to end first:

```bash
composer dev:wp:cmd -- ik2 migrate-articles --legacy-db=legacy --post=1234 --verbose
```

---

## 6. Spot-check

-   Open a few imported posts. Images should load from the new media library and
    no `www.ivankristianto.com` URLs should remain in the content.
-   Confirm categories, tags, post format, and the featured image are set.
-   Confirm Yoast title/description carried over (Yoast meta box on the post).

```bash
composer dev:wp:cmd -- post list --post_type=post --fields=ID,post_name,post_status,post_date
```

---

## 7. Tear down

The `./legacy` directory and the `legacy` database are dev-only. Remove them
once the migration is clean:

```bash
docker compose exec -T db mariadb -uroot -proot -e "DROP DATABASE legacy;"
rm -rf ./legacy
```

---

## How re-running stays safe

-   **Posts** are matched by slug (`post_name`). Existing slug → skipped (or
    overwritten with `--force`). No duplicates.
-   **Media** are matched by the original filename / `_ik2_legacy_src` stamp.
    Already-imported images are reused, not re-downloaded.
-   Each post is imported in isolation; one failure never blocks the others.
    Fix the cause, re-run, watch the failure count drop to zero.
