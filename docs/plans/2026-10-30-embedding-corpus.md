# Embedding corpus: implementation plan

Spec: `docs/specs/2026-10-30-embedding-corpus-design.md`. The spec decides
behaviour; this plan splits the work and fixes the interfaces between the
pieces so they can be built in parallel. Branch: `embedding-corpus`.

## Shared rules for every task

- Work in the main checkout (Docker bind-mounts it). No worktrees.
- Edit only the files your task owns. If you need a change elsewhere, ask the
  coordinator instead of making it.
- Do not commit. The coordinator commits atomically after integration.
- Never run a formatter or `--fix` without a file list (`phpcbf <files>`,
  `eslint --fix <files>`). Unscoped formatters have rewritten this repo before.
- No em dashes in code comments, docs or copy.
- Follow `CLAUDE.md`: named functions for hook callbacks, short doc blocks,
  why-comments only, tokens instead of literal values in CSS, no `!important`.
- After adding a new PHP file or `require_once`, run
  `docker compose restart app` before testing (opcache).

## Interfaces (fixed; change only through the coordinator)

### Tables

Exactly as in the spec's Data model section, with `$wpdb->prefix` (local
`wp_`). The related table's order column is `position`, not `rank`, and
the vectors table's data column is `embedding`, not `vector` (both are
reserved words). dbDelta does not quote identifiers.
Vectors are little-endian float32, unit length, 768 dims, stored in `blob`.
Model string stored in `model` columns: `nomic-ai/nomic-embed-text-v1.5:q8`.
Prefix strings stored in `prefix`: `clustering: ` and `search_document: `
(with the trailing space).

### PHP text module: `wp-content/plugins/ik2/inc/corpus/text.php`

Namespace `IK2\Plugin\Corpus\Text`. Pure functions: no WordPress function
calls, no globals, so PHPUnit can load the file directly. No `ABSPATH` guard
in this file: PHPUnit defines nothing from WordPress and the guard would
turn the test run into zero tests. Tag stripping uses `strip_tags()`; B adds
a file-scoped exclude for `WordPress.WP.AlternativeFunctions.strip_tags_strip_tags`
on this file in `phpcs.xml.dist`, with a one-line reason.

```php
/**
 * @param array<int,array<string,mixed>> $blocks  Output of parse_blocks().
 * @param callable|null $render  fn(array $block): string, used for unknown
 *                               dynamic blocks. Null means skip them.
 * @return list<array{heading:string, anchor:string, text:string}>
 *         The first section has heading '' (text before the first h2/h3).
 */
function clean_blocks( array $blocks, ?callable $render = null ): array;

/**
 * @param list<array{heading:string, anchor:string, text:string}> $sections
 * @return list<array{chunk_index:int, heading:string, anchor:string,
 *                    text:string, token_estimate:int}>
 */
function chunk_sections( string $title, array $sections ): array;

/** @param list<array{heading:string, anchor:string, text:string}> $sections */
function build_embed_text( string $title, array $categories, array $tags, array $sections ): string;

function estimate_tokens( string $text ): int; // ceil( word count * 1.3 )
```

The caller strips shortcodes (`strip_shortcodes`) on `post_content` before
`parse_blocks`, and passes `'render_block'` as `$render`.

### PHP queue API (task A)

```php
namespace IK2\Plugin\Corpus;
const CORPUS_DB_VERSION = '1';
function maybe_upgrade(): void;        // on plugins_loaded, dbDelta when the option differs
function sync( int $post_id ): void;   // every hook calls this; decides enqueue or remove
function enqueue( int $post_id, bool $force = false ): void;
```

See the spec's Queueing section for the hash and status rules.

### PHP read API: `wp-content/plugins/ik2/inc/corpus/related.php`

```php
namespace IK2\Plugin\Corpus;
const RELATED_MIN_SCORE = 0.6; // placeholder, tuned in the eval task
/** @return list<int> post IDs, best first, score >= RELATED_MIN_SCORE */
function get_related( int $post_id, int $limit ): array;
```

The theme calls it guarded with `function_exists( '\IK2\Plugin\Corpus\get_related' )`.

### Worker

- Path `services/embedder/`, Docker context `services/embedder`,
  entry `node src/run.mjs`. Self-contained package with its own lockfile,
  not in the root pnpm workspace.
- Env: `CORPUS_DB_HOST`, `CORPUS_DB_PORT` (default 3306), `CORPUS_DB_USER`,
  `CORPUS_DB_PASSWORD`, `CORPUS_DB_NAME`, `CORPUS_TABLE_PREFIX` (default `wp_`).
- Tests: `node --test` inside `services/embedder` (`npm test` or
  `pnpm test` there).
- One `mysql2/promise` connection for the whole Run, no pool.
- Document inputs truncated at 1,024 tokens and Chunk inputs at 512, with
  the model's tokenizer.
- The Dockerfile runs one embedding offline as a build step.

### Local MySQL user

User `ik2_corpus`, password `ik2_corpus_local` locally only, grants exactly
as in the spec. Local DB is MariaDB 11.8, service `db`, database name from
`docker-compose.yml`. `docker/corpus/grant.sh` (task E) applies them against
the running `db` with `CREATE USER IF NOT EXISTS`; it is not an initdb
script, because table-level grants fail until the tables exist.

## Tasks and ownership

| Task | Owns |
| --- | --- |
| A. PHP corpus core | `wp-content/plugins/ik2/inc/corpus/` except `text.php`; `wp-content/plugins/ik2/inc/cli/class-corpus-command.php`; edits to `wp-content/plugins/ik2/plugin.php`, `inc/namespace.php`, `inc/cli/namespace.php` |
| B. PHP text module + PHPUnit | `wp-content/plugins/ik2/inc/corpus/text.php`; `tests/php/`; `phpunit.xml.dist`; `phpcs.xml.dist`; `composer.json` and `composer.lock` (PHPUnit dev dep, `test` script, `dev:corpus` script) |
| C. Worker | `services/embedder/**` |
| D. Theme block (option B) | `wp-content/themes/ik2/patterns/single-article-related.php`, `templates/single.html`, `inc/blocks.php` (theme), `src/styles/_article-single.scss` related section, built CSS |
| E. Infra | `docker-compose.yml`, `docker/corpus/` (`grant.sql`, `grant.sh`), `.dockerignore` (add `services/`), `.github/workflows/build.yml`, `.github/workflows/quality.yml` (adds the `composer test` and embedder `node --test` steps; they go green once B and C land) |
| F. Eval (after A to E) | `scripts/corpus-eval.mjs`, `tests/fixtures/corpus-eval.json`, tuning `RELATED_MIN_SCORE` |

`composer dev:corpus` runs `bash docker/corpus/grant.sh && docker compose --profile corpus run --rm embedder`
(script owned by B, service and `grant.sh` owned by E).
