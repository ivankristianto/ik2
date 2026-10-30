# `wp ik2 setup` CLI Command Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** A modular `wp ik2 setup [--force]` command that creates the pages the theme templates hardcode links to, sets permalinks to `/%postname%/`, sets the timezone to Asia/Jakarta, disables registration, and prints a ✓/✗ checklist.

**Architecture:** A `Setup_Command` runner holds an ordered registry of `Setup_Step` implementations under `inc/cli/setup/`. Each step returns `Check_Result[]`; the runner prints them grouped by step label and exits non-zero if any check failed. Adding a future step = one new class + one registry entry.

**Tech Stack:** PHP 8.4, WP-CLI, WPCS (PHPCS) + PHPStan level 6. No PHP test harness exists in this repo — verification is running the command against the dev stack plus the quality gates.

**Spec:** `docs/superpowers/specs/2026-06-06-ik2-setup-cli-design.md`

**IMPORTANT — no commits:** The user explicitly instructed that nothing is committed in this session. Do NOT run `git add` or `git commit` at any point. Leave all changes in the working tree.

**Conventions that apply to every task:**
- `declare(strict_types=1)`, `defined( 'ABSPATH' ) || exit;` after the namespace, file docblock with `@package IK2\Plugin` — mirror `wp-content/plugins/ik2/inc/cli/class-stats-command.php`.
- WPCS style: tabs, Yoda conditions, snake_case methods, spaces inside parens, aligned `=>` arrays, `array()` syntax (not `[]`).
- Named methods only — no closures (project convention).
- `wp-content/plugins/` is bind-mounted into the dev containers, so file edits are live immediately for wp-cli.

---

### Task 1: `Check_Result` value object + `Setup_Step` interface

**Files:**
- Create: `wp-content/plugins/ik2/inc/cli/setup/class-check-result.php`
- Create: `wp-content/plugins/ik2/inc/cli/setup/interface-setup-step.php`

- [ ] **Step 1: Create `class-check-result.php`**

```php
<?php
/**
 * Value object describing the outcome of a single setup check.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Setup;

defined( 'ABSPATH' ) || exit;

/**
 * Outcome of one setup check: a label, a success flag, and a short note.
 */
class Check_Result {

	/**
	 * Check label, e.g. a page slug or option name.
	 *
	 * @var string
	 */
	public string $label;

	/**
	 * Whether the check succeeded.
	 *
	 * @var bool
	 */
	public bool $success;

	/**
	 * Short human note, e.g. "created" or "already set".
	 *
	 * @var string
	 */
	public string $note;

	/**
	 * Constructor.
	 *
	 * @param string $label   Check label, e.g. a page slug or option name.
	 * @param bool   $success Whether the check succeeded.
	 * @param string $note    Short human note, e.g. "created" or "already set".
	 */
	public function __construct( string $label, bool $success, string $note ) {
		$this->label   = $label;
		$this->success = $success;
		$this->note    = $note;
	}
}
```

- [ ] **Step 2: Create `interface-setup-step.php`**

```php
<?php
/**
 * Contract for a single `wp ik2 setup` step.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Setup;

defined( 'ABSPATH' ) || exit;

/**
 * One unit of site setup. Steps must be idempotent: running them twice
 * without --force must not change state reported as already correct.
 */
interface Setup_Step {

	/**
	 * Section heading shown above this step's checks, e.g. "Pages".
	 */
	public function label(): string;

	/**
	 * Apply the step and report one result per check performed.
	 *
	 * Implementations must catch their own failures and turn them into
	 * failed Check_Result entries — never let an error escape.
	 *
	 * @param bool $force Re-apply state even where something already exists.
	 * @return array<int, Check_Result>
	 */
	public function run( bool $force ): array;
}
```

- [ ] **Step 3: Syntax-check both files**

Run:
```bash
docker compose exec app php -l /var/www/html/wp-content/plugins/ik2/inc/cli/setup/class-check-result.php
docker compose exec app php -l /var/www/html/wp-content/plugins/ik2/inc/cli/setup/interface-setup-step.php
```
Expected: `No syntax errors detected` for both. (If the stack is not running, start it with `composer dev` first.)

---

### Task 2: `Pages_Step`

**Files:**
- Create: `wp-content/plugins/ik2/inc/cli/setup/class-pages-step.php`

The page manifest is derived from hardcoded links in `wp-content/themes/ik2/parts/header.html` (nav: /articles /projects /speaking /about /contact, CTA: /resume), `parts/footer.html` (/privacy), and page templates.

- [ ] **Step 1: Create `class-pages-step.php`**

```php
<?php
/**
 * Setup step: create the pages the theme templates hardcode links to.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Setup;

defined( 'ABSPATH' ) || exit;

/**
 * Ensures every page the theme links to exists and is published.
 *
 * The manifest mirrors the hardcoded links in the theme's templates and
 * parts (header nav, footer meta, in-template CTAs). Existing pages are
 * skipped unless --force, which re-applies title/slug/status in place so
 * the page ID stays stable.
 */
class Pages_Step implements Setup_Step {

	/**
	 * Slug => title manifest of pages the theme templates link to.
	 */
	private const PAGES = array(
		'articles' => 'Articles',
		'projects' => 'Projects',
		'speaking' => 'Speaking',
		'about'    => 'About',
		'contact'  => 'Contact',
		'resume'   => 'Resume',
		'privacy'  => 'Privacy',
	);

	/**
	 * Section heading shown above this step's checks.
	 */
	public function label(): string {
		return 'Pages';
	}

	/**
	 * Ensure each manifest page exists, one result per page.
	 *
	 * @param bool $force Re-apply title/slug/status on existing pages.
	 * @return array<int, Check_Result>
	 */
	public function run( bool $force ): array {
		$results = array();

		foreach ( self::PAGES as $slug => $title ) {
			$results[] = $this->ensure_page( $slug, $title, $force );
		}

		return $results;
	}

	/**
	 * Create, skip, or (with --force) re-apply a single page.
	 *
	 * @param string $slug  Page slug.
	 * @param string $title Page title.
	 * @param bool   $force Re-apply state on an existing page.
	 */
	private function ensure_page( string $slug, string $title, bool $force ): Check_Result {
		$existing = get_page_by_path( $slug, OBJECT, 'page' );

		if ( null === $existing ) {
			return $this->create_page( $slug, $title );
		}

		if ( ! $force ) {
			$note = 'publish' === $existing->post_status
				? 'exists, skipped'
				: sprintf( 'exists (%s), skipped', $existing->post_status );

			return new Check_Result( $slug, true, $note );
		}

		return $this->reapply_page( $existing->ID, $slug, $title );
	}

	/**
	 * Insert a new published page with empty content (the block template
	 * renders the layout).
	 *
	 * @param string $slug  Page slug.
	 * @param string $title Page title.
	 */
	private function create_page( string $slug, string $title ): Check_Result {
		$created = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => '',
			),
			true
		);

		if ( is_wp_error( $created ) ) {
			return new Check_Result( $slug, false, $created->get_error_message() );
		}

		return new Check_Result( $slug, true, 'created' );
	}

	/**
	 * Re-apply title, slug, and published status in place. The page ID
	 * stays stable so internal links and menus keep working; a trashed
	 * page is restored by forcing its status back to publish.
	 *
	 * @param int    $page_id Existing page ID.
	 * @param string $slug    Page slug.
	 * @param string $title   Page title.
	 */
	private function reapply_page( int $page_id, string $slug, string $title ): Check_Result {
		$updated = wp_update_post(
			array(
				'ID'          => $page_id,
				'post_title'  => $title,
				'post_name'   => $slug,
				'post_status' => 'publish',
			),
			true
		);

		if ( is_wp_error( $updated ) ) {
			return new Check_Result( $slug, false, $updated->get_error_message() );
		}

		return new Check_Result( $slug, true, 'updated' );
	}
}
```

- [ ] **Step 2: Syntax-check**

Run: `docker compose exec app php -l /var/www/html/wp-content/plugins/ik2/inc/cli/setup/class-pages-step.php`
Expected: `No syntax errors detected`

---

### Task 3: `Permalinks_Step`, `Timezone_Step`, `Registration_Step`

**Files:**
- Create: `wp-content/plugins/ik2/inc/cli/setup/class-permalinks-step.php`
- Create: `wp-content/plugins/ik2/inc/cli/setup/class-timezone-step.php`
- Create: `wp-content/plugins/ik2/inc/cli/setup/class-registration-step.php`

- [ ] **Step 1: Create `class-permalinks-step.php`**

```php
<?php
/**
 * Setup step: pretty permalinks.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Setup;

defined( 'ABSPATH' ) || exit;

/**
 * Sets the permalink structure to /%postname%/ and flushes rewrite rules.
 */
class Permalinks_Step implements Setup_Step {

	/**
	 * Target permalink structure.
	 */
	private const STRUCTURE = '/%postname%/';

	/**
	 * Section heading shown above this step's checks.
	 */
	public function label(): string {
		return 'Permalinks';
	}

	/**
	 * Apply the permalink structure if it differs.
	 *
	 * @param bool $force Unused; this step is an idempotent option write.
	 * @return array<int, Check_Result>
	 */
	public function run( bool $force ): array {
		$current = (string) get_option( 'permalink_structure', '' );

		if ( self::STRUCTURE === $current ) {
			return array( new Check_Result( self::STRUCTURE, true, 'already set' ) );
		}

		global $wp_rewrite;

		$wp_rewrite->set_permalink_structure( self::STRUCTURE );
		flush_rewrite_rules();

		return array( new Check_Result( self::STRUCTURE, true, 'set' ) );
	}
}
```

- [ ] **Step 2: Create `class-timezone-step.php`**

```php
<?php
/**
 * Setup step: site timezone.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Setup;

defined( 'ABSPATH' ) || exit;

/**
 * Sets the site timezone to Asia/Jakarta.
 */
class Timezone_Step implements Setup_Step {

	/**
	 * Target timezone identifier.
	 */
	private const TIMEZONE = 'Asia/Jakarta';

	/**
	 * Section heading shown above this step's checks.
	 */
	public function label(): string {
		return 'Timezone';
	}

	/**
	 * Apply the timezone if it differs; clear any manual GMT offset so
	 * the named timezone takes precedence.
	 *
	 * @param bool $force Unused; this step is an idempotent option write.
	 * @return array<int, Check_Result>
	 */
	public function run( bool $force ): array {
		if ( self::TIMEZONE === get_option( 'timezone_string' ) ) {
			return array( new Check_Result( self::TIMEZONE, true, 'already set' ) );
		}

		update_option( 'timezone_string', self::TIMEZONE );
		update_option( 'gmt_offset', '' );

		return array( new Check_Result( self::TIMEZONE, true, 'set' ) );
	}
}
```

- [ ] **Step 3: Create `class-registration-step.php`**

```php
<?php
/**
 * Setup step: registration policy.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Setup;

defined( 'ABSPATH' ) || exit;

/**
 * Disables open user registration.
 */
class Registration_Step implements Setup_Step {

	/**
	 * Section heading shown above this step's checks.
	 */
	public function label(): string {
		return 'Registration';
	}

	/**
	 * Turn off users_can_register if it is on.
	 *
	 * @param bool $force Unused; this step is an idempotent option write.
	 * @return array<int, Check_Result>
	 */
	public function run( bool $force ): array {
		if ( ! get_option( 'users_can_register' ) ) {
			return array( new Check_Result( 'users_can_register', true, 'already off' ) );
		}

		update_option( 'users_can_register', 0 );

		return array( new Check_Result( 'users_can_register', true, 'turned off' ) );
	}
}
```

- [ ] **Step 4: Syntax-check all three**

Run:
```bash
docker compose exec app php -l /var/www/html/wp-content/plugins/ik2/inc/cli/setup/class-permalinks-step.php
docker compose exec app php -l /var/www/html/wp-content/plugins/ik2/inc/cli/setup/class-timezone-step.php
docker compose exec app php -l /var/www/html/wp-content/plugins/ik2/inc/cli/setup/class-registration-step.php
```
Expected: `No syntax errors detected` ×3

---

### Task 4: `Setup_Command` + registration in `namespace.php`

**Files:**
- Create: `wp-content/plugins/ik2/inc/cli/class-setup-command.php`
- Modify: `wp-content/plugins/ik2/inc/cli/namespace.php`

- [ ] **Step 1: Create `class-setup-command.php`**

```php
<?php
/**
 * `wp ik2 setup` — provision the site to match the theme's expectations.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI;

use IK2\Plugin\CLI\Setup\Pages_Step;
use IK2\Plugin\CLI\Setup\Permalinks_Step;
use IK2\Plugin\CLI\Setup\Registration_Step;
use IK2\Plugin\CLI\Setup\Setup_Step;
use IK2\Plugin\CLI\Setup\Timezone_Step;
use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Runs an ordered list of setup steps and prints a ✓/✗ checklist.
 *
 * To add a step, create a class implementing Setup_Step under inc/cli/setup/
 * and append an instance in steps().
 */
class Setup_Command {

	/**
	 * Sets up the site: pages, permalinks, timezone, and registration.
	 *
	 * Creates the pages the theme templates link to, sets the permalink
	 * structure to /%postname%/, sets the timezone to Asia/Jakarta, and
	 * disables open registration. Existing pages are skipped unless
	 * --force is given.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Re-apply title, slug, and published status on pages that already
	 * exist (the page ID is preserved).
	 *
	 * ## EXAMPLES
	 *
	 *     # Set up the site, skipping pages that already exist.
	 *     $ wp ik2 setup
	 *
	 *     # Re-apply page state on existing pages too.
	 *     $ wp ik2 setup --force
	 *
	 * @param array<int, string>    $args       Positional arguments (unused).
	 * @param array<string, string> $assoc_args Associative arguments.
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		$force = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'force', false );

		$ok     = 0;
		$failed = 0;

		foreach ( $this->steps() as $step ) {
			WP_CLI::log( $step->label() );

			foreach ( $step->run( $force ) as $result ) {
				if ( $result->success ) {
					++$ok;
				} else {
					++$failed;
				}

				WP_CLI::log( sprintf( '  %s %s — %s', $result->success ? '✓' : '✗', $result->label, $result->note ) );
			}
		}

		$summary = sprintf( 'Setup complete: %d ok, %d failed.', $ok, $failed );

		if ( $failed > 0 ) {
			WP_CLI::error( $summary );
		}

		WP_CLI::success( $summary );
	}

	/**
	 * The ordered step registry. Append new steps here.
	 *
	 * @return array<int, Setup_Step>
	 */
	private function steps(): array {
		return array(
			new Pages_Step(),
			new Permalinks_Step(),
			new Timezone_Step(),
			new Registration_Step(),
		);
	}
}
```

- [ ] **Step 2: Register the command in `namespace.php`**

Modify `wp-content/plugins/ik2/inc/cli/namespace.php` — the `bootstrap()` function currently reads:

```php
function bootstrap(): void {
	if ( ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
		return;
	}

	require_once __DIR__ . '/class-stats-command.php';

	\WP_CLI::add_command( 'ik2 stats', Stats_Command::class );
}
```

Replace with:

```php
function bootstrap(): void {
	if ( ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
		return;
	}

	require_once __DIR__ . '/class-stats-command.php';
	require_once __DIR__ . '/setup/class-check-result.php';
	require_once __DIR__ . '/setup/interface-setup-step.php';
	require_once __DIR__ . '/setup/class-pages-step.php';
	require_once __DIR__ . '/setup/class-permalinks-step.php';
	require_once __DIR__ . '/setup/class-timezone-step.php';
	require_once __DIR__ . '/setup/class-registration-step.php';
	require_once __DIR__ . '/class-setup-command.php';

	\WP_CLI::add_command( 'ik2 stats', Stats_Command::class );
	\WP_CLI::add_command( 'ik2 setup', Setup_Command::class );
}
```

- [ ] **Step 3: Verify the command is registered**

Run: `composer dev:wp:cmd -- help ik2 setup`
Expected: the help screen showing the `--force` option and examples (no PHP fatals).

---

### Task 5: Functional verification on the dev stack

**Files:** none (verification only)

- [ ] **Step 1: Run the command without --force**

Run: `composer dev:wp:cmd -- ik2 setup`
Expected output shape (all 7 manifest pages already exist on this dev DB):

```
Pages
  ✓ articles — exists, skipped
  ✓ projects — exists, skipped
  ✓ speaking — exists, skipped
  ✓ about — exists, skipped
  ✓ contact — exists, skipped
  ✓ resume — exists, skipped
  ✓ privacy — exists, skipped
Permalinks
  ✓ /%postname%/ — already set
Timezone
  ✓ Asia/Jakarta — already set
Registration
  ✓ users_can_register — already off
Success: Setup complete: 10 ok, 0 failed.
```

Exit code 0.

- [ ] **Step 2: Exercise the create path**

Delete one page, re-run, confirm it is recreated:

```bash
composer dev:wp:cmd -- post delete $(composer dev:wp:cmd -- post list --post_type=page --name=privacy --field=ID) --force
composer dev:wp:cmd -- ik2 setup
```
Expected: `✓ privacy — created` in the output; other pages still `exists, skipped`.
Then confirm: `composer dev:wp:cmd -- post list --post_type=page --name=privacy --fields=ID,post_status` shows a published `privacy` page.

- [ ] **Step 3: Exercise --force**

Run: `composer dev:wp:cmd -- ik2 setup --force`
Expected: all 7 pages report `✓ <slug> — updated`; option steps unchanged (`already set` / `already off`). Exit code 0.

Verify page IDs were preserved (not recreated): `composer dev:wp:cmd -- post list --post_type=page --name=about --field=ID` still returns `46`.

---

### Task 6: Quality gates

**Files:** none (verification only)

- [ ] **Step 1: PHP gates**

Run: `docker compose --profile tools run --rm composer quality`
Expected: PHPCS reports no errors and PHPStan reports `No errors`. If PHPCS flags fixable style issues, run `docker compose --profile tools run --rm composer lint:fix` and re-run the gate; fix any remaining issues by hand. Do not add `phpcs:disable` comments.

(No JS/CSS changed, so `pnpm lint` is not required.)

**Reminder: do NOT commit anything.**
