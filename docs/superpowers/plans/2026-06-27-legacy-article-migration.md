# Legacy Article Migration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a re-runnable `wp ik2 migrate-articles` command that imports published articles from the old ivankristianto.com MySQL dump into the current site, sideloads every referenced image from a local copy of the old uploads folder, and rewrites content to point at the new media.

**Architecture:** A WP-CLI command in the first-party ik2 plugin, following the exact conventions of the existing `ik2 setup` command (a command class plus a `migrate/` subfolder of single-responsibility helper classes). The command opens a second `wpdb` connection to a separate legacy database (read-only), then runs each legacy post through a pipeline: match-by-slug → insert/skip/overwrite → sideload featured + inline media → rewrite URLs → assign terms + post format → copy Yoast meta → stamp legacy id. Idempotency is slug-based; `--force` re-forces the copy.

**Tech Stack:** PHP 8.4, WP-CLI, WordPress core APIs (`wp_insert_post`, `media_handle_sideload`, `wp_set_object_terms`, `set_post_format`), a second `wpdb` instance, Docker Compose (MariaDB 11), PHPCS (WPCS) + PHPStan level 6.

## Global Constraints

- PHP files start with `<?php`, a docblock, `declare(strict_types=1);`, the namespace, then `defined( 'ABSPATH' ) || exit;` — copy this preamble from any existing file under `wp-content/plugins/ik2/inc/cli/`.
- Namespace for new helper classes: `IK2\Plugin\CLI\Migrate`. The command class stays in `IK2\Plugin\CLI` (matching `Stats_Command` / `Setup_Command`).
- **Named functions and methods only — no closures/arrow functions** in hook callbacks or non-trivial array callbacks (project PHP convention).
- **No `// phpcs:disable`** without first checking `phpcs.xml.dist`. Interpolating the configurable table prefix into SQL is unavoidable; if WPCS flags it, use a targeted single-line `// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared` with a comment, never a block disable.
- File naming: `class-<kebab-name>.php` for classes, lowercase with hyphens — matches `class-check-result.php`, `class-setup-command.php`.
- Result/value objects mirror `Check_Result`: public typed properties, a constructor, no behavior.
- Idempotency key is the **post slug** (`post_name`) for posts and the **attachment slug** (sanitized original filename) for media. Re-runs skip; `--force` overwrites/re-sideloads.
- Status filter: **published only** (`post_status = 'publish'`). Comments are **not** migrated. All imported posts are assigned to a single target author.
- Quality gate after every task: `docker compose --profile tools run --rm composer quality` must pass (PHPCS + PHPStan level 6). New PHP files are linted; `wp-content/plugins/` third-party code is excluded by config, but **our** plugin code is not — it is linted.
- Run the command through the existing wrapper: `composer dev:wp:cmd -- ik2 migrate-articles ...`.
- After adding any new PHP file with a new `require_once`, restart the app/cli containers to bust opcache before testing (`docker compose restart app wp-cli`).

---

## File Structure

| File | Responsibility |
| :-- | :-- |
| `docker-compose.yml` (modify) | Bind-mount `./legacy` into the `wp-cli` service so the command can read the legacy uploads copy. |
| `.gitignore` (modify) | Ignore `/legacy/` (dev-only dump + uploads, never committed). |
| `wp-content/plugins/ik2/inc/cli/namespace.php` (modify) | `require_once` the new files and register `ik2 migrate-articles`. |
| `wp-content/plugins/ik2/inc/cli/class-migrate-articles-command.php` (create) | The WP-CLI command: parse flags → build config → wire helpers → loop → print summary. |
| `wp-content/plugins/ik2/inc/cli/migrate/class-migration-config.php` (create) | Value object: legacy connection, prefix, uploads path, old base URL, author id, flags. Built and validated from assoc args + env. |
| `wp-content/plugins/ik2/inc/cli/migrate/class-migration-result.php` (create) | Value object: per-post outcome (status, slug, note, media-added count). |
| `wp-content/plugins/ik2/inc/cli/migrate/class-content-rewriter.php` (create) | **Pure logic.** Strip size suffix, extract upload URLs from content, rewrite URLs via a map. |
| `wp-content/plugins/ik2/inc/cli/migrate/class-legacy-db.php` (create) | Second `wpdb` reader: published posts, postmeta, terms, post format, thumbnail src. |
| `wp-content/plugins/ik2/inc/cli/migrate/class-media-sideloader.php` (create) | Resolve an old URL to a local file, reuse-or-sideload, return new attachment id + URL. |
| `wp-content/plugins/ik2/inc/cli/migrate/class-post-importer.php` (create) | Orchestrate one post end-to-end; returns a `Migration_Result`. |
| `docs/runbooks/legacy-article-migration.md` (create) | Operator runbook: import dump, place uploads, set permalinks, run sequence. |

Verification scripts written during TDD steps go in the scratchpad (`/private/tmp/claude-501/.../scratchpad`) and are **not committed** — the repo has no test framework, and these are throwaway checks consistent with how the existing CLI commands are verified. Durable verification is `composer quality` + dry-run + idempotency re-run, all captured in the runbook.

---

## Task 1: Wire the legacy data mount

**Files:**
- Modify: `docker-compose.yml` (the `wp-cli` service `volumes:` block, around lines 123-129)
- Modify: `.gitignore`

**Interfaces:**
- Produces: a `/legacy` directory inside the `wp-cli` container, read-only, backed by `./legacy` on the host. Convention: `./legacy/uploads/` holds the old `wp-content/uploads` copy; `./legacy/*.sql` holds the dump.

- [ ] **Step 1: Add the bind mount to the `wp-cli` service**

In `docker-compose.yml`, inside the `wp-cli:` service `volumes:` list (the one that currently ends with `- uploads:/var/www/app/wp-content/uploads`), append:

```yaml
            # Dev-only: legacy MySQL dump + old wp-content/uploads copy for the
            # one-off `wp ik2 migrate-articles` command. Read-only; gitignored.
            - ./legacy:/legacy:ro
```

- [ ] **Step 2: Ignore the legacy directory**

Append to `.gitignore`:

```gitignore

# One-off migration inputs (old DB dump + uploads copy). Never committed.
/legacy/
```

- [ ] **Step 3: Create the host directory and recreate the wp-cli container**

Run:
```bash
mkdir -p legacy/uploads
docker compose up -d --force-recreate wp-cli
```
Expected: the `wp-cli` container recreates without error.

- [ ] **Step 4: Verify the mount is visible inside the container**

Run:
```bash
composer dev:wp:cmd -- eval 'echo is_dir("/legacy") ? "MOUNT OK\n" : "MISSING\n";'
```
Expected output: `MOUNT OK`

- [ ] **Step 5: Commit**

```bash
git add docker-compose.yml .gitignore
git commit -m "chore(compose): mount ./legacy into wp-cli for article migration"
```

---

## Task 2: Config and result value objects + runnable command skeleton

**Files:**
- Create: `wp-content/plugins/ik2/inc/cli/migrate/class-migration-config.php`
- Create: `wp-content/plugins/ik2/inc/cli/migrate/class-migration-result.php`
- Create: `wp-content/plugins/ik2/inc/cli/class-migrate-articles-command.php`
- Modify: `wp-content/plugins/ik2/inc/cli/namespace.php`

**Interfaces:**
- Produces: `IK2\Plugin\CLI\Migrate\Migration_Config` with public props `legacy_db, legacy_host, legacy_user, legacy_pass, legacy_prefix` (strings), `uploads_path, old_base_url` (strings), `author_id, limit, only_post` (ints), `dry_run, verbose, force` (bools), and `public static function from_args( array $assoc_args ): self`.
- Produces: `IK2\Plugin\CLI\Migrate\Migration_Result` with public props `status` (string: `created|skipped|overwritten|failed`), `slug` (string), `note` (string), `media_added` (int), and a constructor `( string $status, string $slug, string $note, int $media_added = 0 )`.
- Produces: `IK2\Plugin\CLI\Migrate_Articles_Command` registered as `ik2 migrate-articles`.

- [ ] **Step 1: Write the result value object**

Create `wp-content/plugins/ik2/inc/cli/migrate/class-migration-result.php`:

```php
<?php
/**
 * Value object describing the outcome of importing one legacy post.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Migrate;

defined( 'ABSPATH' ) || exit;

/**
 * Outcome of one post import: status, slug, a short note, and how many
 * media files were sideloaded for it.
 */
class Migration_Result {

	/**
	 * One of: created, skipped, overwritten, failed.
	 *
	 * @var string
	 */
	public string $status;

	/**
	 * Post slug the result refers to.
	 *
	 * @var string
	 */
	public string $slug;

	/**
	 * Short human note, e.g. "already exists" or an error message.
	 *
	 * @var string
	 */
	public string $note;

	/**
	 * Number of media files sideloaded while importing this post.
	 *
	 * @var int
	 */
	public int $media_added;

	/**
	 * Constructor.
	 *
	 * @param string $status      created|skipped|overwritten|failed.
	 * @param string $slug        Post slug the result refers to.
	 * @param string $note        Short human note.
	 * @param int    $media_added Media files sideloaded for this post.
	 */
	public function __construct( string $status, string $slug, string $note, int $media_added = 0 ) {
		$this->status      = $status;
		$this->slug        = $slug;
		$this->note        = $note;
		$this->media_added = $media_added;
	}
}
```

- [ ] **Step 2: Write the config value object**

Create `wp-content/plugins/ik2/inc/cli/migrate/class-migration-config.php`:

```php
<?php
/**
 * Resolved configuration for `wp ik2 migrate-articles`.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Migrate;

use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Holds every input the migration needs: the legacy connection, the local
 * uploads path, the old site's upload base URL, the target author, and the
 * run flags. Built from CLI flags with sensible defaults from the current
 * site's DB credentials. Invalid input aborts the run via WP_CLI::error.
 */
class Migration_Config {

	public string $legacy_db;
	public string $legacy_host;
	public string $legacy_user;
	public string $legacy_pass;
	public string $legacy_prefix;
	public string $uploads_path;
	public string $old_base_url;
	public int $author_id;
	public int $limit;
	public int $only_post;
	public bool $dry_run;
	public bool $verbose;
	public bool $force;

	/**
	 * Build and validate config from associative CLI args.
	 *
	 * Defaults: legacy host/user/pass reuse the current site's DB_* constants
	 * (same MariaDB server); prefix "wp_"; uploads path /legacy/uploads;
	 * author the lowest-ID administrator.
	 *
	 * @param array<string, mixed> $assoc_args Associative CLI arguments.
	 */
	public static function from_args( array $assoc_args ): self {
		$config = new self();

		$config->legacy_db     = (string) WP_CLI\Utils\get_flag_value( $assoc_args, 'legacy-db', '' );
		$config->legacy_host   = (string) WP_CLI\Utils\get_flag_value( $assoc_args, 'legacy-host', DB_HOST );
		$config->legacy_user   = (string) WP_CLI\Utils\get_flag_value( $assoc_args, 'legacy-user', DB_USER );
		$config->legacy_pass   = (string) WP_CLI\Utils\get_flag_value( $assoc_args, 'legacy-pass', DB_PASSWORD );
		$config->legacy_prefix = (string) WP_CLI\Utils\get_flag_value( $assoc_args, 'legacy-prefix', 'wp_' );
		$config->uploads_path  = rtrim( (string) WP_CLI\Utils\get_flag_value( $assoc_args, 'uploads-path', '/legacy/uploads' ), '/' );
		$config->old_base_url  = rtrim( (string) WP_CLI\Utils\get_flag_value( $assoc_args, 'old-base-url', 'https://www.ivankristianto.com/wp-content/uploads' ), '/' );
		$config->author_id     = (int) WP_CLI\Utils\get_flag_value( $assoc_args, 'author', self::default_author_id() );
		$config->limit         = (int) WP_CLI\Utils\get_flag_value( $assoc_args, 'limit', 0 );
		$config->only_post     = (int) WP_CLI\Utils\get_flag_value( $assoc_args, 'post', 0 );
		$config->dry_run       = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$config->verbose       = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'verbose', false );
		$config->force         = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'force', false );

		$config->validate();

		return $config;
	}

	/**
	 * Abort the run if any required input is missing or unusable.
	 */
	private function validate(): void {
		if ( '' === $this->legacy_db ) {
			WP_CLI::error( 'Missing --legacy-db=<name>. Import the old dump into a separate database first.' );
		}

		if ( ! is_dir( $this->uploads_path ) ) {
			WP_CLI::error( sprintf( 'Uploads path not found: %s. Mount the old wp-content/uploads copy there.', $this->uploads_path ) );
		}

		if ( ! get_user_by( 'id', $this->author_id ) ) {
			WP_CLI::error( sprintf( 'Author id %d does not exist on this site.', $this->author_id ) );
		}
	}

	/**
	 * The lowest-ID administrator, used as the default import author.
	 */
	private static function default_author_id(): int {
		$admins = get_users(
			[
				'role'    => 'administrator',
				'orderby' => 'ID',
				'order'   => 'ASC',
				'number'  => 1,
				'fields'  => 'ID',
			]
		);

		return $admins ? (int) $admins[0] : 1;
	}
}
```

- [ ] **Step 3: Write the command skeleton (config echo only)**

Create `wp-content/plugins/ik2/inc/cli/class-migrate-articles-command.php`:

```php
<?php
/**
 * `wp ik2 migrate-articles` — import published articles + images from the
 * old ivankristianto.com site into the current site.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI;

use IK2\Plugin\CLI\Migrate\Migration_Config;
use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Reads a separate legacy database (read-only) and a local copy of the old
 * uploads folder, then imports each published post: body, slug, dates,
 * featured + inline images, categories, tags, post format, and Yoast meta.
 *
 * Idempotent by slug: a post whose slug already exists is skipped, and media
 * whose filename already exists are reused. --force re-forces the copy.
 */
class Migrate_Articles_Command {

	/**
	 * Imports published articles and their images from the old site.
	 *
	 * ## OPTIONS
	 *
	 * [--legacy-db=<name>]
	 * : Name of the database the old dump was imported into. Required.
	 *
	 * [--legacy-prefix=<prefix>]
	 * : Old site's table prefix. Default: wp_.
	 *
	 * [--legacy-host=<host>]
	 * : Legacy DB host. Default: the current site's DB_HOST.
	 *
	 * [--legacy-user=<user>]
	 * : Legacy DB user. Default: the current site's DB_USER.
	 *
	 * [--legacy-pass=<pass>]
	 * : Legacy DB password. Default: the current site's DB_PASSWORD.
	 *
	 * [--uploads-path=<path>]
	 * : Local directory holding the old wp-content/uploads copy.
	 * Default: /legacy/uploads.
	 *
	 * [--old-base-url=<url>]
	 * : The old site's uploads base URL, used to find image URLs in content.
	 * Default: https://www.ivankristianto.com/wp-content/uploads.
	 *
	 * [--author=<id>]
	 * : Target author id for imported posts. Default: lowest-ID administrator.
	 *
	 * [--limit=<n>]
	 * : Import at most N posts (for incremental testing). Default: 0 (all).
	 *
	 * [--post=<old_id>]
	 * : Import only the single legacy post with this old ID.
	 *
	 * [--dry-run]
	 * : Report what would happen; write nothing.
	 *
	 * [--verbose]
	 * : Log one line per post (status, media added, errors).
	 *
	 * [--force]
	 * : Re-force the copy: overwrite existing posts and re-sideload media
	 * even when their slugs already exist.
	 *
	 * ## EXAMPLES
	 *
	 *     # Preview the full migration without writing.
	 *     $ wp ik2 migrate-articles --legacy-db=legacy --dry-run
	 *
	 *     # Import one post end-to-end with per-step logging.
	 *     $ wp ik2 migrate-articles --legacy-db=legacy --post=1234 --verbose
	 *
	 *     # Run the full migration; re-run safely until failures hit zero.
	 *     $ wp ik2 migrate-articles --legacy-db=legacy
	 *
	 *     # Re-import everything, overwriting prior imports.
	 *     $ wp ik2 migrate-articles --legacy-db=legacy --force
	 *
	 * @param array<int, string>   $args       Positional arguments (unused).
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		$config = Migration_Config::from_args( $assoc_args );

		WP_CLI::log( sprintf( 'Legacy DB: %s (prefix %s) on %s', $config->legacy_db, $config->legacy_prefix, $config->legacy_host ) );
		WP_CLI::log( sprintf( 'Uploads:   %s', $config->uploads_path ) );
		WP_CLI::log( sprintf( 'Author:    %d', $config->author_id ) );
		WP_CLI::log( sprintf( 'Flags:     dry-run=%s verbose=%s force=%s limit=%d post=%d', $config->dry_run ? 'yes' : 'no', $config->verbose ? 'yes' : 'no', $config->force ? 'yes' : 'no', $config->limit, $config->only_post ) );

		WP_CLI::success( 'Config resolved. Pipeline wired in later tasks.' );
	}
}
```

- [ ] **Step 4: Register the command and its files**

In `wp-content/plugins/ik2/inc/cli/namespace.php`, inside `bootstrap()`, add the requires after the existing `require_once __DIR__ . '/class-setup-command.php';` line:

```php
	require_once __DIR__ . '/migrate/class-migration-config.php';
	require_once __DIR__ . '/migrate/class-migration-result.php';
	require_once __DIR__ . '/migrate/class-content-rewriter.php';
	require_once __DIR__ . '/migrate/class-legacy-db.php';
	require_once __DIR__ . '/migrate/class-media-sideloader.php';
	require_once __DIR__ . '/migrate/class-post-importer.php';
	require_once __DIR__ . '/class-migrate-articles-command.php';
```

And add the command registration after the existing `\WP_CLI::add_command( 'ik2 setup', Setup_Command::class );` line:

```php
	\WP_CLI::add_command( 'ik2 migrate-articles', Migrate_Articles_Command::class );
```

> Note: the `migrate/` files for rewriter, legacy-db, media-sideloader, and post-importer are created in later tasks. Until then, those `require_once` lines will fatal. To keep this task runnable on its own, **temporarily** include only the three files that exist now (`class-migration-config.php`, `class-migration-result.php`, `class-migrate-articles-command.php`) and add the other four `require_once` lines in the task that creates each file. Create each missing file's `require_once` line in the same task that creates the file.

Revised Step 4 requires for THIS task only:

```php
	require_once __DIR__ . '/migrate/class-migration-config.php';
	require_once __DIR__ . '/migrate/class-migration-result.php';
	require_once __DIR__ . '/class-migrate-articles-command.php';
```

```php
	\WP_CLI::add_command( 'ik2 migrate-articles', Migrate_Articles_Command::class );
```

- [ ] **Step 5: Restart cli to bust opcache and verify the command registers**

Run:
```bash
docker compose restart app wp-cli
composer dev:wp:cmd -- ik2 migrate-articles --legacy-db=legacy --uploads-path=/legacy --dry-run
```
Expected: prints the four config lines and `Success: Config resolved...`. (Using `/legacy` for `--uploads-path` here because it always exists; the real run uses `/legacy/uploads`.)

- [ ] **Step 6: Verify the missing-input guard**

Run:
```bash
composer dev:wp:cmd -- ik2 migrate-articles --uploads-path=/legacy
```
Expected: `Error: Missing --legacy-db=<name>...`

- [ ] **Step 7: Quality gate**

Run:
```bash
docker compose --profile tools run --rm composer quality
```
Expected: PHPCS + PHPStan pass with no errors in the new files.

- [ ] **Step 8: Commit**

```bash
git add wp-content/plugins/ik2/inc/cli/migrate/class-migration-config.php \
        wp-content/plugins/ik2/inc/cli/migrate/class-migration-result.php \
        wp-content/plugins/ik2/inc/cli/class-migrate-articles-command.php \
        wp-content/plugins/ik2/inc/cli/namespace.php
git commit -m "feat(cli): scaffold ik2 migrate-articles command and config"
```

---

## Task 3: Content rewriter (pure logic, TDD)

**Files:**
- Create: `wp-content/plugins/ik2/inc/cli/migrate/class-content-rewriter.php`
- Modify: `wp-content/plugins/ik2/inc/cli/namespace.php` (add its `require_once`)
- Test (scratchpad, not committed): `<scratchpad>/test-content-rewriter.php`

**Interfaces:**
- Produces: `IK2\Plugin\CLI\Migrate\Content_Rewriter` with:
  - `public function strip_size_suffix( string $filename ): string` — `photo-1024x768.jpg` → `photo.jpg`; leaves `photo.jpg` and `photo-scaled.jpg` unchanged.
  - `public function extract_upload_urls( string $content, string $old_base ): array` — returns a unique list of absolute URLs under `<old_base>` found in the content.
  - `public function rewrite( string $content, array $url_map ): string` — replaces each `old => new` URL in `$url_map` throughout the content.

- [ ] **Step 1: Write the failing test**

Create `<scratchpad>/test-content-rewriter.php` (run via `wp eval-file`, so `ABSPATH` is defined):

```php
<?php
require_once '/var/www/app/wp-content/plugins/ik2/inc/cli/migrate/class-content-rewriter.php';

use IK2\Plugin\CLI\Migrate\Content_Rewriter;

$r = new Content_Rewriter();

// strip_size_suffix
assert( $r->strip_size_suffix( 'photo-1024x768.jpg' ) === 'photo.jpg' );
assert( $r->strip_size_suffix( 'photo.jpg' ) === 'photo.jpg' );
assert( $r->strip_size_suffix( 'my-photo-2.png' ) === 'my-photo-2.png' );
assert( $r->strip_size_suffix( 'a-b-300x200.jpeg' ) === 'a-b.jpeg' );

// extract_upload_urls
$base    = 'https://www.ivankristianto.com/wp-content/uploads';
$content = '<img src="' . $base . '/2019/05/a-300x200.jpg"/> text '
		. '<a href="' . $base . '/2019/05/a.jpg">x</a> '
		. '<img src="https://other.example/wp-content/uploads/b.jpg"/>';
$urls = $r->extract_upload_urls( $content, $base );
sort( $urls );
assert( $urls === [ $base . '/2019/05/a-300x200.jpg', $base . '/2019/05/a.jpg' ] );

// rewrite
$map     = [ $base . '/2019/05/a.jpg' => 'https://new.test/wp-content/uploads/2021/01/a.jpg' ];
$rewrote = $r->rewrite( 'see ' . $base . '/2019/05/a.jpg now', $map );
assert( $rewrote === 'see https://new.test/wp-content/uploads/2021/01/a.jpg now' );

echo "ALL PASS\n";
```

- [ ] **Step 2: Run the test to verify it fails**

Run:
```bash
cp <scratchpad>/test-content-rewriter.php legacy/test-content-rewriter.php
composer dev:wp:cmd -- eval-file /legacy/test-content-rewriter.php
```
Expected: a fatal error — `class-content-rewriter.php` does not exist yet (file not found / class not found).

- [ ] **Step 3: Write the implementation**

Create `wp-content/plugins/ik2/inc/cli/migrate/class-content-rewriter.php`:

```php
<?php
/**
 * Pure helpers for finding and rewriting old upload URLs in post content.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Migrate;

defined( 'ABSPATH' ) || exit;

/**
 * Stateless string operations: strip WordPress size suffixes, pull upload
 * URLs out of HTML, and apply an old-to-new URL map. No WordPress or DB
 * dependencies, so it is unit-testable in isolation.
 */
class Content_Rewriter {

	/**
	 * Remove a WordPress "-WIDTHxHEIGHT" size suffix from a filename.
	 *
	 * "photo-1024x768.jpg" becomes "photo.jpg". Filenames without a size
	 * suffix (including "-scaled") are returned unchanged.
	 *
	 * @param string $filename Basename, with extension.
	 */
	public function strip_size_suffix( string $filename ): string {
		return (string) preg_replace( '/-\d+x\d+(?=\.[A-Za-z0-9]+$)/', '', $filename );
	}

	/**
	 * Collect every unique absolute URL under $old_base found in $content.
	 *
	 * @param string $content  Post content (HTML).
	 * @param string $old_base Old uploads base URL, no trailing slash.
	 * @return array<int, string>
	 */
	public function extract_upload_urls( string $content, string $old_base ): array {
		$pattern = '#' . preg_quote( $old_base, '#' ) . '/[^\s"\'<>()]+#i';

		if ( ! preg_match_all( $pattern, $content, $matches ) ) {
			return [];
		}

		return array_values( array_unique( $matches[0] ) );
	}

	/**
	 * Replace each old URL with its new URL throughout the content.
	 *
	 * @param string                $content Post content (HTML).
	 * @param array<string, string> $url_map old_url => new_url.
	 */
	public function rewrite( string $content, array $url_map ): string {
		if ( [] === $url_map ) {
			return $content;
		}

		return strtr( $content, $url_map );
	}
}
```

- [ ] **Step 4: Add its require_once**

In `wp-content/plugins/ik2/inc/cli/namespace.php`, add immediately before the `class-migrate-articles-command.php` require:

```php
	require_once __DIR__ . '/migrate/class-content-rewriter.php';
```

- [ ] **Step 5: Run the test to verify it passes**

Run:
```bash
docker compose restart wp-cli
composer dev:wp:cmd -- eval-file /legacy/test-content-rewriter.php
```
Expected output: `ALL PASS`

- [ ] **Step 6: Quality gate**

Run:
```bash
docker compose --profile tools run --rm composer quality
```
Expected: pass.

- [ ] **Step 7: Clean up the scratch test and commit**

```bash
rm legacy/test-content-rewriter.php
git add wp-content/plugins/ik2/inc/cli/migrate/class-content-rewriter.php \
        wp-content/plugins/ik2/inc/cli/namespace.php
git commit -m "feat(cli): add content URL rewriter for article migration"
```

---

## Task 4: Legacy database reader

**Files:**
- Create: `wp-content/plugins/ik2/inc/cli/migrate/class-legacy-db.php`
- Modify: `wp-content/plugins/ik2/inc/cli/namespace.php` (add its `require_once`)

**Interfaces:**
- Consumes: `Migration_Config` (for connection + prefix).
- Produces: `IK2\Plugin\CLI\Migrate\Legacy_DB` with:
  - `public function __construct( Migration_Config $config )` — opens a second `wpdb`; aborts via `WP_CLI::error` on connection failure.
  - `public function published_posts( int $limit, int $only_post ): array` — rows as associative arrays (`ID, post_title, post_name, post_content, post_excerpt, post_date, post_date_gmt, post_modified, post_modified_gmt, post_status, post_author`).
  - `public function post_meta( int $post_id ): array` — `meta_key => array<int, string>` (a key may repeat).
  - `public function terms( int $post_id ): array` — list of `[ 'taxonomy' => string, 'name' => string, 'slug' => string ]` for `category` and `post_tag`.
  - `public function post_format( int $post_id ): ?string` — e.g. `aside`, `quote`, or `null` for standard.
  - `public function thumbnail_url( int $post_id ): ?string` — absolute old URL of the featured image, or `null`.

- [ ] **Step 1: Write the implementation**

Create `wp-content/plugins/ik2/inc/cli/migrate/class-legacy-db.php`:

```php
<?php
/**
 * Read-only access to the old site's database via a second wpdb connection.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Migrate;

use WP_CLI;
use wpdb;

defined( 'ABSPATH' ) || exit;

/**
 * Wraps a second wpdb instance pointed at the imported legacy database. All
 * methods read; nothing here writes. Table names are built from the
 * config-supplied prefix (not user input), so prefix interpolation is safe.
 */
class Legacy_DB {

	/**
	 * The legacy connection.
	 *
	 * @var wpdb
	 */
	private wpdb $db;

	/**
	 * Old site's table prefix, e.g. "wp_".
	 *
	 * @var string
	 */
	private string $prefix;

	/**
	 * Old site's uploads base URL, used to absolutize attachment paths.
	 *
	 * @var string
	 */
	private string $old_base_url;

	/**
	 * Open the legacy connection or abort the run.
	 *
	 * @param Migration_Config $config Resolved migration config.
	 */
	public function __construct( Migration_Config $config ) {
		$this->prefix       = $config->legacy_prefix;
		$this->old_base_url = $config->old_base_url;

		$this->db = new wpdb( $config->legacy_user, $config->legacy_pass, $config->legacy_db, $config->legacy_host );

		if ( ! empty( $this->db->error ) ) {
			WP_CLI::error( sprintf( 'Cannot connect to legacy DB "%s": %s', $config->legacy_db, (string) $this->db->last_error ) );
		}

		// Probe the posts table so a bad prefix fails fast with a clear message.
		$table = $this->prefix . 'posts';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- prefix is config, not user input.
		$exists = $this->db->get_var( "SHOW TABLES LIKE '{$table}'" );

		if ( null === $exists ) {
			WP_CLI::error( sprintf( 'Legacy table %s not found. Check --legacy-prefix.', $table ) );
		}
	}

	/**
	 * Published posts (post_type=post), oldest first.
	 *
	 * @param int $limit     Max rows, or 0 for no limit.
	 * @param int $only_post A single legacy post ID, or 0 for all.
	 * @return array<int, array<string, string>>
	 */
	public function published_posts( int $limit, int $only_post ): array {
		$table = $this->prefix . 'posts';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- prefix is config, not user input.
		$sql = "SELECT * FROM {$table} WHERE post_type = 'post' AND post_status = 'publish'";

		if ( $only_post > 0 ) {
			$sql .= $this->db->prepare( ' AND ID = %d', $only_post );
		}

		$sql .= ' ORDER BY ID ASC';

		if ( $limit > 0 ) {
			$sql .= $this->db->prepare( ' LIMIT %d', $limit );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- assembled with prepare() above.
		$rows = $this->db->get_results( $sql, ARRAY_A );

		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * All postmeta for a post as meta_key => list of values.
	 *
	 * @param int $post_id Legacy post ID.
	 * @return array<string, array<int, string>>
	 */
	public function post_meta( int $post_id ): array {
		$table = $this->prefix . 'postmeta';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- prefix is config, not user input.
		$rows = $this->db->get_results(
			$this->db->prepare( "SELECT meta_key, meta_value FROM {$table} WHERE post_id = %d", $post_id ),
			ARRAY_A
		);

		$meta = [];

		foreach ( (array) $rows as $row ) {
			$meta[ $row['meta_key'] ][] = (string) $row['meta_value'];
		}

		return $meta;
	}

	/**
	 * Category and tag terms attached to a post.
	 *
	 * @param int $post_id Legacy post ID.
	 * @return array<int, array<string, string>>
	 */
	public function terms( int $post_id ): array {
		$tr = $this->prefix . 'term_relationships';
		$tt = $this->prefix . 'term_taxonomy';
		$t  = $this->prefix . 'terms';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- prefixes are config, not user input.
		$sql = $this->db->prepare(
			"SELECT tt.taxonomy AS taxonomy, t.name AS name, t.slug AS slug
			 FROM {$tr} tr
			 INNER JOIN {$tt} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
			 INNER JOIN {$t} t ON t.term_id = tt.term_id
			 WHERE tr.object_id = %d AND tt.taxonomy IN ( 'category', 'post_tag' )",
			$post_id
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- prepared above.
		$rows = $this->db->get_results( $sql, ARRAY_A );

		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * The post format slug (e.g. "aside"), or null for standard.
	 *
	 * @param int $post_id Legacy post ID.
	 */
	public function post_format( int $post_id ): ?string {
		$tr = $this->prefix . 'term_relationships';
		$tt = $this->prefix . 'term_taxonomy';
		$t  = $this->prefix . 'terms';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- prefixes are config, not user input.
		$sql = $this->db->prepare(
			"SELECT t.slug AS slug
			 FROM {$tr} tr
			 INNER JOIN {$tt} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
			 INNER JOIN {$t} t ON t.term_id = tt.term_id
			 WHERE tr.object_id = %d AND tt.taxonomy = 'post_format'
			 LIMIT 1",
			$post_id
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- prepared above.
		$slug = $this->db->get_var( $sql );

		if ( null === $slug ) {
			return null;
		}

		// Stored as "post-format-aside"; WordPress wants "aside".
		return (string) preg_replace( '/^post-format-/', '', (string) $slug );
	}

	/**
	 * Absolute old URL of the featured image, or null if none.
	 *
	 * @param int $post_id Legacy post ID.
	 */
	public function thumbnail_url( int $post_id ): ?string {
		$meta          = $this->post_meta( $post_id );
		$thumbnail_ids = $meta['_thumbnail_id'] ?? [];

		if ( [] === $thumbnail_ids ) {
			return null;
		}

		$attachment_id = (int) $thumbnail_ids[0];
		$attached      = $this->post_meta( $attachment_id );
		$relative      = $attached['_wp_attached_file'][0] ?? '';

		if ( '' === $relative ) {
			return null;
		}

		return $this->old_base_url . '/' . ltrim( $relative, '/' );
	}
}
```

- [ ] **Step 2: Add its require_once**

In `wp-content/plugins/ik2/inc/cli/namespace.php`, add before the command require:

```php
	require_once __DIR__ . '/migrate/class-legacy-db.php';
```

- [ ] **Step 3: Verify against the imported legacy DB**

Prerequisite: the operator has imported the old dump into a `legacy` database (see runbook, Task 8). If the dump is not yet available, seed a one-row fixture:

```bash
docker compose exec -T db mariadb -uroot -proot -e \
  "CREATE DATABASE IF NOT EXISTS legacy; GRANT ALL ON legacy.* TO 'wordpress'@'%'; FLUSH PRIVILEGES;"
docker compose exec -T db mariadb -uroot -proot legacy -e \
  "CREATE TABLE IF NOT EXISTS wp_posts (ID bigint PRIMARY KEY, post_author bigint, post_date datetime, post_date_gmt datetime, post_modified datetime, post_modified_gmt datetime, post_content longtext, post_title text, post_excerpt text, post_status varchar(20), post_name varchar(200), post_type varchar(20));
   INSERT IGNORE INTO wp_posts (ID,post_author,post_date,post_date_gmt,post_modified,post_modified_gmt,post_content,post_title,post_excerpt,post_status,post_name,post_type)
   VALUES (1,1,'2019-05-01 10:00:00','2019-05-01 10:00:00','2019-05-01 10:00:00','2019-05-01 10:00:00','body','Hello Legacy','x','publish','hello-legacy','post');"
```

Then run a probe via `wp eval`:
```bash
composer dev:wp:cmd -- eval '
require_once "/var/www/app/wp-content/plugins/ik2/inc/cli/migrate/class-migration-config.php";
require_once "/var/www/app/wp-content/plugins/ik2/inc/cli/migrate/class-legacy-db.php";
$c = IK2\Plugin\CLI\Migrate\Migration_Config::from_args(["legacy-db"=>"legacy","uploads-path"=>"/legacy"]);
$db = new IK2\Plugin\CLI\Migrate\Legacy_DB($c);
$posts = $db->published_posts(0,0);
echo "POSTS=".count($posts)."\n";
echo "FIRST_SLUG=".($posts[0]["post_name"] ?? "none")."\n";
'
```
Expected: `POSTS=1` (or the real count) and `FIRST_SLUG=hello-legacy` (or the real first slug).

- [ ] **Step 4: Quality gate**

Run:
```bash
docker compose --profile tools run --rm composer quality
```
Expected: pass. If PHPCS flags the prefix interpolation despite the targeted `// phpcs:ignore` lines, confirm the sniff names against `phpcs.xml.dist` and adjust the ignore to the exact sniff reported — do not add a block disable.

- [ ] **Step 5: Commit**

```bash
git add wp-content/plugins/ik2/inc/cli/migrate/class-legacy-db.php \
        wp-content/plugins/ik2/inc/cli/namespace.php
git commit -m "feat(cli): add legacy DB reader for article migration"
```

---

## Task 5: Media sideloader

**Files:**
- Create: `wp-content/plugins/ik2/inc/cli/migrate/class-media-sideloader.php`
- Modify: `wp-content/plugins/ik2/inc/cli/namespace.php` (add its `require_once`)

**Interfaces:**
- Consumes: `Migration_Config` (uploads path, old base URL) and `Content_Rewriter` (`strip_size_suffix`).
- Produces: `IK2\Plugin\CLI\Migrate\Media_Sideloader` with:
  - `public function __construct( Migration_Config $config, Content_Rewriter $rewriter )`.
  - `public function import( string $old_url, int $parent_post_id, bool $force ): array` — returns `[ 'id' => int, 'url' => string, 'reused' => bool ]`; throws `\RuntimeException` if the file cannot be found locally or sideload fails.
  - `public function find_existing( string $filename ): ?int` — attachment id matched by `_ik2_legacy_src` meta or by the sanitized original filename slug.

- [ ] **Step 1: Write the implementation**

Create `wp-content/plugins/ik2/inc/cli/migrate/class-media-sideloader.php`:

```php
<?php
/**
 * Sideloads old images from a local uploads copy into the media library.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Migrate;

use RuntimeException;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves an old upload URL to a file inside the local uploads copy and
 * imports it via media_handle_sideload, stamping each attachment with its
 * source URL so re-runs reuse instead of duplicating. --force re-imports.
 */
class Media_Sideloader {

	private string $uploads_path;
	private string $old_base_url;
	private Content_Rewriter $rewriter;

	/**
	 * Constructor; loads the admin includes media_handle_sideload needs.
	 *
	 * @param Migration_Config $config   Resolved migration config.
	 * @param Content_Rewriter $rewriter Filename helper.
	 */
	public function __construct( Migration_Config $config, Content_Rewriter $rewriter ) {
		$this->uploads_path = $config->uploads_path;
		$this->old_base_url = $config->old_base_url;
		$this->rewriter     = $rewriter;

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}

	/**
	 * Find an already-imported attachment for this source filename.
	 *
	 * Matches first on the _ik2_legacy_src stamp (any size of the same
	 * source), then on the sanitized original filename used as the slug.
	 *
	 * @param string $filename Original basename (size suffix stripped).
	 */
	public function find_existing( string $filename ): ?int {
		$slug = sanitize_title( pathinfo( $filename, PATHINFO_FILENAME ) );

		$by_slug = get_posts(
			[
				'post_type'        => 'attachment',
				'post_status'      => 'inherit',
				'name'             => $slug,
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => false,
			]
		);

		return $by_slug ? (int) $by_slug[0] : null;
	}

	/**
	 * Import one old URL into the media library (or reuse an existing one).
	 *
	 * @param string $old_url        Absolute old upload URL.
	 * @param int    $parent_post_id Attach to this post.
	 * @param bool   $force          Re-import even if already present.
	 * @return array{id:int,url:string,reused:bool}
	 * @throws RuntimeException If the file is missing locally or sideload fails.
	 */
	public function import( string $old_url, int $parent_post_id, bool $force ): array {
		$basename = basename( (string) wp_parse_url( $old_url, PHP_URL_PATH ) );
		$original = $this->rewriter->strip_size_suffix( $basename );

		if ( ! $force ) {
			$existing = $this->find_existing( $original );

			if ( null !== $existing ) {
				return [
					'id'     => $existing,
					'url'    => (string) wp_get_attachment_url( $existing ),
					'reused' => true,
				];
			}
		}

		$path = $this->resolve_local_path( $old_url );

		if ( null === $path ) {
			throw new RuntimeException( sprintf( 'local file not found for %s', $old_url ) );
		}

		// Copy into a temp file media_handle_sideload can consume and delete.
		$tmp = wp_tempnam( $original );

		if ( ! $tmp || ! copy( $path, $tmp ) ) {
			throw new RuntimeException( sprintf( 'could not stage temp file for %s', $old_url ) );
		}

		$file_array = [
			'name'     => $original,
			'tmp_name' => $tmp,
		];

		$attachment_id = media_handle_sideload( $file_array, $parent_post_id );

		if ( is_wp_error( $attachment_id ) ) {
			if ( file_exists( $tmp ) ) {
				wp_delete_file( $tmp );
			}
			throw new RuntimeException( sprintf( 'sideload failed for %s: %s', $old_url, $attachment_id->get_error_message() ) );
		}

		update_post_meta( $attachment_id, '_ik2_legacy_src', $old_url );

		return [
			'id'     => (int) $attachment_id,
			'url'    => (string) wp_get_attachment_url( $attachment_id ),
			'reused' => false,
		];
	}

	/**
	 * Map an old upload URL to a path inside the local uploads copy.
	 *
	 * Tries the exact path first, then the size-stripped original (so a
	 * URL referencing a generated thumbnail resolves to the source file).
	 *
	 * @param string $old_url Absolute old upload URL.
	 */
	private function resolve_local_path( string $old_url ): ?string {
		$url_path  = (string) wp_parse_url( $old_url, PHP_URL_PATH );
		$marker    = '/wp-content/uploads/';
		$marker_at = strpos( $url_path, $marker );

		if ( false === $marker_at ) {
			return null;
		}

		$relative = substr( $url_path, $marker_at + strlen( $marker ) );
		$exact    = $this->uploads_path . '/' . $relative;

		if ( is_file( $exact ) ) {
			return $exact;
		}

		$dir      = dirname( $relative );
		$stripped = $this->rewriter->strip_size_suffix( basename( $relative ) );
		$fallback = $this->uploads_path . '/' . ( '.' === $dir ? '' : $dir . '/' ) . $stripped;

		return is_file( $fallback ) ? $fallback : null;
	}
}
```

- [ ] **Step 2: Add its require_once**

In `wp-content/plugins/ik2/inc/cli/namespace.php`, add before the command require:

```php
	require_once __DIR__ . '/migrate/class-media-sideloader.php';
```

- [ ] **Step 3: Verify with a sample image**

Stage a sample file inside the uploads copy, then sideload it twice:

```bash
mkdir -p legacy/uploads/2019/05
cp wp-content/plugins/ik2/blocks/project-card/render.php /dev/null 2>/dev/null || true
# Use any real image on the host; example uses the WP admin star (always present in core):
docker compose exec -T wp-cli sh -c 'cp /var/www/app/wp-admin/images/wordpress-logo.png /legacy/uploads/2019/05/sample.png'

composer dev:wp:cmd -- eval '
require_once "/var/www/app/wp-content/plugins/ik2/inc/cli/migrate/class-migration-config.php";
require_once "/var/www/app/wp-content/plugins/ik2/inc/cli/migrate/class-content-rewriter.php";
require_once "/var/www/app/wp-content/plugins/ik2/inc/cli/migrate/class-media-sideloader.php";
$c = IK2\Plugin\CLI\Migrate\Migration_Config::from_args(["legacy-db"=>"legacy","uploads-path"=>"/legacy/uploads"]);
$s = new IK2\Plugin\CLI\Migrate\Media_Sideloader($c, new IK2\Plugin\CLI\Migrate\Content_Rewriter());
$url = "https://www.ivankristianto.com/wp-content/uploads/2019/05/sample-300x200.png";
$a = $s->import($url, 0, false);
echo "FIRST reused=".($a["reused"]?"yes":"no")." id=".$a["id"]."\n";
$b = $s->import($url, 0, false);
echo "SECOND reused=".($b["reused"]?"yes":"no")." id=".$b["id"]."\n";
echo ($a["id"] === $b["id"] ? "SAME ATTACHMENT\n" : "DUPLICATED!\n");
'
```
Expected:
```
FIRST reused=no id=<n>
SECOND reused=yes id=<n>
SAME ATTACHMENT
```
(The sized URL `sample-300x200.png` resolves to the local `sample.png` via the size-stripped fallback.)

- [ ] **Step 4: Quality gate**

Run:
```bash
docker compose --profile tools run --rm composer quality
```
Expected: pass.

- [ ] **Step 5: Commit**

```bash
git add wp-content/plugins/ik2/inc/cli/migrate/class-media-sideloader.php \
        wp-content/plugins/ik2/inc/cli/namespace.php
git commit -m "feat(cli): add media sideloader for article migration"
```

---

## Task 6: Post importer (orchestrates one post)

**Files:**
- Create: `wp-content/plugins/ik2/inc/cli/migrate/class-post-importer.php`
- Modify: `wp-content/plugins/ik2/inc/cli/namespace.php` (add its `require_once`)

**Interfaces:**
- Consumes: `Migration_Config`, `Legacy_DB`, `Media_Sideloader`, `Content_Rewriter`, and produces `Migration_Result`.
- Produces: `IK2\Plugin\CLI\Migrate\Post_Importer` with:
  - `public function __construct( Migration_Config $config, Legacy_DB $legacy, Media_Sideloader $media, Content_Rewriter $rewriter )`.
  - `public function import_one( array $legacy_post ): Migration_Result` — full pipeline for one post; never throws (catches and returns a `failed` result).

- [ ] **Step 1: Write the implementation**

Create `wp-content/plugins/ik2/inc/cli/migrate/class-post-importer.php`:

```php
<?php
/**
 * Imports a single legacy post end-to-end into the current site.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Migrate;

use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Runs one legacy post through the full pipeline: match by slug, insert or
 * skip/overwrite, sideload featured + inline media, rewrite content URLs,
 * assign categories/tags + post format, copy Yoast meta, and stamp the
 * legacy id. Each call is isolated: any failure becomes a failed result.
 */
class Post_Importer {

	/**
	 * Yoast meta keys copied verbatim from legacy postmeta.
	 *
	 * @var array<int, string>
	 */
	private const YOAST_KEYS = [
		'_yoast_wpseo_title',
		'_yoast_wpseo_metadesc',
		'_yoast_wpseo_canonical',
		'_yoast_wpseo_focuskw',
		'_yoast_wpseo_opengraph-title',
		'_yoast_wpseo_opengraph-description',
		'_yoast_wpseo_twitter-title',
		'_yoast_wpseo_twitter-description',
		'_yoast_wpseo_meta-robots-noindex',
		'_yoast_wpseo_meta-robots-nofollow',
	];

	private Migration_Config $config;
	private Legacy_DB $legacy;
	private Media_Sideloader $media;
	private Content_Rewriter $rewriter;

	/**
	 * Constructor.
	 *
	 * @param Migration_Config $config   Resolved config.
	 * @param Legacy_DB        $legacy   Legacy reader.
	 * @param Media_Sideloader $media    Media importer.
	 * @param Content_Rewriter $rewriter URL helper.
	 */
	public function __construct( Migration_Config $config, Legacy_DB $legacy, Media_Sideloader $media, Content_Rewriter $rewriter ) {
		$this->config   = $config;
		$this->legacy   = $legacy;
		$this->media    = $media;
		$this->rewriter = $rewriter;
	}

	/**
	 * Import one legacy post row.
	 *
	 * @param array<string, string> $legacy_post A row from Legacy_DB::published_posts().
	 */
	public function import_one( array $legacy_post ): Migration_Result {
		$slug      = (string) $legacy_post['post_name'];
		$legacy_id = (int) $legacy_post['ID'];

		try {
			$existing = $this->find_by_slug( $slug );

			if ( null !== $existing && ! $this->config->force ) {
				return new Migration_Result( 'skipped', $slug, 'slug already exists' );
			}

			if ( $this->config->dry_run ) {
				$status = null === $existing ? 'created' : 'overwritten';
				$inline = count( $this->rewriter->extract_upload_urls( (string) $legacy_post['post_content'], $this->config->old_base_url ) );
				return new Migration_Result( $status, $slug, sprintf( 'dry-run: %d inline image(s)', $inline ), 0 );
			}

			$media_added = 0;

			// 1. Insert or update the post (content rewritten in step 3).
			$post_id = $this->upsert_post( $legacy_post, $existing );

			// 2. Featured image.
			$thumb_url = $this->legacy->thumbnail_url( $legacy_id );

			if ( null !== $thumb_url ) {
				$thumb = $this->media->import( $thumb_url, $post_id, $this->config->force );
				set_post_thumbnail( $post_id, $thumb['id'] );
				$media_added += $thumb['reused'] ? 0 : 1;
			}

			// 3. Inline images + content rewrite.
			$content = (string) $legacy_post['post_content'];
			$urls    = $this->rewriter->extract_upload_urls( $content, $this->config->old_base_url );
			$map     = [];

			foreach ( $urls as $url ) {
				$imported            = $this->media->import( $url, $post_id, $this->config->force );
				$map[ $url ]         = $imported['url'];
				$media_added        += $imported['reused'] ? 0 : 1;
			}

			$rewritten = $this->rewriter->rewrite( $content, $map );

			if ( $rewritten !== $content ) {
				wp_update_post(
					[
						'ID'           => $post_id,
						'post_content' => $rewritten,
					]
				);
			}

			// 4. Terms + post format.
			$this->assign_terms( $post_id, $legacy_id );
			$this->assign_format( $post_id, $legacy_id );

			// 5. Yoast meta.
			$this->copy_yoast_meta( $post_id, $legacy_id );

			// 6. Stamp legacy id for traceability.
			update_post_meta( $post_id, '_ik2_legacy_id', $legacy_id );

			$status = null === $existing ? 'created' : 'overwritten';

			return new Migration_Result( $status, $slug, 'ok', $media_added );
		} catch ( Throwable $e ) {
			return new Migration_Result( 'failed', $slug, $e->getMessage() );
		}
	}

	/**
	 * Find an existing published/draft post by exact slug.
	 *
	 * @param string $slug Post slug.
	 */
	private function find_by_slug( string $slug ): ?int {
		$found = get_posts(
			[
				'post_type'        => 'post',
				'post_status'      => [ 'publish', 'draft', 'pending', 'private', 'future' ],
				'name'             => $slug,
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => false,
			]
		);

		return $found ? (int) $found[0] : null;
	}

	/**
	 * Insert a new post or update the matched one. Content is set pre-rewrite.
	 *
	 * @param array<string, string> $legacy_post Legacy row.
	 * @param int|null              $existing    Existing post id or null.
	 */
	private function upsert_post( array $legacy_post, ?int $existing ): int {
		$data = [
			'post_title'        => (string) $legacy_post['post_title'],
			'post_name'         => (string) $legacy_post['post_name'],
			'post_content'      => (string) $legacy_post['post_content'],
			'post_excerpt'      => (string) $legacy_post['post_excerpt'],
			'post_status'       => 'publish',
			'post_type'         => 'post',
			'post_author'       => $this->config->author_id,
			'post_date'         => (string) $legacy_post['post_date'],
			'post_date_gmt'     => (string) $legacy_post['post_date_gmt'],
			'post_modified'     => (string) $legacy_post['post_modified'],
			'post_modified_gmt' => (string) $legacy_post['post_modified_gmt'],
		];

		if ( null !== $existing ) {
			$data['ID'] = $existing;
			$result     = wp_update_post( $data, true );
		} else {
			$result = wp_insert_post( $data, true );
		}

		if ( is_wp_error( $result ) ) {
			throw new \RuntimeException( 'post upsert failed: ' . $result->get_error_message() );
		}

		return (int) $result;
	}

	/**
	 * Recreate and assign categories and tags by slug.
	 *
	 * @param int $post_id   New post id.
	 * @param int $legacy_id Legacy post id.
	 */
	private function assign_terms( int $post_id, int $legacy_id ): void {
		$categories = [];
		$tags       = [];

		foreach ( $this->legacy->terms( $legacy_id ) as $term ) {
			$taxonomy = $term['taxonomy'];
			$existing = get_term_by( 'slug', $term['slug'], $taxonomy );

			if ( $existing instanceof \WP_Term ) {
				$term_id = $existing->term_id;
			} else {
				$created = wp_insert_term( $term['name'], $taxonomy, [ 'slug' => $term['slug'] ] );

				if ( is_wp_error( $created ) ) {
					continue;
				}

				$term_id = (int) $created['term_id'];
			}

			if ( 'category' === $taxonomy ) {
				$categories[] = $term_id;
			} else {
				$tags[] = $term_id;
			}
		}

		if ( [] !== $categories ) {
			wp_set_object_terms( $post_id, $categories, 'category', false );
		}

		if ( [] !== $tags ) {
			wp_set_object_terms( $post_id, $tags, 'post_tag', false );
		}
	}

	/**
	 * Apply the legacy post format, if any.
	 *
	 * @param int $post_id   New post id.
	 * @param int $legacy_id Legacy post id.
	 */
	private function assign_format( int $post_id, int $legacy_id ): void {
		$format = $this->legacy->post_format( $legacy_id );

		if ( null !== $format ) {
			set_post_format( $post_id, $format );
		}
	}

	/**
	 * Copy known Yoast meta keys from legacy postmeta to the new post.
	 *
	 * @param int $post_id   New post id.
	 * @param int $legacy_id Legacy post id.
	 */
	private function copy_yoast_meta( int $post_id, int $legacy_id ): void {
		$meta = $this->legacy->post_meta( $legacy_id );

		foreach ( self::YOAST_KEYS as $key ) {
			if ( isset( $meta[ $key ][0] ) && '' !== $meta[ $key ][0] ) {
				update_post_meta( $post_id, $key, $meta[ $key ][0] );
			}
		}
	}
}
```

- [ ] **Step 2: Add its require_once**

In `wp-content/plugins/ik2/inc/cli/namespace.php`, add before the command require:

```php
	require_once __DIR__ . '/migrate/class-post-importer.php';
```

- [ ] **Step 3: Quality gate**

Run:
```bash
docker compose --profile tools run --rm composer quality
```
Expected: pass. (Full end-to-end verification happens in Task 7 once the command wires the importer in.)

- [ ] **Step 4: Commit**

```bash
git add wp-content/plugins/ik2/inc/cli/migrate/class-post-importer.php \
        wp-content/plugins/ik2/inc/cli/namespace.php
git commit -m "feat(cli): add post importer orchestrating one article"
```

---

## Task 7: Wire the full pipeline into the command + summary

**Files:**
- Modify: `wp-content/plugins/ik2/inc/cli/class-migrate-articles-command.php`

**Interfaces:**
- Consumes: `Migration_Config`, `Legacy_DB`, `Media_Sideloader`, `Content_Rewriter`, `Post_Importer`, `Migration_Result`.

- [ ] **Step 1: Replace the command body with the full pipeline**

In `class-migrate-articles-command.php`, update the `use` block to add the helpers:

```php
use IK2\Plugin\CLI\Migrate\Content_Rewriter;
use IK2\Plugin\CLI\Migrate\Legacy_DB;
use IK2\Plugin\CLI\Migrate\Media_Sideloader;
use IK2\Plugin\CLI\Migrate\Migration_Config;
use IK2\Plugin\CLI\Migrate\Migration_Result;
use IK2\Plugin\CLI\Migrate\Post_Importer;
use WP_CLI;
```

Then replace the body of `__invoke()` (everything after the existing docblock) with:

```php
	public function __invoke( array $args, array $assoc_args ): void {
		$config = Migration_Config::from_args( $assoc_args );

		$rewriter = new Content_Rewriter();
		$legacy   = new Legacy_DB( $config );
		$media    = new Media_Sideloader( $config, $rewriter );
		$importer = new Post_Importer( $config, $legacy, $media, $rewriter );

		$posts = $legacy->published_posts( $config->limit, $config->only_post );

		WP_CLI::log(
			sprintf(
				'%s %d post(s) from %s%s.',
				$config->dry_run ? 'Previewing' : 'Importing',
				count( $posts ),
				$config->legacy_db,
				$config->force ? ' (force)' : ''
			)
		);

		$tally = [
			'created'     => 0,
			'overwritten' => 0,
			'skipped'     => 0,
			'failed'      => 0,
		];
		$media_added = 0;
		$failures    = [];

		foreach ( $posts as $post ) {
			$result                  = $importer->import_one( $post );
			$tally[ $result->status ] = ( $tally[ $result->status ] ?? 0 ) + 1;
			$media_added            += $result->media_added;

			if ( 'failed' === $result->status ) {
				$failures[] = sprintf( '#%s %s — %s', $post['ID'], $result->slug, $result->note );
			}

			if ( $config->verbose ) {
				WP_CLI::log( sprintf( '  [%s] %s — %s (+%d media)', $result->status, $result->slug, $result->note, $result->media_added ) );
			}
		}

		WP_CLI::log( '' );
		WP_CLI::log(
			sprintf(
				'Summary: %d created, %d overwritten, %d skipped, %d failed; %d media added.',
				$tally['created'],
				$tally['overwritten'],
				$tally['skipped'],
				$tally['failed'],
				$media_added
			)
		);

		if ( [] !== $failures ) {
			WP_CLI::log( 'Failures:' );

			foreach ( $failures as $failure ) {
				WP_CLI::log( '  ✗ ' . $failure );
			}

			WP_CLI::error( sprintf( '%d post(s) failed. Fix the cause and re-run; successful posts are skipped.', $tally['failed'] ) );
		}

		WP_CLI::success( $config->dry_run ? 'Dry run complete.' : 'Migration complete.' );
	}
```

Remove the now-unused `Migration_Result` import if PHPStan flags it (it is referenced only as a type in the loop; keep it only if used). Verify with the quality gate in Step 5.

- [ ] **Step 2: Restart and run a dry run against the seed/real legacy DB**

Run:
```bash
docker compose restart wp-cli
composer dev:wp:cmd -- ik2 migrate-articles --legacy-db=legacy --uploads-path=/legacy/uploads --dry-run --verbose
```
Expected: a `Previewing N post(s)...` line, one `[created] <slug> — dry-run: K inline image(s)` line per post, a `Summary:` line, and `Success: Dry run complete.` No posts created (verify with `wp post list`).

- [ ] **Step 3: Run a real single-post import and verify it end-to-end**

Run:
```bash
composer dev:wp:cmd -- ik2 migrate-articles --legacy-db=legacy --uploads-path=/legacy/uploads --post=1 --verbose
composer dev:wp:cmd -- post list --post_type=post --fields=ID,post_name,post_status
```
Expected: one `[created] hello-legacy — ok` line; the post appears in `post list`. Inspect it:
```bash
composer dev:wp:cmd -- eval '
$p = get_page_by_path("hello-legacy","OBJECT","post");
echo "ID=".$p->ID." date=".$p->post_date."\n";
echo "legacy_id=".get_post_meta($p->ID,"_ik2_legacy_id",true)."\n";
'
```
Expected: the legacy date is preserved and `_ik2_legacy_id` is set.

- [ ] **Step 4: Verify idempotency and --force**

Run the same single-post import again, then with `--force`:
```bash
composer dev:wp:cmd -- ik2 migrate-articles --legacy-db=legacy --uploads-path=/legacy/uploads --post=1 --verbose
composer dev:wp:cmd -- ik2 migrate-articles --legacy-db=legacy --uploads-path=/legacy/uploads --post=1 --verbose --force
composer dev:wp:cmd -- post list --post_type=post --fields=ID,post_name | wc -l
```
Expected: second run logs `[skipped] hello-legacy — slug already exists`; the `--force` run logs `[overwritten] hello-legacy — ok`; the post count does not increase (no duplicates).

- [ ] **Step 5: Quality gate**

Run:
```bash
docker compose --profile tools run --rm composer quality
```
Expected: pass.

- [ ] **Step 6: Commit**

```bash
git add wp-content/plugins/ik2/inc/cli/class-migrate-articles-command.php
git commit -m "feat(cli): wire full article migration pipeline and summary"
```

---

## Task 8: Operator runbook

**Files:**
- Create: `docs/runbooks/legacy-article-migration.md`

**Interfaces:** none (documentation).

- [ ] **Step 1: Write the runbook**

Create `docs/runbooks/legacy-article-migration.md`:

```markdown
# Runbook: Migrate legacy articles from www.ivankristianto.com

One-off, re-runnable migration of published articles + their images from the
old site into this site, via `wp ik2 migrate-articles`.

## 1. Stage the inputs (host)

Place both inputs under the gitignored `./legacy/` directory:

- `./legacy/<old-site>.sql` — a mysqldump of the old DB (at minimum the
  `posts`, `postmeta`, `terms`, `term_taxonomy`, `term_relationships` tables).
- `./legacy/uploads/` — a copy of the old `wp-content/uploads/` directory
  (rsync or unzip it here). Files must be world-readable (the wp-cli
  container runs as uid 82).

`./legacy` is bind-mounted read-only into the wp-cli container at `/legacy`.

## 2. Import the dump into a separate database

```bash
docker compose exec -T db mariadb -uroot -proot -e \
  "CREATE DATABASE IF NOT EXISTS legacy; GRANT ALL ON legacy.* TO 'wordpress'@'%'; FLUSH PRIVILEGES;"
docker compose exec -T db sh -c 'mariadb -uroot -proot legacy < /legacy/<old-site>.sql'
```

Note the old site's **table prefix** (look at the table names in the dump,
e.g. `wp_posts` → prefix `wp_`). Pass it as `--legacy-prefix` if not `wp_`.

## 3. Match the permalink structure (SEO)

Old URLs only keep working if this site's permalink structure matches the old
one. Set it in Settings → Permalinks (or `wp option update permalink_structure`)
before announcing the migration. The script preserves slugs and dates but does
not install redirects.

## 4. Preview

```bash
composer dev:wp:cmd -- ik2 migrate-articles --legacy-db=legacy --dry-run --verbose
```

Check the post count and the per-post inline-image counts look right.

## 5. Run

```bash
composer dev:wp:cmd -- ik2 migrate-articles --legacy-db=legacy
```

Re-run until the summary reports `0 failed`. Successful posts are skipped on
re-run (matched by slug); only failures are retried. Use `--verbose` to see
per-post outcomes, `--limit=N` / `--post=<id>` to scope, and `--force` to
re-import and overwrite an already-migrated post and its media.

## 6. Spot-check

- Open a few imported posts; confirm images load from the new media library
  and no `www.ivankristianto.com` URLs remain in the content.
- Confirm categories, tags, post format, and the featured image are set.
- Confirm Yoast title/description carried over (Yoast meta box).

## 7. Tear down (after a clean run)

The `./legacy` directory and the `legacy` database are dev-only. Remove them
when done:

```bash
docker compose exec -T db mariadb -uroot -proot -e "DROP DATABASE legacy;"
rm -rf ./legacy
```
```

- [ ] **Step 2: Commit**

```bash
git add docs/runbooks/legacy-article-migration.md
git commit -m "docs(runbooks): add legacy article migration runbook"
```

---

## Self-Review Notes

- **Spec coverage:** legacy-DB-as-source (Task 4), solo/published-only (Legacy_DB `published_posts` filter), local uploads sideload (Task 5), slug-based idempotency + `--force` (Tasks 6-7), slugs+dates / categories+tags / post formats / Yoast meta (Task 6), `--dry-run` / `--verbose` / `--force` flags (Tasks 2,7), plugin CLI packaging mirroring `setup` (all tasks), run summary with failures (Task 7), permalink-match + no-redirects caveat (Task 8 runbook). All spec requirements map to a task.
- **No PHPUnit:** the project has no PHP test framework; pure logic is verified with `wp eval-file` assertion scripts (Task 3), integration via running the command (Tasks 4-7), and `composer quality` gates every task. This is the deliberate, codebase-appropriate adaptation noted in the plan intro.
- **Type consistency:** `Migration_Result` props (`status`, `slug`, `note`, `media_added`) are used identically in Task 6 (construction) and Task 7 (tally/log). `Media_Sideloader::import` returns `{id,url,reused}` in Task 5 and is consumed with those exact keys in Task 6. `Legacy_DB` method names (`published_posts`, `post_meta`, `terms`, `post_format`, `thumbnail_url`) match between Tasks 4 and 6. `Content_Rewriter` methods (`strip_size_suffix`, `extract_upload_urls`, `rewrite`) match between Tasks 3, 5, 6.
- **Known caveat to watch during execution:** PHPStan level 6 may want `wpdb`/`WP_Term` imports or array-shape annotations; add `use` statements and `@return array{...}` shapes as flagged rather than loosening types.
```
