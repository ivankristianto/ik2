# Embedding corpus

## Goal

Build one place where published Articles are cleaned, split, embedded and
ranked, so AI features read from the same prepared text instead of each
scraping posts their own way. The first consumer is the related-posts block
under every Article ("Keep reading"). A chatbot and a summarizer are expected
later and get their own specs.

This spec replaces item 2.8 (Related posts) in
`docs/specs/2026-10-30-seo-aeo-geo-improvements-design.md`.

## Why

Today's block shows the 3 newest posts from the Article's first category.
Misc holds 199 posts and Tooling 173, so most Articles get the same three
recent posts, whatever they are about. "Upgraded to Ubuntu 15.04",
"Google+ Review and Facebook Comparison" and "ModSecurity Error" all show
"Why You Should Always Use Site with HTTPS", "Ubuntu 16.04 and Android
Lollipop" and "Everybody Loves Progress".

A prototype over all 490 local Articles (nomic-embed-text vectors fused with
BM25) gave clearly better picks:

| Article | Today | Prototype |
| --- | --- | --- |
| Secure Your WordPress Site | LiteSpeed webinar, AMP talk, WordCamp recap | Simple Way to Secure Your WordPress, Secure Your WordPress Blog, What To Do When Your WordPress Blog Got Hacked |
| Radio Streaming Server in Ubuntu Using IceCast2 | Cloudflare API CLI Tool, OpenVPN, Docker | Broadcast Radio With Winamp + SHOUTcast to Ubuntu with IceCast2, Zeya: Music Server With HTML 5, The Best Media Player In Ubuntu |
| Use And Tweak timthumb Script | LiteSpeed webinar, Secure Your WordPress Site, AMP talk | TimThumb Zero Day Vulnerability, Facebook Thumbnail Image, Create a Maintenance Page in WordPress |

Some third picks were weak (the ModSecurity Article got "Free Web Proxy
Server To Bypass Access Filter"), which is why the block applies a score
floor and keeps a fallback.

Measured on a MacBook with transformers.js and the 8-bit nomic model: 145 MB
model, about 75 ms per Article one at a time, 575 MB resident memory.
Batching 8 at a time raised memory to 1.6 GB, so the worker embeds one input
at a time.

## Language

**Corpus**: the published Articles, cleaned, chunked and embedded, that AI
features read from. Added to `GLOSSARY.md`.

**Document**: one Article in the Corpus. Holds the cleaned text and the
text used for its Article-level vector.

**Chunk**: one section of a Document, split on headings, sized for
retrieval.

**Run**: one pass of the worker that found work and changed the Corpus.

## Shape

```
save / term change / unpublish / wp ik2 corpus enqueue
        │
        ▼
PHP (ik2 plugin)  ── clean, chunk, hash ──▶  documents + chunks (status = queued | remove)
                                                   │
          hourly Dokploy Server schedule           │
          docker run --rm ik2-embedder  ◀──────────┘
                    │
                    ├─ embed changed Documents and Chunks ──▶ vectors
                    ├─ BM25 + cosine, fused with RRF ───────▶ related (top 8 per Article)
                    └─ record the Run ──────────────────────▶ runs
                                                   │
single Article page ── get_related() ◀─────────────┘  (cached under the last Run ID)
```

Everything that knows WordPress (blocks, terms, Yoast meta, post status) stays
in PHP. The worker reads plain text from the Corpus tables, embeds it, ranks
it and writes results. It never touches `wp_posts` or `wp_postmeta`.

## Scope

In scope:

- Corpus tables, owned and migrated by the ik2 plugin.
- PHP cleaning, chunking, hashing and queueing for Articles (`post` type).
- The worker image `ghcr.io/ivankristianto/ik2-embedder`, its hourly
  schedule and its own MySQL user.
- Related posts: ranking in the worker, reading and rendering in PHP and the
  theme, in the "Next read" layout (option B).
- `wp ik2 corpus enqueue`, `status` and `preview`.
- PHPUnit for the PHP cleaner and chunker, `node --test` for the worker.

Out of scope, each for a later spec:

- Embedding a reader's question at request time (needed by a chatbot), and
  any long-running embed service.
- A stored BM25 term index for request-time search.
- Pages, projects and talks. The tables carry `object_type` so adding them
  needs no schema change.
- Alerting on failed runs.

## Data model

All tables use `$wpdb->prefix`. The plugin creates and upgrades them with
`dbDelta` from a version check on `plugins_loaded` that compares the
`ik2_corpus_db_version` option with a `CORPUS_DB_VERSION` constant, on every
request and under WP-CLI. The activation hook is not relied on: the plugin is
already active in production and a redeploy never fires it. The worker never
runs DDL.

### `{prefix}ik2_corpus_documents`

| Column | Type | Notes |
| --- | --- | --- |
| `id` | bigint unsigned PK | |
| `object_type` | varchar(20) | `post` for now |
| `object_id` | bigint unsigned | post ID |
| `title` | text | |
| `url` | varchar(500) | permalink at enqueue time |
| `embed_text` | mediumtext | text for the Article-level vector, see below |
| `embed_hash` | char(64) | sha256 of `embed_text` alone, what the Document vector is checked against |
| `content_hash` | char(64) | sha256 of the title, URL, `embed_text` and every Chunk, used to skip no-op saves. A URL-only change re-queues the Document; the worker then marks it `indexed` without embedding, since `embed_hash` is unchanged |
| `status` | varchar(16) | `queued`, `indexed`, `remove`, `parked` |
| `attempts` | tinyint unsigned | reset to 0 on enqueue and on a successful embed |
| `last_error` | text | last worker error, cleared on success |
| `queued_at` | datetime | |
| `indexed_at` | datetime null | |

Unique key on (`object_type`, `object_id`), key on `status`.

### `{prefix}ik2_corpus_chunks`

| Column | Type | Notes |
| --- | --- | --- |
| `id` | bigint unsigned PK | |
| `document_id` | bigint unsigned | |
| `chunk_index` | smallint unsigned | order within the Article |
| `heading` | text | section heading, empty for the opening section |
| `anchor` | varchar(200) | heading `id` when the block has one, else empty |
| `text` | mediumtext | `Article title > Section heading` then the section text |
| `token_estimate` | smallint unsigned | words × 1.3 |
| `content_hash` | char(64) | sha256 of `text` |

Unique key on (`document_id`, `chunk_index`).

### `{prefix}ik2_corpus_vectors`

| Column | Type | Notes |
| --- | --- | --- |
| `id` | bigint unsigned PK | |
| `level` | varchar(10) | `document` or `chunk` |
| `owner_id` | bigint unsigned | document or chunk ID |
| `model` | varchar(100) | e.g. `nomic-ai/nomic-embed-text-v1.5:q8` |
| `prefix` | varchar(32) | `clustering:` or `search_document:` |
| `dims` | smallint unsigned | 768 |
| `embedding` | blob | packed little-endian float32, unit length (`vector` is reserved in MariaDB 11.7+) |
| `content_hash` | char(64) | hash of the text that produced it |
| `embedded_at` | datetime | |

Unique key on (`level`, `owner_id`). A vector is stale when its `model`
differs from the worker's, or its `content_hash` differs from its owner's:
the Document's `embed_hash` for `document` vectors, the Chunk's
`content_hash` for `chunk` vectors. A vector whose owner no longer exists is
an orphan and gets deleted.

### `{prefix}ik2_corpus_related`

| Column | Type | Notes |
| --- | --- | --- |
| `post_id` | bigint unsigned | |
| `position` | tinyint unsigned | 1 to 8, by fused rank (`rank` is reserved in MySQL 8) |
| `related_id` | bigint unsigned | |
| `score` | float | cosine similarity of the two Article vectors |
| `run_id` | bigint unsigned | |

Primary key (`post_id`, `position`).

### `{prefix}ik2_corpus_runs`

| Column | Type | Notes |
| --- | --- | --- |
| `id` | bigint unsigned PK | |
| `started_at`, `finished_at` | datetime | |
| `model` | varchar(100) | |
| `documents_embedded`, `chunks_embedded`, `removed`, `failed` | int unsigned | |

A row is written only when a Run changed something. A check that finds no
work logs a line and exits without a row.

## PHP: `wp-content/plugins/ik2/inc/corpus/`

Namespace `IK2\Plugin\Corpus`, booted from `IK2\Plugin\bootstrap()`. Hook
callbacks are named functions, per the repo's PHP conventions.

### What goes in

An Article is in the Corpus when all of these hold:

- `post_type` is `post` and `post_status` is `publish`.
- It has no password.
- Yoast's `_yoast_wpseo_meta-robots-noindex` is not `1`.
- `_ik2_corpus_exclude` is empty.

When an indexed Article stops meeting these, its Document goes to `remove`.

### Cleaning (`parse_blocks()` walk)

- `core/heading` at level 2 or 3 starts a new section. Level 4 and below is
  kept as text in the current section.
- Text is kept from `core/paragraph`, `core/list` and `core/list-item`,
  `core/quote`, `core/pullquote`, `core/verse`, `core/preformatted`,
  `core/table` (cell text) and `core/code`.
- A single code block over 200 tokens is cut at 200 with a `…` marker.
- Dropped: `core/image`, `core/gallery`, `core/cover`, `core/embed`,
  `core/video`, `core/audio`, `core/file`, `core/buttons`, `core/button`,
  `core/spacer`, `core/separator`, `core/html`.
- Container blocks (`core/group`, `core/columns`, `core/column` and the like)
  are walked into.
- Any other block is rendered with `render_block()` and kept if it yields
  text after `wp_strip_all_tags()`.
- Freeform content (a `null` block name) is treated as HTML and split on
  `<h2>` and `<h3>`.
- Shortcodes are stripped, entities decoded, whitespace collapsed.

### Document text

`embed_text` is:

```
{title}
Category: {category names}
Tags: {tag names}

{first ~800 words of the cleaned body}
```

### Chunking

- Sections come from the cleaning step.
- Target 300 to 400 tokens, hard cap 512 (estimated as words × 1.3). A
  section over the cap splits on paragraph boundaries.
- A section under 80 tokens merges into the next one. The last one merges
  into the previous.
- Chunk text starts with `{title} > {heading}`, or `{title}` for the opening
  section.

The worker enforces the real 512-token cap with nomic's tokenizer, since the
PHP estimate can be off.

### Queueing

Every trigger calls one `sync( int $post_id )`, which applies the inclusion
rules and either calls `enqueue()` or marks the Document `remove`, so the
hooks that fire together on one save can't disagree.

`enqueue( int $post_id, bool $force = false )` builds the Document and its
Chunks, hashes them, and compares `content_hash` with the stored one. Same
hash and status `queued` or `indexed`, without `$force`: nothing happens.
Otherwise (new hash, a `remove` or `parked` row, or `$force`): it upserts the Chunks on (`document_id`, `chunk_index`), deletes
only the rows past the new last index, and writes the Document with
`status = queued`, `attempts = 0` and the current time. Upserting keeps Chunk
IDs stable, so the worker re-embeds only the Chunks whose text changed.

Triggers:

| Event | Action |
| --- | --- |
| `wp_after_insert_post` for a `post` (not autosave or revision) | `enqueue` |
| `transition_post_status` away from `publish` | mark `remove` |
| `deleted_post` | mark `remove` |
| `updated_post_meta` / `added_post_meta` / `deleted_post_meta` for the noindex or exclude keys | re-check inclusion, then `enqueue` or mark `remove` |

`wp_after_insert_post` fires once per save, after terms and meta are stored,
for both block editor (REST) and classic saves, so one save cleans the
Article once. Term changes made in the editor arrive through it too.

### WP-CLI

- `wp ik2 corpus enqueue <id>... | --all [--force]`: queue Articles by hand,
  and the first backfill. `--all` walks every published Article. `--force`
  re-queues even when the hash is unchanged, which is how a parked row is
  retried.
- `wp ik2 corpus status`: Documents by status, parked rows with their
  `last_error`, Chunk and vector counts, and the last Run.
- `wp ik2 corpus preview <id>`: print the `embed_text` and each Chunk with
  its heading, anchor and token estimate, without writing anything.

### Reading related posts

`get_related( int $post_id, int $limit ): int[]` returns related post IDs in
rank order, keeping only rows with `score >= RELATED_MIN_SCORE`. Results are
cached with `wp_cache_*` in group `ik2-corpus` under
`related:{post_id}:{last run id}`. The last Run ID comes from one
`SELECT MAX(id)` per request, so a new Run changes every key without
flushing Redis.

`RELATED_MIN_SCORE` is a PHP constant so it can be tuned without a worker
release. The 2026-10-30 eval (`docs/evals/2026-10-30-corpus-relevance.md`)
found no floor that keeps unrelated picks out without dropping good ones, and
the category fallback grades worse than a weak pick, so it is 0.65: a safety
net that sends no current Article to the fallback.

## Worker: `services/embedder/`

A self-contained Node package (`@ik2/embedder`) with its own lockfile and `Dockerfile`, built with `services/embedder` as the Docker context. It is not a member of the root pnpm workspace, so the root install and lockfile stay untouched.

### Runtime

- `node:24-slim`, `@huggingface/transformers` pinned, `mysql2`.
- Model `nomic-ai/nomic-embed-text-v1.5`, dtype `q8`, downloaded at image
  build into `/models` and loaded with remote downloads off, so a Run needs
  no network beyond MySQL. The Dockerfile runs one embedding with remote
  models disabled as a build step, so a cache layout mistake fails the build
  instead of the first hourly Run.
- Configured by environment: `CORPUS_DB_HOST`, `CORPUS_DB_PORT`,
  `CORPUS_DB_USER`, `CORPUS_DB_PASSWORD`, `CORPUS_DB_NAME`,
  `CORPUS_TABLE_PREFIX` (default `wp_`).

### A Run

The whole Run uses one `mysql2/promise` connection, never a pool: `GET_LOCK`
belongs to the session that took it and a transaction is per connection.

1. `SELECT GET_LOCK('ik2_corpus', 0)`. If another Run holds it, log and exit 0.
2. Find work: Documents with `status` `queued` or `remove` and
   `attempts < 3`, stale or missing vectors, and orphan vectors (see the
   vectors table). Read each queued Document's `content_hash` now and keep
   it for step 7. No work: log one line, release the lock, exit 0 without
   loading the model.
3. Removals: delete the Document, its Chunks and their vectors, then any
   orphan vectors.
4. Embeddings, one input at a time:
   - Document vectors from `embed_text` with the `clustering: ` prefix,
     truncated at 1,024 tokens by the model's tokenizer.
   - Chunk vectors from Chunk `text` with the `search_document: ` prefix,
     truncated at 512 tokens.
   - Only stale or missing vectors. A model change makes every vector
     stale, so the whole Corpus is re-embedded in that Run.
   - Mean pooling, normalised to unit length, 768 dimensions.
   - A Document whose embedding fails gets `attempts + 1` and `last_error`.
     At 3 it becomes `parked`. Others carry on.
5. Ranking, over every Document with a current-model vector that is not
   marked `remove` (not only the changed ones, since one
   new Article can change everyone's list):
   - Vector list: cosine between Document vectors, top 20.
   - Keyword list: BM25 (k1 1.2, b 0.75, stopwords, light plural stemming)
     over `embed_text`, querying with the Article's title and tags as
     unique terms (what the prototype measured), top 20.
   - Fuse with reciprocal rank fusion, k = 60, equal weights.
   - Keep the top 8 per Article with their cosine score.
6. In one transaction: insert the Run row, `DELETE` every row from
   `ik2_corpus_related` (not `TRUNCATE`, which commits on its own and needs
   the DROP privilege), and insert the new lists with that Run's ID. The new
   Run ID and the new lists become visible in the same commit, so a reader
   can never cache old lists under the new ID.
7. Set each processed Document to `indexed` with `indexed_at`, using
   `WHERE id = ? AND content_hash = ?` with the hash read in step 2. An
   Article edited during the Run keeps `queued` and is picked up next hour.
8. Release the lock and log one line: Documents and Chunks embedded,
   removed, failed, duration.

Exit codes: 0 on success or no work, 1 when the Run itself failed
(database unreachable, model would not load). Per-Document failures don't
fail the Run.

### MySQL user

The worker connects as its own user. In production, once:

```sql
CREATE USER 'ik2_corpus'@'%' IDENTIFIED BY '<generated>';
GRANT SELECT, UPDATE, DELETE ON `<db>`.`wp_ik2_corpus_documents` TO 'ik2_corpus'@'%';
GRANT SELECT, DELETE ON `<db>`.`wp_ik2_corpus_chunks` TO 'ik2_corpus'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON `<db>`.`wp_ik2_corpus_vectors` TO 'ik2_corpus'@'%';
GRANT SELECT, INSERT, DELETE ON `<db>`.`wp_ik2_corpus_related` TO 'ik2_corpus'@'%';
GRANT SELECT, INSERT ON `<db>`.`wp_ik2_corpus_runs` TO 'ik2_corpus'@'%';
```

`GET_LOCK` needs no grant. The password lives only in the env file on the
host.

## Related posts block

### Ranking rules

`filter_related_query` (queryId 44, `wp-content/themes/ik2/inc/blocks.php`)
asks for 4 Articles:

1. `get_related( $post_id, 4 )`.
2. If that returns fewer than 4, fill from today's query (newest in the first
   category, excluding the current post and the IDs already picked).
3. Set `post__in` to the final IDs with `orderby => post__in`.

So an Article with no Corpus rows yet, or none above the floor, shows
today's picks in the new layout. `hide_empty_related_query` stays as it is.
The Query Loop only returns published posts, so a related row pointing at an
Article unpublished since the last Run is skipped on its own.

### Layout ("Next read", option B)

Mockups: https://claude.ai/artifact/SSEb1dpkqXej6R2MVwWxAR

- Moves from the 60rem article column to the wide size (75rem), after the
  tags line.
- Opens with a 2px ink rule. The heading "Keep reading" sits on the left in
  mono at small size, and "closest to this post" in dust on the right.
- One Query Loop, 4 items. The first item is the lead: category wash cover
  (the same `category-wash` tokens as the /articles card), date and reading
  time in mono, title at medium size, excerpt. Items 2 to 4 are rows in a
  second column: title and `date · category`, separated by `line` hairlines.
  The post template carries every field, and CSS shows the cover and excerpt
  only on `:first-child`.
- Under 40em the columns stack: lead first, then the rows.
- The lead is one link target (title link stretched over the card). Hover
  changes title color to `signal-deep` only, `200ms ease`. Focus-visible
  follows the site rule.
- Styles stay in `src/styles/_article-single.scss` under the existing
  `.ik-related` class names, so `_view-transitions.scss` keeps working.

## Build, local and production

- **Image.** `build.yml` gets an `embedder` job that builds
  `services/embedder/Dockerfile` and pushes
  `ghcr.io/ivankristianto/ik2-embedder` with the same tags as app and nginx
  (semver and `latest` on `v*` tags, `<branch>` and `sha-*` on manual runs).
- **Local.** An `embedder` service in `docker-compose.yml` under the
  `corpus` profile, on the default network, pointed at the local `db`.
  `composer dev:corpus` runs `docker/corpus/grant.sh` and then
  `docker compose --profile corpus run --rm embedder`. `grant.sh` applies the
  same grants as production against the running `db` service with
  `CREATE USER IF NOT EXISTS`, so it is safe to repeat and grant mistakes show
  up locally first. It is not an initdb script: table-level grants fail until
  the plugin has created the tables.
- **Production.** A Dokploy Server schedule, hourly (`0 * * * *`):

  ```
  docker run --rm --pull=always --memory=1g --network dokploy-network \
    --env-file /etc/ik2/corpus.env ghcr.io/ivankristianto/ik2-embedder:latest
  ```

  `--pull=always` picks up a new release on the next hour. `--memory=1g`
  stops a bad Run from starving WordPress. Dokploy compose and application
  schedules run through `docker exec` into a running container, which is why
  this one is a Server schedule.

## Testing

- **PHPUnit** (new dev dependency, `composer test`, added to the Quality
  workflow). Pure functions only, no WordPress bootstrap: the cleaner takes
  a block array and returns sections, the chunker takes sections and returns
  Chunks. Fixtures cover h2/h3 splits, h4 kept inline, tiny-section merging,
  long-section paragraph splits, long code blocks, dropped media blocks,
  nested groups and freeform HTML.
- **`node --test`** in `services/embedder/`: BM25 scoring, RRF fusion,
  cosine on packed float32, staleness detection, prefix choice per level,
  and the 8-per-Article cut.
- **Relevance check.** A fixture of the 8 test Articles (IDs 503, 494, 985,
  576, 536, 522, 701, 1981) with the picks a reader would accept. After a
  local backfill, print each Article's top 4 and their scores, and pick
  `RELATED_MIN_SCORE` as the lowest score that keeps the clearly unrelated
  picks out. Record the result in the PR.
- **Spot checks.** `wp ik2 corpus preview <id>` on a long guide, a short
  2009 note and a post with code.

## Rollout

1. Merge, then cut a release (`pnpm release minor`). Merging alone deploys
   nothing. The release deploys the plugin, which creates the tables, and
   pushes the `ik2-embedder` image. Nothing is queued yet, so the block keeps
   rendering today's picks through the fallback.
2. Create the `ik2_corpus` MySQL user with its grants (the tables now exist)
   and `/etc/ik2/corpus.env` on the host. Make the new
   `ghcr.io/ivankristianto/ik2-embedder` package public (a first push starts
   private, and the host's `docker run` doesn't use Dokploy's registry
   login), then `docker pull` it once by hand.
3. `wp ik2 corpus enqueue --all` on production.
4. Run the `docker run` command once by hand, watch its peak memory, and
   check `wp ik2 corpus status`.
5. Add the hourly Dokploy Server schedule.
6. Delete the "Hello world!" sample post (2026-10-06) on production.

Later schema changes: the worker image moves `latest` on every release,
independently of the app deploy. A change that adds a column the worker reads
ships the plugin migration in one release and the worker change in the next,
or the worker tolerates both shapes.

## Risks

- **Server memory.** 575 MB measured on a Mac at about 600 tokens per input.
  The 512-token Chunk cap and the 1,024-token Document cap, both enforced
  by the tokenizer in the worker, keep inputs bounded, and `--memory=1g` caps the container. Check the first Run's peak
  in Dokploy before enabling the schedule.
- **Image size.** `node_modules` for transformers.js was 377 MB on macOS,
  including binaries for other platforms. Prune to linux-x64 in the
  Dockerfile and check the final image size in the first build.
- **Hourly lag.** A new or edited Article shows category picks, or its old
  list, for up to an hour, and cached pages until they expire. Accepted.
