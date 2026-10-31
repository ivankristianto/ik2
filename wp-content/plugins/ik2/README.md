# ik2 plugin

Site-specific code for ivankristianto.com: post types, blocks, WP-CLI
commands and the Corpus. This README covers running the Corpus. The design is
in `docs/specs/2026-10-30-embedding-corpus-design.md`, the worker's own notes
in `services/embedder/README.md`.

## Corpus

The plugin cleans every published Article, splits it into Chunks and queues
it (`inc/corpus/`). The `ik2-embedder` worker (`services/embedder/`) embeds
whatever is queued with nomic-embed-text, ranks related Articles, writes the
results to the `wp_ik2_corpus_*` tables, and exits. "Keep reading" on single
posts reads those results through `IK2\Plugin\Corpus\get_related()` and falls
back to the newest posts in the category when an Article has none yet.

Saving, unpublishing, changing noindex or editing an Article's tags queues it.
Nothing is embedded during a page view.

### Commands

```bash
wp ik2 corpus status              # Documents by status, parked rows, last Run
wp ik2 corpus preview <id>        # the text and Chunks an Article would send
wp ik2 corpus enqueue <id>...     # queue Articles by hand
wp ik2 corpus enqueue --all       # queue every published Article
wp ik2 corpus enqueue <id> --force  # re-queue even if unchanged, retries a parked row
wp ik2 corpus related on          # Keep reading uses the Corpus picks
wp ik2 corpus related off         # back to category picks (the default)
```

A Document is parked after 3 failed embeds. `status` lists it with its error.

The Corpus picks stay off until `related on`, and `related off` is the kill
switch. Off, Keep reading shows the newest posts from the Article's first
category, as it did before the Corpus. The setting lives in the database, so no
redeploy is needed. Saves still queue and Runs still run while it is off, so
`related on` takes effect at once. With WP Super Cache on, cached pages keep
their list for up to an hour; Settings → WP Super Cache → Delete Cache clears
them now. The cache sits in the `app` container, so the command can't clear it.

### Local

```bash
docker compose exec -T wp-cli wp ik2 corpus enqueue --all
composer dev:corpus
```

`composer dev:corpus` applies the local `ik2_corpus` grants
(`docker/corpus/grant.sh`, safe to repeat) and runs the worker under the
`corpus` compose profile. A full backfill of 490 Articles takes about 3
minutes and peaks around 600 MB. A run with nothing queued exits in about 2
seconds.

### Production

The prod compose (`docker-compose.prod.yml`) runs an `embedder` service next
to `wp-cli`. It idles on `tail -f /dev/null` at about 1 MB, and an hourly
Dokploy Compose schedule runs a Run inside it with `docker exec`. The model
loads only during a Run, under the service's 1 GB `mem_limit`. It uses the
same `IMAGE_TAG` as the app, so the worker and the plugin's tables always come
from the same release, and Dokploy pulls it with its own registry login.

### Production setup

Do this once. Step 1 happens before the release, the rest after it.

**1. Pick the worker's password and give it to Dokploy.** Generate it with
`openssl rand -base64 24 | tr -d '/+='` and add it to the Dokploy environment
as `CORPUS_DB_PASSWORD`. The service takes its host, database name and table
prefix from the `WORDPRESS_DB_*` and `WORDPRESS_TABLE_PREFIX` values already
there, and `CORPUS_DB_USER` defaults to `ik2_corpus`.

**2. Release.** `pnpm release minor`. The deploy waits for the embedder image,
starts the `embedder` service, and creates the tables on the first request.
Check them from the Dokploy host:

```bash
CLI=$(docker ps --format '{{.Names}}' | grep wp-cli | head -1)
EMB=$(docker ps --format '{{.Names}}' | grep embedder | head -1)
docker exec "$CLI" wp ik2 corpus status
```

**3. Create the worker's MySQL user** with the password from step 1, on the
MariaDB service as root (the Dokploy database terminal, or
`docker exec -it <db container> mariadb -uroot -p`). The grants need the
tables, so this comes after the release.

```sql
USE <WORDPRESS_DB_NAME>;
CREATE USER IF NOT EXISTS 'ik2_corpus'@'%' IDENTIFIED BY '<CORPUS_DB_PASSWORD>';
GRANT SELECT, UPDATE, DELETE ON wp_ik2_corpus_documents TO 'ik2_corpus'@'%';
GRANT SELECT, DELETE ON wp_ik2_corpus_chunks TO 'ik2_corpus'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON wp_ik2_corpus_vectors TO 'ik2_corpus'@'%';
GRANT SELECT, INSERT, DELETE ON wp_ik2_corpus_related TO 'ik2_corpus'@'%';
GRANT SELECT, INSERT ON wp_ik2_corpus_runs TO 'ik2_corpus'@'%';
```

Change the table names if `WORDPRESS_TABLE_PREFIX` isn't `wp_`. The worker
can't touch any other table. `'%'` is fine while the database port stays off
the public interface.

**4. Queue every Article.**

```bash
docker exec "$CLI" wp ik2 corpus enqueue --all
```

**5. Run the worker once by hand.**

```bash
docker exec "$EMB" node src/run.mjs
```

It ends with one summary line, for example
`ik2-embedder: run 1, 490 documents and 777 chunks embedded, 0 removed, 0 failed, ... peak rss 605 MB`.
Watch `docker stats "$EMB"` in a second shell the first time, then check
`docker exec "$CLI" wp ik2 corpus status`.

**6. Turn the picks on.** Until now Keep reading has shown category picks.

```bash
docker exec "$CLI" wp ik2 corpus related on
```

Open an Article: "Keep reading" shows the new picks once its cached page
expires.

**7. Schedule it hourly.** In Dokploy, add a Schedule Job of type **Compose**
on this compose app, service `embedder`, cron `0 * * * *`, command
`node src/run.mjs`. An hour with nothing queued logs `ik2-embedder: no work`
and exits in under a second.

### Upgrades

Every release redeploys the `embedder` service at the new `IMAGE_TAG`, after
its image is built (the deploy job waits for it). Rolling back `IMAGE_TAG`
rolls the worker back with the app. A Run already going during a redeploy is
stopped with its container; nothing is half-written, because a Run commits its
results in one transaction, and the next hour redoes the work.

### When something looks wrong

- **Keep reading still shows category picks:** `related` is off (the default),
  the Article has no Run yet, or the cached page predates the Run. Check `wp ik2 corpus status`.
- **The picks look wrong:** `wp ik2 corpus related off` while you look into it.
- **A row is parked:** read its error in `status`, fix the cause, then
  `wp ik2 corpus enqueue <id> --force`.
- **Run exits 1:** the database was unreachable or the model failed to load.
  Check `CORPUS_DB_PASSWORD` in Dokploy matches the MySQL user, and that the
  service is on `dokploy-network`.
- **"another Run holds the lock, exiting":** a previous run is still going. The new one
  exits 0 and the next hour catches up.
