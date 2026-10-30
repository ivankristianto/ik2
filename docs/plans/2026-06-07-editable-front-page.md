# Editable Front Page Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

> **NO COMMITS.** Per explicit user instruction for this run, do NOT run `git commit` (or `git add`) at any point. Leave all changes in the working tree. Commit steps normally present in plans are intentionally omitted.

**Goal:** Move homepage section control from the hardcoded `front-page.html` block template into an editable static "Home" page, provisioned by `wp ik2 setup`.

**Architecture:** `front-page.html` keeps the header/main/footer chrome and renders `core/post-content`. Two new theme patterns make every homepage section re-insertable (`ik2/home-projects-preview` fills an existing gap; `ik2/home-page` is the full six-section composition, offered as a starter pattern). A new `Home_Page_Step` in the ik2 plugin's setup CLI creates the Home page seeded from the `ik2/home-page` pattern and converges `show_on_front` / `page_on_front`. `Reading_Step` stops pinning `show_on_front = posts`.

**Tech Stack:** WordPress block theme (HTML block templates + PHP pattern files), WP-CLI command classes in the first-party ik2 plugin (`IK2\Plugin\CLI\Setup` namespace, WPCS + PHPStan level 6), Node built-in `assert` structure test, Docker dev stack.

**Spec:** `docs/superpowers/specs/2026-06-07-editable-front-page-design.md`

**Repo facts the executor needs:**

- All paths are relative to the repo root `/Users/ivan/Works/ik2org`.
- `wp-content/themes` and `wp-content/plugins` are bind-mounted into the dev containers — file edits are visible immediately, **but PHP opcache caches files**. After adding new PHP files, run `docker compose restart app wp-cli` before any runtime verification.
- The structure test is plain top-level assertions, run directly: `node tests/home-patterns-structure.test.mjs`. It is not wired into package.json or CI.
- PHP style: tabs, `array()` syntax, Yoda conditions, snake_case methods, one class per `class-*.php` file, docblock on every file/class/method, `declare(strict_types=1)`, `defined( 'ABSPATH' ) || exit;`.
- Quality gates run inside Docker: `docker compose --profile tools run --rm composer quality`.

---

### Task 1: Rewrite the structure test (failing first)

The existing test asserts `front-page.html` renders each `ik2/home-*` block directly. The new contract: `front-page.html` renders `core/post-content` and **no** home blocks; six thin-wrapper patterns exist (adding `home-projects-preview.php`); a new `home-page.php` pattern composes all six sections in template order and is registered as a page starter pattern.

**Files:**
- Modify: `tests/home-patterns-structure.test.mjs` (full rewrite)

- [ ] **Step 1: Replace the entire content of `tests/home-patterns-structure.test.mjs` with:**

```js
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const frontPage = readFileSync(
	'wp-content/themes/ik2/templates/front-page.html',
	'utf8'
);

assert.match(
	frontPage,
	/<!-- wp:post-content\b/,
	'Expected front-page.html to render core/post-content so the static Home page owns the homepage sections.'
);

assert.doesNotMatch(
	frontPage,
	/<!-- wp:ik2\/home-/,
	'Expected front-page.html to contain no hardcoded ik2/home-* blocks; the Home page content decides which sections render.'
);

const patternFiles = [
	'wp-content/themes/ik2/patterns/home-evergreen-guides.php',
	'wp-content/themes/ik2/patterns/home-featured-topics.php',
	'wp-content/themes/ik2/patterns/home-hero.php',
	'wp-content/themes/ik2/patterns/home-latest-notes.php',
	'wp-content/themes/ik2/patterns/home-projects-preview.php',
	'wp-content/themes/ik2/patterns/home-speaking-preview.php',
];

const blockNames = [
	'ik2/home-evergreen-guides',
	'ik2/home-featured-topics',
	'ik2/home-hero',
	'ik2/home-latest-notes',
	'ik2/home-projects-preview',
	'ik2/home-speaking-preview',
];

for ( let index = 0; index < patternFiles.length; index += 1 ) {
	const pattern = readFileSync( patternFiles[ index ], 'utf8' );
	const blockName = blockNames[ index ];

	assert.match(
		pattern,
		new RegExp( `<!-- wp:${ blockName.replace( '/', '\\/' ) }\\s*\\/-->` ),
		`Expected ${ patternFiles[ index ] } to be a thin wrapper around ${ blockName }.`
	);

	assert.doesNotMatch(
		pattern,
		/<!-- wp:html\b|WP_Query|get_term_by|get_post_meta|file_exists|wp_count_posts|taxQuery/s,
		`Expected ${ patternFiles[ index ] } to avoid dynamic logic and Custom HTML once ${ blockName } exists.`
	);
}

const homePagePattern = readFileSync(
	'wp-content/themes/ik2/patterns/home-page.php',
	'utf8'
);

assert.match(
	homePagePattern,
	/^ \* Slug: ik2\/home-page$/m,
	'Expected the home-page pattern to register as ik2/home-page.'
);

assert.match(
	homePagePattern,
	/^ \* Block Types: core\/post-content$/m,
	'Expected the home-page pattern to declare Block Types: core/post-content so WordPress offers it as a page starter pattern.'
);

assert.match(
	homePagePattern,
	/^ \* Post Types: page$/m,
	'Expected the home-page pattern to declare Post Types: page.'
);

const sectionOrder = [
	'ik2/home-hero',
	'ik2/home-featured-topics',
	'ik2/home-evergreen-guides',
	'ik2/home-latest-notes',
	'ik2/home-projects-preview',
	'ik2/home-speaking-preview',
];

let cursor = -1;

for ( const blockName of sectionOrder ) {
	const at = homePagePattern.indexOf( `<!-- wp:${ blockName } /-->` );

	assert.ok(
		at > cursor,
		`Expected ${ blockName } to appear in patterns/home-page.php after the previous section (template order).`
	);

	cursor = at;
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `node tests/home-patterns-structure.test.mjs`
Expected: FAIL — `AssertionError` on the first assertion (`front-page.html` does not yet contain `wp:post-content`). Anything other than an assertion failure (e.g. a syntax error) means Step 1 was typed wrong.

---

### Task 2: Template change + two new patterns (make the test pass)

**Files:**
- Modify: `wp-content/themes/ik2/templates/front-page.html` (full rewrite, 15 lines → 11 lines)
- Create: `wp-content/themes/ik2/patterns/home-projects-preview.php`
- Create: `wp-content/themes/ik2/patterns/home-page.php`

- [ ] **Step 1: Replace the entire content of `wp-content/themes/ik2/templates/front-page.html` with:**

```html
<!-- wp:template-part {"slug":"header","tagName":"header"} /-->

<!-- wp:group {"tagName":"main","layout":{"type":"default"}} -->
<main class="wp-block-group">
	<!-- wp:post-content /-->
</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
```

(Indentation inside `<main>` is one tab, matching the current file.)

- [ ] **Step 2: Create `wp-content/themes/ik2/patterns/home-projects-preview.php` with:**

```php
<?php
/**
 * Title: Home — Projects Preview
 * Slug: ik2/home-projects-preview
 * Categories: ik2-home
 * Description: Curated grid of Projects shown on the homepage.
 *
 * @package IK2
 */

?>
<!-- wp:ik2/home-projects-preview /-->
```

(The `—` in Title is an em dash, matching the sibling pattern files. The block title in `blocks/home-projects-preview/block.json` is `Home — Projects Preview`.)

- [ ] **Step 3: Create `wp-content/themes/ik2/patterns/home-page.php` with:**

```php
<?php
/**
 * Title: Home — Full page
 * Slug: ik2/home-page
 * Categories: ik2-home
 * Block Types: core/post-content
 * Post Types: page
 * Description: The full homepage composition: hero, featured topics, evergreen guides, latest notes, projects, and speaking.
 *
 * @package IK2
 */

?>
<!-- wp:ik2/home-hero /-->
<!-- wp:ik2/home-featured-topics /-->
<!-- wp:ik2/home-evergreen-guides /-->
<!-- wp:ik2/home-latest-notes /-->
<!-- wp:ik2/home-projects-preview /-->
<!-- wp:ik2/home-speaking-preview /-->
```

- [ ] **Step 4: Run the structure test to verify it passes**

Run: `node tests/home-patterns-structure.test.mjs`
Expected: exits 0, no output.

---

### Task 3: `Home_Page_Step` CLI setup step

A new setup step that creates a published "Home" page (slug `home`) seeded from the registered `ik2/home-page` pattern, then converges `show_on_front = page` and `page_on_front`. Existing page content is never touched (it is editorial). Modeled on `Privacy_Page_Step` (`wp-content/plugins/ik2/inc/cli/setup/class-privacy-page-step.php`) — read it first for the house style.

There is no PHP unit test infrastructure in this repo; this task is verified at runtime in Task 5 and by static analysis in Task 6.

**Files:**
- Create: `wp-content/plugins/ik2/inc/cli/setup/class-home-page-step.php`
- Modify: `wp-content/plugins/ik2/inc/cli/namespace.php` (one `require_once` line)

- [ ] **Step 1: Create `wp-content/plugins/ik2/inc/cli/setup/class-home-page-step.php` with:**

```php
<?php
/**
 * Setup step: provision the static Home front page.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Setup;

use WP_Block_Patterns_Registry;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Creates a published "Home" page seeded from the theme's ik2/home-page
 * block pattern and points show_on_front / page_on_front at it, so the
 * homepage sections are editable per page instead of hardcoded in the
 * front-page.html template. An existing page's content is never touched:
 * it is editorial. A page_on_front pointing at a different published
 * page is treated as a deliberate choice and only overridden with
 * --force, mirroring Privacy_Page_Step.
 */
class Home_Page_Step implements Setup_Step {

	/**
	 * Slug of the static front page.
	 */
	private const SLUG = 'home';

	/**
	 * Pattern whose content seeds a newly created Home page.
	 */
	private const PATTERN = 'ik2/home-page';

	/**
	 * Section heading shown above this step's checks.
	 */
	public function label(): string {
		return 'Home page';
	}

	/**
	 * Ensure the Home page exists and the front-page options point at it.
	 *
	 * @param bool $force Publish a non-published Home page and override a
	 *                    page_on_front pointing at a different published page.
	 * @return array<int, Check_Result>
	 */
	public function run( bool $force ): array {
		$results = array();
		$page    = get_page_by_path( self::SLUG, OBJECT, 'page' );

		if ( $page instanceof WP_Post ) {
			$result = $this->ensure_published( $page, $force );

			if ( ! $result->success ) {
				return array( $result );
			}

			$results[] = $result;
			$home_id   = $page->ID;
		} else {
			$content = $this->pattern_content();

			if ( null === $content ) {
				return array( new Check_Result( self::SLUG, false, sprintf( 'pattern %s not found (is the ik2 theme installed?)', self::PATTERN ) ) );
			}

			$home_id = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => 'Home',
					'post_name'    => self::SLUG,
					'post_content' => $content,
				),
				true
			);

			if ( is_wp_error( $home_id ) ) {
				return array( new Check_Result( self::SLUG, false, $home_id->get_error_message() ) );
			}

			$results[] = new Check_Result( self::SLUG, true, 'created' );
		}

		$results[] = $this->converge_show_on_front();
		$results[] = $this->converge_page_on_front( (int) $home_id, $force );

		return $results;
	}

	/**
	 * Report an existing Home page, publishing it when forced.
	 *
	 * @param WP_Post $page  The existing Home page.
	 * @param bool    $force Publish a non-published page instead of failing.
	 */
	private function ensure_published( WP_Post $page, bool $force ): Check_Result {
		if ( 'publish' === $page->post_status ) {
			return new Check_Result( self::SLUG, true, 'exists, content untouched' );
		}

		if ( ! $force ) {
			return new Check_Result( self::SLUG, false, sprintf( 'exists with status "%s" — publish it or re-run with --force', $page->post_status ) );
		}

		$updated = wp_update_post(
			array(
				'ID'          => $page->ID,
				'post_status' => 'publish',
			),
			true
		);

		if ( is_wp_error( $updated ) ) {
			return new Check_Result( self::SLUG, false, $updated->get_error_message() );
		}

		return new Check_Result( self::SLUG, true, 'published' );
	}

	/**
	 * Converge show_on_front to "page".
	 */
	private function converge_show_on_front(): Check_Result {
		if ( 'page' === get_option( 'show_on_front' ) ) {
			return new Check_Result( 'show_on_front', true, 'already set' );
		}

		update_option( 'show_on_front', 'page' );

		return new Check_Result( 'show_on_front', true, 'set to "page"' );
	}

	/**
	 * Converge page_on_front to the Home page ID.
	 *
	 * @param int  $home_id The Home page ID.
	 * @param bool $force   Override a different published page too.
	 */
	private function converge_page_on_front( int $home_id, bool $force ): Check_Result {
		$current_id = (int) get_option( 'page_on_front' );

		if ( $current_id === $home_id ) {
			return new Check_Result( 'page_on_front', true, 'already set' );
		}

		$current = $current_id > 0 ? get_post( $current_id ) : null;

		if ( ! $force && $current instanceof WP_Post && 'publish' === $current->post_status ) {
			return new Check_Result( 'page_on_front', true, sprintf( 'set to page %d, skipped', $current_id ) );
		}

		update_option( 'page_on_front', $home_id );

		return new Check_Result( 'page_on_front', true, 'set to /' . self::SLUG );
	}

	/**
	 * Content of the ik2/home-page pattern.
	 *
	 * Prefers the block pattern registry. Falls back to rendering the
	 * theme's pattern file directly: when Theme_Step activates the theme
	 * earlier in this same process, _register_theme_block_patterns() has
	 * already run at init against the previous theme, so the registry
	 * does not know ik2 patterns yet.
	 *
	 * @return string|null Pattern markup, or null when unavailable.
	 */
	private function pattern_content(): ?string {
		$pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( self::PATTERN );

		if ( is_array( $pattern ) && isset( $pattern['content'] ) && '' !== trim( (string) $pattern['content'] ) ) {
			return trim( (string) $pattern['content'] );
		}

		$file = get_stylesheet_directory() . '/patterns/home-page.php';

		if ( ! is_readable( $file ) ) {
			return null;
		}

		ob_start();
		include $file;
		$content = trim( (string) ob_get_clean() );

		return '' !== $content ? $content : null;
	}
}
```

- [ ] **Step 2: Register the file in `wp-content/plugins/ik2/inc/cli/namespace.php`**

In `bootstrap()`, after the line:

```php
	require_once __DIR__ . '/setup/class-privacy-page-step.php';
```

add:

```php
	require_once __DIR__ . '/setup/class-home-page-step.php';
```

- [ ] **Step 3: PHP syntax check**

Run: `docker compose --profile tools run --rm composer exec -- php -l wp-content/plugins/ik2/inc/cli/setup/class-home-page-step.php`

If the composer container can't run `php -l` that way, use: `docker compose run --rm wp-cli php -l /var/www/app/wp-content/plugins/ik2/inc/cli/setup/class-home-page-step.php` — or rely on Task 5's runtime check.
Expected: `No syntax errors detected`.

---

### Task 4: Wire the step into the command; unpin `show_on_front` in `Reading_Step`

**Files:**
- Modify: `wp-content/plugins/ik2/inc/cli/class-setup-command.php` (use statement, steps registry, three docblock spots)
- Modify: `wp-content/plugins/ik2/inc/cli/setup/class-reading-step.php` (drop option, fix docblock)

- [ ] **Step 1: Add the use statement in `class-setup-command.php`**

The use block is alphabetical. After:

```php
use IK2\Plugin\CLI\Setup\Discussion_Step;
```

add:

```php
use IK2\Plugin\CLI\Setup\Home_Page_Step;
```

- [ ] **Step 2: Register the step instance**

In `steps()`, after `new Privacy_Page_Step(),` add `new Home_Page_Step(),`:

```php
			new Pages_Step(),
			new Privacy_Page_Step(),
			new Home_Page_Step(),
			new Permalinks_Step(),
```

(Before `Permalinks_Step` so the `page_on_front` change is in place when permalinks flush in a fresh process. The step key derives from the label: "Home page" → `home-page`.)

- [ ] **Step 3: Update the command docblock — long description**

Replace the sentence fragment in the `__invoke` docblock:

```
	 * Activates the ik2 theme and the composer-installed plugins, creates
	 * the pages the theme templates link to, designates the privacy page,
	 * converges permalinks / timezone / date formats / reading /
```

with:

```
	 * Activates the ik2 theme and the composer-installed plugins, creates
	 * the pages the theme templates link to, designates the privacy page,
	 * provisions the static Home front page, converges permalinks /
	 * timezone / date formats / reading /
```

- [ ] **Step 4: Update the command docblock — `--force` and `--only` descriptions**

Replace:

```
	 * [--force]
	 * : Re-apply state that exists but was deliberately changed: page
	 * title/slug/status (the page ID is preserved), a custom site title,
	 * and a privacy page pointing at a different published page.
```

with:

```
	 * [--force]
	 * : Re-apply state that exists but was deliberately changed: page
	 * title/slug/status (the page ID is preserved), a custom site title,
	 * a privacy page pointing at a different published page, and a front
	 * page that is unpublished or pointing at a different published page.
```

Replace:

```
	 * Case-insensitive. Valid keys: theme, plugins, pages, privacy-page,
	 * permalinks, timezone, date-formats, reading, discussion,
	 * registration, site-identity, object-cache, sample-content.
```

with:

```
	 * Case-insensitive. Valid keys: theme, plugins, pages, privacy-page,
	 * home-page, permalinks, timezone, date-formats, reading, discussion,
	 * registration, site-identity, object-cache, sample-content.
```

- [ ] **Step 5: Unpin `show_on_front` in `class-reading-step.php`**

Replace the class docblock:

```php
/**
 * Converges the reading settings the theme expects: the front-page.html
 * block template owns the homepage (so show_on_front stays "posts"),
 * posts_per_page matches the 9-per-page articles grid, and blog_public
 * guards against the classic "cloned from staging, search engines still
 * discouraged" trap.
 */
```

with:

```php
/**
 * Converges the reading settings the theme expects: posts_per_page
 * matches the 9-per-page articles grid, and blog_public guards against
 * the classic "cloned from staging, search engines still discouraged"
 * trap. The front page itself (show_on_front / page_on_front) is
 * provisioned by Home_Page_Step.
 */
```

and replace the `options()` body:

```php
		return array(
			'show_on_front'  => 'posts',
			'posts_per_page' => 9,
			'blog_public'    => 1,
		);
```

with:

```php
		return array(
			'posts_per_page' => 9,
			'blog_public'    => 1,
		);
```

- [ ] **Step 6: Re-run the structure test (regression guard)**

Run: `node tests/home-patterns-structure.test.mjs`
Expected: exits 0.

---

### Task 5: Runtime verification on the dev stack

Requires the dev stack to be up (`composer dev` if it isn't — check with `docker compose ps`).

- [ ] **Step 1: Restart PHP containers to bust opcache (new PHP files were added)**

Run: `docker compose restart app wp-cli`
Expected: both containers restart cleanly.

- [ ] **Step 2: Run the new step in isolation**

Run: `composer dev:wp:cmd -- ik2 setup --only=home-page`
Expected output shape (first run on a site without a Home page):

```
Home page
  ✓ home — created
  ✓ show_on_front — set to "page"
  ✓ page_on_front — set to /home
Success: Setup complete: 3 ok, 0 failed.
```

- [ ] **Step 3: Verify idempotency — run it again**

Run: `composer dev:wp:cmd -- ik2 setup --only=home-page`
Expected:

```
Home page
  ✓ home — exists, content untouched
  ✓ show_on_front — already set
  ✓ page_on_front — already set
Success: Setup complete: 3 ok, 0 failed.
```

- [ ] **Step 4: Verify the options and page content directly**

Run:

```bash
composer dev:wp:cmd -- option get show_on_front
composer dev:wp:cmd -- option get page_on_front
composer dev:wp:cmd -- post list --post_type=page --name=home --fields=ID,post_title,post_status
```

Expected: `page`; a numeric ID matching the `home` page; the page listed as `publish`.

Then confirm the page content holds all six section blocks:

```bash
composer dev:wp:cmd -- post get $(docker compose exec -T wp-cli wp option get page_on_front) --field=post_content
```

Expected: the six `<!-- wp:ik2/home-* /-->` comments in template order (hero, featured-topics, evergreen-guides, latest-notes, projects-preview, speaking-preview).

If the `$(...)` nesting fails through composer's argument passing, read the ID from Step 4's output and substitute it literally.

- [ ] **Step 5: Verify the homepage renders the sections**

Find the site URL/port: `rtk grep -n "ports" -A 2 docker-compose.yml` (nginx service). Then:

Run: `rtk curl -s http://localhost:<port>/ | rtk grep -c "wp-block"`
Expected: non-zero — the homepage HTML still renders the section markup. Spot-check that hero content is present, e.g. `rtk curl -s http://localhost:<port>/ | rtk grep -i "hero\|home-hero"` (the exact class names come from the blocks' render.php files; any of the six sections' wrapper classes appearing is a pass).

- [ ] **Step 6: Run the full setup to check step interplay**

Run: `composer dev:wp:cmd -- ik2 setup`
Expected: all steps ✓ (Reading no longer reports `show_on_front`; "Home page" section shows all-✓ already-set lines). Exit code 0.

---

### Task 6: Quality gates

- [ ] **Step 1: PHP gates**

Run: `docker compose --profile tools run --rm composer quality`
Expected: PHPCS reports no errors; PHPStan (level 6) reports no errors. Fix anything reported in the files this plan touched, re-run until clean. Style fixes can use `docker compose --profile tools run --rm composer lint:fix` first.

- [ ] **Step 2: Structure test final pass**

Run: `node tests/home-patterns-structure.test.mjs`
Expected: exits 0.

(No JS/CSS sources changed, so `pnpm lint` is not required; the `.mjs` test file is outside the lint globs.)

---

## Self-review notes

- Spec coverage: template (Task 2), both patterns (Task 2), test rewrite (Task 1), Home_Page_Step incl. registry-with-file-fallback (Task 3), Reading_Step unpin + command registration + docblocks (Task 4), runtime + idempotency verification (Task 5), quality gates (Task 6). `page_for_posts` intentionally untouched per spec.
- Deviation from spec, justified: the spec says "read from the registry"; Task 3 prefers the registry but falls back to rendering the theme pattern file, because on a fresh install Theme_Step activates ik2 in-process after `_register_theme_block_patterns()` already ran — the registry would miss ik2 patterns in that same run (the same in-process pitfall the Permalinks_Step docblock describes).
- No commits anywhere, per user instruction.
