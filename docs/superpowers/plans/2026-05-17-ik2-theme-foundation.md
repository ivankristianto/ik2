# IK2 Theme Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the Header, Footer, and Homepage for the `ik2` block theme with a working SCSS build pipeline via `@wordpress/scripts`, mirroring the React prototype in `samples/` and following the "Ink, Paper, Signal" design system.

**Architecture:** Core blocks composed in template parts and PHP block patterns — no custom blocks this round. SCSS compiled to `wp-content/themes/ik2/build/` by wp-scripts. PHP split into `inc/Setup.php`, `inc/Assets.php`, `inc/Patterns.php` under `IK2\Theme` namespace. Patterns auto-registered from `patterns/` directory. Interactivity API scaffolded but not wired.

**Tech Stack:** WordPress 6.x block theme (FSE), PHP 8.4, `@wordpress/scripts` 30.x, Sass, Docker Compose stack (`composer dev`).

**Reference spec:** `docs/superpowers/specs/2026-05-16-ik2-theme-foundation-design.md`

**Source-of-truth for visuals:** `samples/components/`, `samples/pages/Home.jsx`, `samples/assets/kit.css`.

**Testing approach:** WordPress themes don't ship unit tests. Each task is verified by the relevant quality gate: `composer quality` for PHP, `pnpm lint` for JS/CSS, `pnpm build` for the asset pipeline, and a manual visual check at the end. Run the gates after every task — don't accumulate failures.

**Docker note:** All `composer` and `pnpm` commands below are run via the Docker tools profile, matching `CLAUDE.md`. If the engineer prefers, they can run `pnpm`/`composer` directly on the host after `pnpm install` and `composer install` once — the result is identical.

---

## Task 1: Build pipeline — wp-scripts + SCSS entry points

**Files:**
- Modify: `package.json`
- Create: `webpack.config.js`
- Create: `wp-content/themes/ik2/src/index.js`
- Create: `wp-content/themes/ik2/src/editor.js`
- Create: `wp-content/themes/ik2/src/style.scss`
- Create: `wp-content/themes/ik2/src/editor.scss`

- [ ] **Step 1: Add `sass` to devDependencies**

Edit `package.json` to add `sass` under `devDependencies`. The resulting block:

```json
"devDependencies": {
    "@wordpress/eslint-plugin": "^21.0.0",
    "@wordpress/prettier-config": "^4.0.0",
    "@wordpress/scripts": "^30.0.0",
    "@wordpress/stylelint-config": "^23.0.0",
    "sass": "^1.77.0"
}
```

- [ ] **Step 2: Install deps**

Run: `docker compose --profile tools run --rm pnpm install`
Expected: lockfile updated, `sass` resolved.

- [ ] **Step 3: Create webpack.config.js at repo root**

```js
const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		index: path.resolve( __dirname, 'wp-content/themes/ik2/src/index.js' ),
		editor: path.resolve( __dirname, 'wp-content/themes/ik2/src/editor.js' ),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'wp-content/themes/ik2/build' ),
	},
};
```

- [ ] **Step 4: Create the four entry/style files**

`wp-content/themes/ik2/src/index.js`:

```js
import './style.scss';
```

`wp-content/themes/ik2/src/editor.js`:

```js
import './editor.scss';
```

`wp-content/themes/ik2/src/style.scss`:

```scss
// IK2 theme — front-end styles. Partials added in later tasks.
```

`wp-content/themes/ik2/src/editor.scss`:

```scss
// IK2 theme — editor-only overrides. Kept small.
```

- [ ] **Step 5: Run the build and verify outputs**

Run: `docker compose --profile tools run --rm pnpm build`
Expected: command exits 0; the following files exist:
- `wp-content/themes/ik2/build/index.js`
- `wp-content/themes/ik2/build/index.css`
- `wp-content/themes/ik2/build/editor.js`
- `wp-content/themes/ik2/build/editor.css`

If a file is missing, the entry name or path in `webpack.config.js` is wrong — fix and re-run.

- [ ] **Step 6: Confirm `build/` is gitignored**

Run: `git status --short wp-content/themes/ik2/build/`
Expected: no output (already covered by `/wp-content/themes/*/build/` in `.gitignore`).

- [ ] **Step 7: Commit**

```bash
git add package.json pnpm-lock.yaml webpack.config.js wp-content/themes/ik2/src
git commit -m "Wire wp-scripts + SCSS build pipeline for ik2 theme"
```

---

## Task 2: PHP architecture — split functions.php into inc/

**Files:**
- Modify: `wp-content/themes/ik2/functions.php`
- Create: `wp-content/themes/ik2/inc/Setup.php`
- Create: `wp-content/themes/ik2/inc/Assets.php`
- Create: `wp-content/themes/ik2/inc/Patterns.php`

- [ ] **Step 1: Slim functions.php to a loader**

Replace the entire contents of `wp-content/themes/ik2/functions.php` with:

```php
<?php
/**
 * IK2 theme bootstrap. Loads namespaced modules from inc/.
 *
 * @package IK2
 */

declare(strict_types=1);

namespace IK2\Theme;

defined( 'ABSPATH' ) || exit;

const VERSION = '0.1.0';

require_once __DIR__ . '/inc/Setup.php';
require_once __DIR__ . '/inc/Assets.php';
require_once __DIR__ . '/inc/Patterns.php';
```

- [ ] **Step 2: Create inc/Setup.php**

```php
<?php
/**
 * Theme supports and editor configuration.
 *
 * @package IK2
 */

declare(strict_types=1);

namespace IK2\Theme;

defined( 'ABSPATH' ) || exit;

add_action(
	'after_setup_theme',
	static function (): void {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'editor-styles' );
		add_theme_support(
			'html5',
			array( 'style', 'script', 'comment-form', 'comment-list', 'gallery', 'caption' )
		);

		add_editor_style( 'build/editor.css' );

		load_theme_textdomain( 'ik2', __DIR__ . '/../languages' );
	}
);
```

- [ ] **Step 3: Create inc/Assets.php**

```php
<?php
/**
 * Front-end asset enqueues. Stylesheet header lives in style.css; the real
 * CSS comes from build/index.css produced by wp-scripts.
 *
 * @package IK2
 */

declare(strict_types=1);

namespace IK2\Theme;

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		$build_dir = __DIR__ . '/../build';
		$build_uri = get_theme_file_uri( 'build' );

		if ( file_exists( $build_dir . '/index.css' ) ) {
			wp_enqueue_style(
				'ik2',
				$build_uri . '/index.css',
				array(),
				(string) filemtime( $build_dir . '/index.css' )
			);
		}

		if ( file_exists( $build_dir . '/index.js' ) ) {
			wp_enqueue_script(
				'ik2',
				$build_uri . '/index.js',
				array(),
				(string) filemtime( $build_dir . '/index.js' ),
				true
			);
		}
	}
);
```

- [ ] **Step 4: Create inc/Patterns.php (category only for now)**

```php
<?php
/**
 * Block pattern category registration. Individual patterns are auto-
 * registered by WordPress from the theme's patterns/ directory.
 *
 * @package IK2
 */

declare(strict_types=1);

namespace IK2\Theme;

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	static function (): void {
		register_block_pattern_category(
			'ik2-home',
			array( 'label' => __( 'IK2 — Home', 'ik2' ) )
		);
	}
);
```

- [ ] **Step 5: Run PHP quality gate**

Run: `docker compose --profile tools run --rm composer quality`
Expected: PHPCS and PHPStan both pass on the new files. If PHPStan complains about undefined WP functions, that means `phpstan.neon.dist` needs no change — these are core WordPress functions and should resolve via the existing stub setup.

- [ ] **Step 6: Commit**

```bash
git add wp-content/themes/ik2/functions.php wp-content/themes/ik2/inc
git commit -m "Split ik2 theme bootstrap into namespaced inc/ modules"
```

---

## Task 3: theme.json — templateParts + custom.width.full

**Files:**
- Modify: `wp-content/themes/ik2/theme.json`
- Modify: `design-system/theme.json`

Both files must end up identical — they're the canonical and theme copies of the tokens.

- [ ] **Step 1: Add `templateParts` and `settings.custom.width.full` to wp-content/themes/ik2/theme.json**

Insert `templateParts` as a top-level key (sibling of `settings`/`styles`), and add `custom.width.full` under `settings.custom`. The diff:

In `settings`, ensure a `custom` block exists with `width.full`:

```json
"custom": {
  "width": {
    "full": "1280px"
  }
}
```

At the top level, after `styles`, add:

```json
"templateParts": [
  { "name": "header", "title": "Header", "area": "header" },
  { "name": "footer", "title": "Footer", "area": "footer" }
]
```

- [ ] **Step 2: Mirror the same edits in design-system/theme.json**

Apply the identical two additions to `design-system/theme.json`. The two files must `diff` clean afterwards.

- [ ] **Step 3: Verify the two files match**

Run: `diff wp-content/themes/ik2/theme.json design-system/theme.json`
Expected: no output (files identical).

- [ ] **Step 4: Commit**

```bash
git add wp-content/themes/ik2/theme.json design-system/theme.json
git commit -m "Declare ik2 templateParts and full container width token"
```

---

## Task 4: SCSS foundation — tokens, reset, layout, typography

**Files:**
- Create: `wp-content/themes/ik2/src/styles/_tokens.scss`
- Create: `wp-content/themes/ik2/src/styles/_reset.scss`
- Create: `wp-content/themes/ik2/src/styles/_layout.scss`
- Create: `wp-content/themes/ik2/src/styles/_typography.scss`
- Modify: `wp-content/themes/ik2/src/style.scss`

- [ ] **Step 1: Create `_tokens.scss`**

Maps SCSS variables to the CSS custom properties WordPress emits from `theme.json`. Use these throughout the theme so values track tokens automatically.

```scss
// Token bridge. WordPress emits CSS custom properties from theme.json;
// these SCSS variables alias them so component partials read naturally.

$color-paper:        var(--wp--preset--color--paper);
$color-surface:      var(--wp--preset--color--surface);
$color-soft-paper:   var(--wp--preset--color--soft-paper);
$color-ink:          var(--wp--preset--color--ink);
$color-graphite:     var(--wp--preset--color--graphite);
$color-dust:         var(--wp--preset--color--dust);
$color-line:         var(--wp--preset--color--line);
$color-rule:         var(--wp--preset--color--rule);
$color-signal:       var(--wp--preset--color--signal);
$color-signal-deep:  var(--wp--preset--color--signal-deep);
$color-signal-soft:  var(--wp--preset--color--signal-soft);
$color-code-paper:   var(--wp--preset--color--code-paper);
$color-code-ink:     var(--wp--preset--color--code-ink);
$color-build-green:  var(--wp--preset--color--build-green);
$color-amber:        var(--wp--preset--color--amber);
$color-red:          var(--wp--preset--color--red);

$font-sans: var(--wp--preset--font-family--sans);
$font-mono: var(--wp--preset--font-family--mono);

$size-xs:   var(--wp--preset--font-size--xs);
$size-sm:   var(--wp--preset--font-size--sm);
$size-md:   var(--wp--preset--font-size--md);
$size-lg:   var(--wp--preset--font-size--lg);
$size-xl:   var(--wp--preset--font-size--xl);
$size-2xl:  var(--wp--preset--font-size--2-xl);
$size-3xl:  var(--wp--preset--font-size--3-xl);
$size-4xl:  var(--wp--preset--font-size--4-xl);
$size-hero: var(--wp--preset--font-size--hero);

$space-1:  var(--wp--preset--spacing--1);
$space-2:  var(--wp--preset--spacing--2);
$space-3:  var(--wp--preset--spacing--3);
$space-4:  var(--wp--preset--spacing--4);
$space-5:  var(--wp--preset--spacing--5);
$space-6:  var(--wp--preset--spacing--6);
$space-7:  var(--wp--preset--spacing--7);
$space-8:  var(--wp--preset--spacing--8);
$space-9:  var(--wp--preset--spacing--9);
$space-10: var(--wp--preset--spacing--10);

$width-content: 720px;
$width-wide:    1080px;
$width-full:    var(--wp--custom--width--full);

$radius-sm: 0.25rem;
$radius-md: 0.375rem;
$radius-lg: 0.5rem;

$shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.04), 0 1px 1px rgba(0, 0, 0, 0.03);
$shadow-md: 0 6px 24px rgba(0, 0, 0, 0.08);

$focus: 2px solid #{$color-signal};
```

- [ ] **Step 2: Create `_reset.scss`**

```scss
// Minimal reset — WordPress already normalises a lot. Keep this small.

*,
*::before,
*::after {
	box-sizing: border-box;
}

img,
svg {
	display: block;
	max-width: 100%;
	height: auto;
}

a {
	color: inherit;
}

:focus-visible {
	outline: $focus;
	outline-offset: 3px;
}

button {
	font: inherit;
	cursor: pointer;
}
```

- [ ] **Step 3: Create `_layout.scss`**

```scss
// Container widths and section rhythm.

.container-full {
	width: 100%;
	max-width: $width-full;
	margin-inline: auto;
	padding-inline: $space-5;

	@media (min-width: 768px) {
		padding-inline: $space-6;
	}
}

.ik-section {
	padding-block: $space-9;

	&--muted {
		background: $color-soft-paper;
	}
}

.ik-section__head {
	display: flex;
	align-items: end;
	justify-content: space-between;
	gap: $space-5;
	margin-bottom: $space-7;
	flex-wrap: wrap;
}

.ik-section__eyebrow {
	font-family: $font-mono;
	font-size: $size-xs;
	text-transform: uppercase;
	letter-spacing: 0.08em;
	color: $color-graphite;
	margin-bottom: $space-2;
}

.ik-section__title {
	font-size: $size-3xl;
	line-height: 1.1;
	letter-spacing: -0.04em;
	margin: 0;
}

.ik-section__more {
	font-size: $size-sm;
	color: $color-signal;
	text-decoration: none;

	&:hover {
		text-decoration: underline;
	}
}

.ik-grid-2 {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	gap: $space-6;

	@media (max-width: 768px) {
		grid-template-columns: 1fr;
	}
}

.ik-grid-3 {
	display: grid;
	grid-template-columns: repeat(3, minmax(0, 1fr));
	gap: $space-5;

	@media (max-width: 960px) {
		grid-template-columns: repeat(2, minmax(0, 1fr));
	}

	@media (max-width: 600px) {
		grid-template-columns: 1fr;
	}
}
```

- [ ] **Step 4: Create `_typography.scss`**

```scss
// Body typography baseline. Headings inherit from theme.json styles.

body {
	font-family: $font-sans;
	font-size: $size-lg;
	line-height: 1.7;
	color: $color-ink;
	background: $color-paper;
	-webkit-font-smoothing: antialiased;
}

h1, h2, h3, h4, h5, h6 {
	font-weight: 700;
	letter-spacing: -0.04em;
	line-height: 1.1;
}

code,
kbd,
pre,
samp {
	font-family: $font-mono;
	font-size: 0.95em;
}

kbd {
	background: $color-code-paper;
	border: 1px solid $color-line;
	border-radius: $radius-sm;
	padding: 0 $space-2;
	font-size: $size-xs;
}
```

- [ ] **Step 5: Wire partials into `style.scss`**

Replace the contents of `wp-content/themes/ik2/src/style.scss`:

```scss
// IK2 theme — front-end stylesheet entry.

@use 'styles/tokens' as *;
@use 'styles/reset';
@use 'styles/typography';
@use 'styles/layout';
```

Note: `@use` with `as *` exposes the token variables globally; subsequent partials (`reset`, `typography`, `layout`) don't need to import `tokens` themselves because Dart Sass with `@use` would normally scope them — to keep partials terse, **each partial that uses tokens must `@use '../styles/tokens' as *;` at the top**. Add that line to `_reset.scss`, `_layout.scss`, and `_typography.scss` now.

The final structure of each partial begins with:

```scss
@use 'tokens' as *;
```

(Path is relative to the partial file. Since they're sibling files under `src/styles/`, `'tokens'` is correct.)

Update each of `_reset.scss`, `_layout.scss`, `_typography.scss` to start with that `@use 'tokens' as *;` line.

- [ ] **Step 6: Build and verify CSS is non-empty**

Run: `docker compose --profile tools run --rm pnpm build`
Expected: build succeeds. Check `wp-content/themes/ik2/build/index.css` has content (more than just an empty file).

- [ ] **Step 7: Lint CSS**

Run: `docker compose --profile tools run --rm pnpm lint:css`
Expected: passes. If Stylelint flags `@use` syntax, that's a Stylelint-scss issue — fix by adjusting the partial structure, but the standard `@wordpress/stylelint-config` accepts SCSS.

- [ ] **Step 8: Commit**

```bash
git add wp-content/themes/ik2/src
git commit -m "Add SCSS foundation: tokens, reset, layout, typography"
```

---

## Task 5: Wordmark styles

**Files:**
- Create: `wp-content/themes/ik2/src/styles/_wordmark.scss`
- Modify: `wp-content/themes/ik2/src/style.scss`

The wordmark renders the literal Site Title (e.g. "ivan") with a `~ $` prefix and a blinking cursor caret, all via CSS pseudo-elements so the underlying block stays a real Site Title block.

- [ ] **Step 1: Create `_wordmark.scss`**

```scss
@use 'tokens' as *;

.ik-wordmark {
	font-family: $font-mono;
	font-size: $size-lg;
	font-weight: 600;
	color: $color-ink;
	display: inline-flex;
	align-items: baseline;
	gap: 0.4ch;
	text-decoration: none;
	letter-spacing: -0.01em;
}

.ik-wordmark::before {
	content: '~ $';
	color: $color-graphite;
	font-weight: 500;
}

.ik-wordmark::after {
	content: '';
	display: inline-block;
	width: 0.55ch;
	height: 1em;
	background: $color-signal;
	transform: translateY(0.15em);
	animation: ik-cursor-blink 1.1s steps(2, end) infinite;
}

@keyframes ik-cursor-blink {
	to { opacity: 0; }
}

// When the wordmark sits inside the Site Title block, suppress its default link decoration.
.wp-block-site-title.ik-wordmark a,
.wp-block-site-title.ik-wordmark a:hover {
	text-decoration: none;
	color: inherit;
}
```

- [ ] **Step 2: Import the partial in style.scss**

Add `@use 'styles/wordmark';` to `wp-content/themes/ik2/src/style.scss` after the `layout` import. The file now reads:

```scss
@use 'styles/tokens' as *;
@use 'styles/reset';
@use 'styles/typography';
@use 'styles/layout';
@use 'styles/wordmark';
```

- [ ] **Step 3: Build and lint**

Run: `docker compose --profile tools run --rm pnpm build`
Expected: passes.
Run: `docker compose --profile tools run --rm pnpm lint:css`
Expected: passes.

- [ ] **Step 4: Commit**

```bash
git add wp-content/themes/ik2/src
git commit -m "Add wordmark styles with caret prefix and blinking cursor"
```

---

## Task 6: Header — nav fallback + part + styles

**Files:**
- Modify: `wp-content/themes/ik2/inc/Setup.php`
- Modify: `wp-content/themes/ik2/parts/header.html`
- Create: `wp-content/themes/ik2/src/styles/_header.scss`
- Modify: `wp-content/themes/ik2/src/style.scss`

- [ ] **Step 1: Add nav menu fallback to Setup.php**

Append the following to `wp-content/themes/ik2/inc/Setup.php` (after the existing `after_setup_theme` callback):

```php
add_action(
	'init',
	static function (): void {
		register_nav_menu( 'primary', __( 'Primary', 'ik2' ) );

		if ( ! is_admin() && ! wp_doing_ajax() ) {
			$existing = wp_get_nav_menu_object( 'IK2 Primary' );

			if ( false === $existing ) {
				$menu_id = wp_create_nav_menu( 'IK2 Primary' );

				if ( ! is_wp_error( $menu_id ) ) {
					$items = array(
						array( 'title' => 'Home',     'url' => home_url( '/' ) ),
						array( 'title' => 'Articles', 'url' => home_url( '/articles' ) ),
						array( 'title' => 'Projects', 'url' => home_url( '/projects' ) ),
						array( 'title' => 'Speaking', 'url' => home_url( '/speaking' ) ),
						array( 'title' => 'About',    'url' => home_url( '/about' ) ),
						array( 'title' => 'Contact',  'url' => home_url( '/contact' ) ),
					);

					foreach ( $items as $item ) {
						wp_update_nav_menu_item(
							$menu_id,
							0,
							array(
								'menu-item-title'   => $item['title'],
								'menu-item-url'     => $item['url'],
								'menu-item-status'  => 'publish',
							)
						);
					}
				}
			}
		}
	}
);
```

- [ ] **Step 2: Replace the header part markup**

Overwrite `wp-content/themes/ik2/parts/header.html`:

```html
<!-- wp:group {"tagName":"header","className":"ik-header","style":{"spacing":{"padding":{"top":"var:preset|spacing|5","bottom":"var:preset|spacing|5"}}},"layout":{"type":"constrained"}} -->
<header class="wp-block-group ik-header" style="padding-top:var(--wp--preset--spacing--5);padding-bottom:var(--wp--preset--spacing--5)">
	<!-- wp:group {"className":"container-full ik-header__row","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group container-full ik-header__row">
		<!-- wp:site-title {"level":0,"className":"ik-wordmark"} /-->

		<!-- wp:group {"className":"ik-header__right","layout":{"type":"flex","flexWrap":"nowrap"}} -->
		<div class="wp-block-group ik-header__right">
			<!-- wp:navigation {"className":"ik-header__nav","overlayMenu":"mobile","layout":{"type":"flex","setCascadingProperties":true,"justifyContent":"left"}} /-->

			<!-- wp:html -->
			<button type="button" class="ik-header__cmd" aria-label="Open command palette (coming soon)">
				<span>Search</span>
				<kbd>⌘K</kbd>
			</button>
			<!-- /wp:html -->

			<!-- wp:buttons {"className":"ik-header__resume-wrap"} -->
			<div class="wp-block-buttons ik-header__resume-wrap">
				<!-- wp:button {"className":"ik-header__resume","style":{"border":{"radius":"0.375rem"}}} -->
				<div class="wp-block-button ik-header__resume"><a class="wp-block-button__link wp-element-button" href="/resume" style="border-radius:0.375rem">Resume</a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</header>
<!-- /wp:group -->
```

- [ ] **Step 3: Create `_header.scss`**

```scss
@use 'tokens' as *;

.ik-header {
	background: $color-paper;
	border-bottom: 1px solid $color-line;
	position: sticky;
	top: 0;
	z-index: 50;
	backdrop-filter: blur(8px);
}

.ik-header__row {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: $space-5;
}

.ik-header__right {
	display: flex;
	align-items: center;
	gap: $space-4;
}

.ik-header__nav {
	font-size: $size-sm;

	a {
		color: $color-graphite;
		text-decoration: none;
		padding: $space-2 $space-3;
		border-radius: $radius-sm;
		transition: color 200ms ease, background 200ms ease;

		&:hover {
			color: $color-ink;
			background: $color-soft-paper;
		}

		&.current-menu-item,
		&[aria-current='page'] {
			color: $color-ink;
			background: $color-soft-paper;
		}
	}
}

.ik-header__cmd {
	display: inline-flex;
	align-items: center;
	gap: $space-2;
	background: $color-surface;
	border: 1px solid $color-line;
	border-radius: $radius-md;
	padding: $space-2 $space-3;
	color: $color-graphite;
	font-size: $size-sm;
	transition: border-color 200ms ease, color 200ms ease;

	&:hover {
		border-color: $color-rule;
		color: $color-ink;
	}

	kbd {
		font-size: $size-xs;
	}
}

.ik-header__resume .wp-block-button__link {
	background: $color-ink;
	color: $color-surface;
	font-size: $size-sm;
	padding: $space-2 $space-4;

	&:hover {
		background: $color-signal-deep;
	}
}

@media (max-width: 768px) {
	.ik-header__cmd {
		display: none;
	}

	.ik-header__nav {
		font-size: $size-md;
	}
}
```

- [ ] **Step 4: Import the partial in style.scss**

Add `@use 'styles/header';` after `wordmark`:

```scss
@use 'styles/tokens' as *;
@use 'styles/reset';
@use 'styles/typography';
@use 'styles/layout';
@use 'styles/wordmark';
@use 'styles/header';
```

- [ ] **Step 5: Build, lint PHP, lint JS/CSS**

Run: `docker compose --profile tools run --rm pnpm build`
Run: `docker compose --profile tools run --rm composer quality`
Run: `docker compose --profile tools run --rm pnpm lint`
Expected: all pass.

- [ ] **Step 6: Commit**

```bash
git add wp-content/themes/ik2/inc/Setup.php wp-content/themes/ik2/parts/header.html wp-content/themes/ik2/src
git commit -m "Build header part with wordmark, nav fallback, search stub, resume CTA"
```

---

## Task 7: Footer — icons, part, styles

**Files:**
- Create: `wp-content/themes/ik2/assets/icons/github.svg` (copy)
- Create: `wp-content/themes/ik2/assets/icons/linkedin.svg` (copy)
- Create: `wp-content/themes/ik2/assets/icons/twitter.svg` (copy)
- Create: `wp-content/themes/ik2/assets/icons/wordpress.svg` (copy)
- Create: `wp-content/themes/ik2/assets/icons/rss.svg` (copy)
- Modify: `wp-content/themes/ik2/parts/footer.html`
- Create: `wp-content/themes/ik2/src/styles/_footer.scss`
- Modify: `wp-content/themes/ik2/src/style.scss`

- [ ] **Step 1: Copy SVG icons from samples/**

Run:

```bash
mkdir -p wp-content/themes/ik2/assets/icons
cp samples/assets/icons/github.svg samples/assets/icons/linkedin.svg samples/assets/icons/twitter.svg samples/assets/icons/wordpress.svg samples/assets/icons/rss.svg wp-content/themes/ik2/assets/icons/
```

Expected: five `.svg` files now exist under `wp-content/themes/ik2/assets/icons/`.

- [ ] **Step 2: Overwrite the footer part**

Overwrite `wp-content/themes/ik2/parts/footer.html`:

```html
<!-- wp:group {"tagName":"footer","className":"ik-footer","backgroundColor":"soft-paper","style":{"spacing":{"padding":{"top":"var:preset|spacing|9","bottom":"var:preset|spacing|6"}}},"layout":{"type":"constrained"}} -->
<footer class="wp-block-group ik-footer has-soft-paper-background-color has-background" style="padding-top:var(--wp--preset--spacing--9);padding-bottom:var(--wp--preset--spacing--6)">
	<!-- wp:group {"className":"container-full","layout":{"type":"default"}} -->
	<div class="wp-block-group container-full">

		<!-- wp:columns {"className":"ik-footer__columns"} -->
		<div class="wp-block-columns ik-footer__columns">

			<!-- wp:column {"className":"ik-footer__brand"} -->
			<div class="wp-block-column ik-footer__brand">
				<!-- wp:html -->
				<div class="ik-wordmark" style="font-size:1.0625rem">ivan</div>
				<!-- /wp:html -->
				<!-- wp:paragraph {"className":"ik-footer__tagline","fontSize":"sm"} -->
				<p class="ik-footer__tagline has-sm-font-size">Exploring WordPress, AI, performance, and developer tooling.</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"className":"ik-footer__handle","fontSize":"xs"} -->
				<p class="ik-footer__handle has-xs-font-size">// @ivankristianto on the web</p>
				<!-- /wp:paragraph -->
				<!-- wp:html -->
				<nav class="ik-footer__social" aria-label="Social">
					<a href="https://github.com/ivankristianto" aria-label="GitHub"><img src="/wp-content/themes/ik2/assets/icons/github.svg" alt="" width="20" height="20"></a>
					<a href="https://linkedin.com/in/ivankristianto" aria-label="LinkedIn"><img src="/wp-content/themes/ik2/assets/icons/linkedin.svg" alt="" width="20" height="20"></a>
					<a href="https://twitter.com/ivankristianto" aria-label="Twitter / X"><img src="/wp-content/themes/ik2/assets/icons/twitter.svg" alt="" width="20" height="20"></a>
					<a href="https://profiles.wordpress.org/ivankristianto/" aria-label="WordPress.org"><img src="/wp-content/themes/ik2/assets/icons/wordpress.svg" alt="" width="20" height="20"></a>
					<a href="/feed" aria-label="RSS feed" class="ik-footer__rss"><img src="/wp-content/themes/ik2/assets/icons/rss.svg" alt="" width="20" height="20"></a>
				</nav>
				<!-- /wp:html -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"className":"ik-footer__col"} -->
			<div class="wp-block-column ik-footer__col">
				<!-- wp:heading {"level":4} --><h4 class="wp-block-heading">Site</h4><!-- /wp:heading -->
				<!-- wp:list -->
				<ul class="wp-block-list">
					<li><a href="/">Home</a></li>
					<li><a href="/articles">Articles</a></li>
					<li><a href="/projects">Projects</a></li>
					<li><a href="/speaking">Speaking</a></li>
					<li><a href="/about">About</a></li>
					<li><a href="/contact">Contact</a></li>
					<li><a href="/resume">Resume</a></li>
				</ul>
				<!-- /wp:list -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"className":"ik-footer__col"} -->
			<div class="wp-block-column ik-footer__col">
				<!-- wp:heading {"level":4} --><h4 class="wp-block-heading">Topics</h4><!-- /wp:heading -->
				<!-- wp:list -->
				<ul class="wp-block-list">
					<li><a href="/category/wordpress">WordPress</a></li>
					<li><a href="/category/ai">AI</a></li>
					<li><a href="/category/performance">Performance</a></li>
					<li><a href="/category/web-apis">Web APIs</a></li>
					<li><a href="/category/tooling">Tooling</a></li>
				</ul>
				<!-- /wp:list -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"className":"ik-footer__col"} -->
			<div class="wp-block-column ik-footer__col">
				<!-- wp:heading {"level":4} --><h4 class="wp-block-heading">Subscribe</h4><!-- /wp:heading -->
				<!-- wp:list -->
				<ul class="wp-block-list">
					<li><a href="/feed">RSS feed</a></li>
					<li><a href="/feed/json">JSON feed</a></li>
					<li><a href="mailto:hello@ivankristianto.com">Email me</a></li>
				</ul>
				<!-- /wp:list -->
			</div>
			<!-- /wp:column -->

		</div>
		<!-- /wp:columns -->

		<!-- wp:group {"className":"ik-footer__bottom","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
		<div class="wp-block-group ik-footer__bottom">
			<!-- wp:paragraph {"fontSize":"xs"} -->
			<p class="has-xs-font-size">© 2026 Ivan Kristianto  ·  Built on WordPress with a custom block theme  ·  Ink, Paper, Signal design system.</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"fontSize":"xs"} -->
			<p class="has-xs-font-size">Last published May 12, 2026</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

	</div>
	<!-- /wp:group -->
</footer>
<!-- /wp:group -->
```

- [ ] **Step 3: Create `_footer.scss`**

```scss
@use 'tokens' as *;

.ik-footer {
	color: $color-graphite;
	font-size: $size-sm;
	border-top: 1px solid $color-line;
}

.ik-footer__columns {
	gap: $space-6;
}

.ik-footer__brand .ik-wordmark {
	margin-bottom: $space-3;
}

.ik-footer__tagline {
	max-width: 280px;
	color: $color-graphite;
	margin: 0 0 $space-4;
}

.ik-footer__handle {
	font-family: $font-mono;
	color: $color-dust;
	margin: 0 0 $space-4;
}

.ik-footer__social {
	display: flex;
	gap: $space-3;

	a {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		width: 28px;
		height: 28px;
		border-radius: $radius-sm;
		opacity: 0.7;
		transition: opacity 200ms ease, background 200ms ease;

		&:hover {
			opacity: 1;
			background: $color-paper;
		}
	}

	img {
		width: 18px;
		height: 18px;
	}
}

.ik-footer__col {
	h4 {
		font-size: $size-xs;
		font-family: $font-mono;
		text-transform: uppercase;
		letter-spacing: 0.08em;
		color: $color-ink;
		margin: 0 0 $space-3;
	}

	ul {
		list-style: none;
		padding: 0;
		margin: 0;
		display: flex;
		flex-direction: column;
		gap: $space-2;
	}

	a {
		color: $color-graphite;
		text-decoration: none;
		transition: color 200ms ease;

		&:hover {
			color: $color-ink;
		}
	}
}

.ik-footer__bottom {
	margin-top: $space-8;
	padding-top: $space-5;
	border-top: 1px solid $color-line;
	color: $color-dust;

	p {
		margin: 0;
	}
}
```

- [ ] **Step 4: Wire partial**

In `wp-content/themes/ik2/src/style.scss`, add `@use 'styles/footer';` after the header import.

- [ ] **Step 5: Build + lint everything**

Run: `docker compose --profile tools run --rm pnpm build`
Run: `docker compose --profile tools run --rm pnpm lint`
Run: `docker compose --profile tools run --rm composer quality`
Expected: all pass.

- [ ] **Step 6: Commit**

```bash
git add wp-content/themes/ik2/assets wp-content/themes/ik2/parts/footer.html wp-content/themes/ik2/src
git commit -m "Build footer part with brand column, social icons, and link groups"
```

---

## Task 8: Hero pattern + styles

**Files:**
- Create: `wp-content/themes/ik2/patterns/home-hero.php`
- Create: `wp-content/themes/ik2/src/styles/_hero.scss`
- Modify: `wp-content/themes/ik2/src/style.scss`

- [ ] **Step 1: Create the pattern file**

```php
<?php
/**
 * Title: Home — Hero
 * Slug: ik2/home-hero
 * Categories: ik2-home
 * Description: Homepage hero with eyebrow, headline, intro, and two CTAs.
 */
?>
<!-- wp:group {"className":"ik-section ik-hero","layout":{"type":"constrained"}} -->
<section class="wp-block-group ik-section ik-hero">
	<div class="container-full">
		<!-- wp:paragraph {"className":"ik-section__eyebrow"} -->
		<p class="ik-section__eyebrow">// CURRENTLY EXPLORING</p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"level":1,"className":"ik-hero__title","fontSize":"hero"} -->
		<h1 class="wp-block-heading ik-hero__title has-hero-font-size">Building things on the web — mostly with WordPress and AI.</h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"ik-hero__lede","fontSize":"xl"} -->
		<p class="ik-hero__lede has-xl-font-size">I write about WordPress engineering, AI-assisted development, performance, and the developer tooling that quietly makes large projects bearable. Most of what I publish here started as a working note to myself.</p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons {"className":"ik-hero__ctas"} -->
		<div class="wp-block-buttons ik-hero__ctas">
			<!-- wp:button {"className":"is-style-fill","style":{"border":{"radius":"0.375rem"}}} -->
			<div class="wp-block-button is-style-fill"><a class="wp-block-button__link wp-element-button" href="/articles" style="border-radius:0.375rem">Browse articles</a></div>
			<!-- /wp:button -->
			<!-- wp:button {"className":"is-style-outline","style":{"border":{"radius":"0.375rem"}}} -->
			<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/about" style="border-radius:0.375rem">About me</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
</section>
<!-- /wp:group -->
```

- [ ] **Step 2: Create `_hero.scss`**

```scss
@use 'tokens' as *;

.ik-hero {
	padding-block: $space-10 $space-9;
}

.ik-hero__title {
	max-width: 18ch;
	margin: 0 0 $space-5;
}

.ik-hero__lede {
	max-width: 60ch;
	color: $color-graphite;
	margin: 0 0 $space-6;
}

.ik-hero__ctas .wp-block-button__link {
	font-size: $size-md;
}

.ik-hero__ctas .is-style-fill .wp-block-button__link {
	background: $color-ink;
	color: $color-surface;

	&:hover {
		background: $color-signal-deep;
	}
}

.ik-hero__ctas .is-style-outline .wp-block-button__link {
	border: 1px solid $color-line;
	color: $color-ink;
	background: transparent;

	&:hover {
		border-color: $color-rule;
		background: $color-soft-paper;
	}
}
```

- [ ] **Step 3: Wire and verify**

Add `@use 'styles/hero';` to `style.scss`.
Run: `docker compose --profile tools run --rm pnpm build`
Run: `docker compose --profile tools run --rm composer quality`
Expected: pass.

- [ ] **Step 4: Commit**

```bash
git add wp-content/themes/ik2/patterns/home-hero.php wp-content/themes/ik2/src
git commit -m "Add home-hero pattern and hero styles"
```

---

## Task 9: Featured topics pattern + styles

**Files:**
- Create: `wp-content/themes/ik2/patterns/home-featured-topics.php`
- Create: `wp-content/themes/ik2/src/styles/_topics.scss`
- Modify: `wp-content/themes/ik2/src/style.scss`

- [ ] **Step 1: Create the pattern**

```php
<?php
/**
 * Title: Home — Featured topics
 * Slug: ik2/home-featured-topics
 * Categories: ik2-home
 * Description: Six topic cards with one-line blurbs.
 */
?>
<!-- wp:group {"className":"ik-section","layout":{"type":"constrained"}} -->
<section class="wp-block-group ik-section">
	<div class="container-full">
		<div class="ik-section__head">
			<div>
				<!-- wp:paragraph {"className":"ik-section__eyebrow"} --><p class="ik-section__eyebrow">// FEATURED TOPICS</p><!-- /wp:paragraph -->
				<!-- wp:heading {"level":2,"className":"ik-section__title"} --><h2 class="wp-block-heading ik-section__title">Where I spend my time on the web</h2><!-- /wp:heading -->
			</div>
			<!-- wp:paragraph {"className":"ik-section__more"} --><p class="ik-section__more"><a href="/articles">All articles →</a></p><!-- /wp:paragraph -->
		</div>

		<!-- wp:html -->
		<div class="ik-topics">
			<a class="ik-topic" href="/category/wordpress">
				<div class="ik-topic__row"><span class="ik-topic__name">WordPress</span><span class="ik-topic__count">42</span></div>
				<p class="ik-topic__blurb">Engineering notes from large-scale WordPress builds.</p>
			</a>
			<a class="ik-topic" href="/category/ai">
				<div class="ik-topic__row"><span class="ik-topic__name">AI</span><span class="ik-topic__count">18</span></div>
				<p class="ik-topic__blurb">How I use LLMs day-to-day, and where they actually help.</p>
			</a>
			<a class="ik-topic" href="/category/performance">
				<div class="ik-topic__row"><span class="ik-topic__name">Performance</span><span class="ik-topic__count">23</span></div>
				<p class="ik-topic__blurb">Real numbers from real sites — caching, queries, Core Web Vitals.</p>
			</a>
			<a class="ik-topic" href="/category/web-apis">
				<div class="ik-topic__row"><span class="ik-topic__name">Web APIs</span><span class="ik-topic__count">11</span></div>
				<p class="ik-topic__blurb">Platform primitives — what's new, what's stable, what's worth using.</p>
			</a>
			<a class="ik-topic" href="/category/tooling">
				<div class="ik-topic__row"><span class="ik-topic__name">Tooling</span><span class="ik-topic__count">16</span></div>
				<p class="ik-topic__blurb">Editor setup, CLI scripts, CI tricks, things that compound.</p>
			</a>
			<a class="ik-topic" href="/category/process">
				<div class="ik-topic__row"><span class="ik-topic__name">Process</span><span class="ik-topic__count">9</span></div>
				<p class="ik-topic__blurb">How I plan work, run reviews, and ship without drama.</p>
			</a>
		</div>
		<!-- /wp:html -->
	</div>
</section>
<!-- /wp:group -->
```

- [ ] **Step 2: Create `_topics.scss`**

```scss
@use 'tokens' as *;

.ik-topics {
	display: grid;
	grid-template-columns: repeat(3, minmax(0, 1fr));
	gap: $space-4;

	@media (max-width: 960px) {
		grid-template-columns: repeat(2, minmax(0, 1fr));
	}

	@media (max-width: 600px) {
		grid-template-columns: 1fr;
	}
}

.ik-topic {
	display: block;
	padding: $space-5;
	background: $color-surface;
	border: 1px solid $color-line;
	border-radius: $radius-md;
	text-decoration: none;
	color: inherit;
	transition: border-color 200ms ease, box-shadow 200ms ease;

	&:hover {
		border-color: $color-rule;
		box-shadow: $shadow-sm;
	}
}

.ik-topic__row {
	display: flex;
	align-items: baseline;
	justify-content: space-between;
	margin-bottom: $space-2;
}

.ik-topic__name {
	font-weight: 600;
	color: $color-ink;
}

.ik-topic__count {
	font-family: $font-mono;
	font-size: $size-xs;
	color: $color-dust;
}

.ik-topic__blurb {
	margin: 0;
	font-size: $size-sm;
	color: $color-graphite;
	line-height: 1.5;
}
```

- [ ] **Step 3: Wire and verify**

Add `@use 'styles/topics';` to `style.scss`.
Run: `docker compose --profile tools run --rm pnpm build`
Run: `docker compose --profile tools run --rm composer quality`
Expected: pass.

- [ ] **Step 4: Commit**

```bash
git add wp-content/themes/ik2/patterns/home-featured-topics.php wp-content/themes/ik2/src
git commit -m "Add featured-topics pattern with six topic cards"
```

---

## Task 10: Evergreen guides pattern + styles

**Files:**
- Create: `wp-content/themes/ik2/patterns/home-evergreen-guides.php`
- Create: `wp-content/themes/ik2/src/styles/_guides.scss`
- Modify: `wp-content/themes/ik2/src/style.scss`

- [ ] **Step 1: Create the pattern**

The Query Loop filters by category slug `guide`. If no posts are tagged `guide`, the Query No Results inner block renders a small "no guides yet" message.

```php
<?php
/**
 * Title: Home — Evergreen guides
 * Slug: ik2/home-evergreen-guides
 * Categories: ik2-home
 * Description: 2-column Query Loop of posts in the "guide" category.
 */
?>
<!-- wp:group {"className":"ik-section ik-section--muted","layout":{"type":"constrained"}} -->
<section class="wp-block-group ik-section ik-section--muted">
	<div class="container-full">
		<div class="ik-section__head">
			<div>
				<!-- wp:paragraph {"className":"ik-section__eyebrow"} --><p class="ik-section__eyebrow">// START HERE</p><!-- /wp:paragraph -->
				<!-- wp:heading {"level":2,"className":"ik-section__title"} --><h2 class="wp-block-heading ik-section__title">Evergreen guides</h2><!-- /wp:heading -->
			</div>
			<!-- wp:paragraph {"className":"ik-section__more"} --><p class="ik-section__more"><a href="/articles">All guides →</a></p><!-- /wp:paragraph -->
		</div>

		<!-- wp:query {"queryId":1,"query":{"perPage":4,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","taxQuery":{"category":["guide"]},"inherit":false}} -->
		<div class="wp-block-query">
			<!-- wp:post-template {"className":"ik-grid-2"} -->
				<!-- wp:group {"className":"ik-guide","layout":{"type":"constrained"}} -->
				<article class="wp-block-group ik-guide">
					<!-- wp:post-title {"isLink":true,"level":3,"className":"ik-guide__title"} /-->
					<!-- wp:post-excerpt {"className":"ik-guide__excerpt","excerptLength":28} /-->
					<!-- wp:post-date {"className":"ik-guide__meta","format":"F j, Y"} /-->
				</article>
				<!-- /wp:group -->
			<!-- /wp:post-template -->

			<!-- wp:query-no-results -->
				<!-- wp:paragraph -->
				<p>No guides yet — they will appear here once posts are tagged with the <code>guide</code> category.</p>
				<!-- /wp:paragraph -->
			<!-- /wp:query-no-results -->
		</div>
		<!-- /wp:query -->
	</div>
</section>
<!-- /wp:group -->
```

- [ ] **Step 2: Create `_guides.scss`**

```scss
@use 'tokens' as *;

.ik-guide {
	background: $color-surface;
	border: 1px solid $color-line;
	border-radius: $radius-md;
	padding: $space-6;
	transition: border-color 200ms ease, box-shadow 200ms ease;

	&:hover {
		border-color: $color-rule;
		box-shadow: $shadow-sm;
	}
}

.ik-guide__title {
	font-size: $size-xl;
	margin: 0 0 $space-3;

	a {
		color: $color-ink;
		text-decoration: none;

		&:hover {
			color: $color-signal;
		}
	}
}

.ik-guide__excerpt {
	color: $color-graphite;
	font-size: $size-md;
	line-height: 1.6;
	margin: 0 0 $space-4;
}

.ik-guide__meta {
	font-family: $font-mono;
	font-size: $size-xs;
	color: $color-dust;
}
```

- [ ] **Step 3: Wire and verify**

Add `@use 'styles/guides';` to `style.scss`.
Run: `docker compose --profile tools run --rm pnpm build`
Run: `docker compose --profile tools run --rm composer quality`
Expected: pass.

- [ ] **Step 4: Commit**

```bash
git add wp-content/themes/ik2/patterns/home-evergreen-guides.php wp-content/themes/ik2/src
git commit -m "Add evergreen-guides Query Loop pattern with fallback"
```

---

## Task 11: Latest notes + /now sidebar pattern + styles

**Files:**
- Create: `wp-content/themes/ik2/patterns/home-latest-notes.php`
- Create: `wp-content/themes/ik2/src/styles/_notes.scss`
- Modify: `wp-content/themes/ik2/src/style.scss`

- [ ] **Step 1: Create the pattern**

Two-column layout: left = Query Loop of notes; right = static `/now` widget.

```php
<?php
/**
 * Title: Home — Latest notes + /now
 * Slug: ik2/home-latest-notes
 * Categories: ik2-home
 * Description: Latest notes list alongside a static /now sidebar.
 */
?>
<!-- wp:group {"className":"ik-section","layout":{"type":"constrained"}} -->
<section class="wp-block-group ik-section">
	<div class="container-full">
		<div class="ik-section__head">
			<div>
				<!-- wp:paragraph {"className":"ik-section__eyebrow"} --><p class="ik-section__eyebrow">// LATEST NOTES  ·  TIL  ·  EXPERIMENTS  ·  LINKS</p><!-- /wp:paragraph -->
				<!-- wp:heading {"level":2,"className":"ik-section__title"} --><h2 class="wp-block-heading ik-section__title">What I&rsquo;ve been working on</h2><!-- /wp:heading -->
			</div>
			<!-- wp:paragraph {"className":"ik-section__more"} --><p class="ik-section__more"><a href="/articles">All articles →</a></p><!-- /wp:paragraph -->
		</div>

		<!-- wp:columns {"className":"ik-notes-layout"} -->
		<div class="wp-block-columns ik-notes-layout">

			<!-- wp:column {"width":"66.66%","className":"ik-notes-layout__main"} -->
			<div class="wp-block-column ik-notes-layout__main" style="flex-basis:66.66%">
				<!-- wp:query {"queryId":2,"query":{"perPage":6,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","taxQuery":{"category":["note"]},"inherit":false}} -->
				<div class="wp-block-query">
					<!-- wp:post-template -->
						<!-- wp:group {"className":"ik-note","layout":{"type":"constrained"}} -->
						<article class="wp-block-group ik-note">
							<!-- wp:post-date {"className":"ik-note__date","format":"F j, Y"} /-->
							<!-- wp:post-title {"isLink":true,"level":3,"className":"ik-note__title"} /-->
							<!-- wp:post-excerpt {"className":"ik-note__excerpt","excerptLength":24} /-->
						</article>
						<!-- /wp:group -->
					<!-- /wp:post-template -->
					<!-- wp:query-no-results -->
						<!-- wp:paragraph --><p>No notes yet.</p><!-- /wp:paragraph -->
					<!-- /wp:query-no-results -->
				</div>
				<!-- /wp:query -->
				<!-- wp:paragraph {"className":"ik-notes-layout__more"} -->
				<p class="ik-notes-layout__more"><a href="/articles">Read every note →</a></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"width":"33.33%","className":"ik-notes-layout__aside"} -->
			<div class="wp-block-column ik-notes-layout__aside" style="flex-basis:33.33%">
				<!-- wp:html -->
				<aside class="ik-now" aria-label="What Ivan is doing right now">
					<header class="ik-now__head">
						<span class="ik-now__dot" aria-hidden="true"></span>
						<span class="ik-now__label">// /now</span>
						<span class="ik-now__date">May 12, 2026</span>
					</header>
					<div class="ik-now__group">
						<div class="ik-now__group-title">Currently building</div>
						<div class="ik-now__item"><code>ivankristianto-theme</code> &mdash; rebuilding this site as a block theme.</div>
					</div>
					<div class="ik-now__group">
						<div class="ik-now__group-title">Currently reading</div>
						<div class="ik-now__item"><em>Designing Data-Intensive Applications</em>, Kleppmann &mdash; for the third time.</div>
					</div>
					<div class="ik-now__group">
						<div class="ik-now__group-title">Currently learning</div>
						<div class="ik-now__item">The WordPress Interactivity API &mdash; instant search + a real command palette.</div>
					</div>
					<div class="ik-now__group">
						<div class="ik-now__group-title">Listening</div>
						<div class="ik-now__item">The Changelog &middot; Syntax.fm &middot; WP Tavern Jukebox</div>
					</div>
					<footer class="ik-now__foot">Inspired by <a href="https://nownownow.com" target="_blank" rel="noreferrer">/now</a>. Updated when the world changes &mdash; not on a schedule.</footer>
				</aside>
				<!-- /wp:html -->
			</div>
			<!-- /wp:column -->

		</div>
		<!-- /wp:columns -->
	</div>
</section>
<!-- /wp:group -->
```

- [ ] **Step 2: Create `_notes.scss`**

```scss
@use 'tokens' as *;

.ik-notes-layout {
	gap: $space-7;
	align-items: flex-start;
}

.ik-note {
	padding: $space-5 0;
	border-bottom: 1px solid $color-line;

	&:first-child {
		padding-top: 0;
	}
}

.ik-note__date {
	font-family: $font-mono;
	font-size: $size-xs;
	color: $color-dust;
	margin: 0 0 $space-2;
}

.ik-note__title {
	font-size: $size-lg;
	margin: 0 0 $space-2;

	a {
		color: $color-ink;
		text-decoration: none;

		&:hover {
			color: $color-signal;
		}
	}
}

.ik-note__excerpt {
	color: $color-graphite;
	font-size: $size-md;
	line-height: 1.55;
	margin: 0;
}

.ik-notes-layout__more {
	margin-top: $space-5;
	font-size: $size-sm;

	a {
		color: $color-signal;
		text-decoration: none;

		&:hover {
			text-decoration: underline;
		}
	}
}

.ik-now {
	background: $color-surface;
	border: 1px solid $color-line;
	border-radius: $radius-md;
	padding: $space-5;
	font-size: $size-sm;
}

.ik-now__head {
	display: flex;
	align-items: center;
	gap: $space-2;
	font-family: $font-mono;
	font-size: $size-xs;
	color: $color-graphite;
	margin-bottom: $space-4;
}

.ik-now__dot {
	width: 8px;
	height: 8px;
	background: $color-build-green;
	border-radius: 50%;
	animation: ik-now-pulse 2s ease-in-out infinite;
}

@keyframes ik-now-pulse {
	0%, 100% { opacity: 1; }
	50% { opacity: 0.4; }
}

.ik-now__date {
	margin-left: auto;
	color: $color-dust;
}

.ik-now__group {
	padding: $space-3 0;
	border-top: 1px dashed $color-line;
}

.ik-now__group:first-of-type {
	border-top: 0;
	padding-top: 0;
}

.ik-now__group-title {
	font-family: $font-mono;
	font-size: $size-xs;
	color: $color-graphite;
	margin-bottom: $space-1;
}

.ik-now__item {
	color: $color-ink;
	line-height: 1.55;

	code {
		font-size: 0.9em;
		background: $color-code-paper;
		padding: 0 $space-1;
		border-radius: $radius-sm;
	}
}

.ik-now__foot {
	margin-top: $space-4;
	padding-top: $space-3;
	border-top: 1px solid $color-line;
	font-size: $size-xs;
	color: $color-dust;

	a {
		color: $color-graphite;
	}
}

@media (max-width: 768px) {
	.ik-notes-layout {
		gap: $space-5;
	}
}
```

- [ ] **Step 3: Wire and verify**

Add `@use 'styles/notes';` to `style.scss`.
Run: `docker compose --profile tools run --rm pnpm build`
Run: `docker compose --profile tools run --rm composer quality`
Expected: pass.

- [ ] **Step 4: Commit**

```bash
git add wp-content/themes/ik2/patterns/home-latest-notes.php wp-content/themes/ik2/src
git commit -m "Add latest-notes pattern with /now sidebar"
```

---

## Task 12: Projects preview pattern + styles

**Files:**
- Create: `wp-content/themes/ik2/patterns/home-projects-preview.php`
- Create: `wp-content/themes/ik2/src/styles/_projects.scss`
- Modify: `wp-content/themes/ik2/src/style.scss`

- [ ] **Step 1: Create the pattern**

```php
<?php
/**
 * Title: Home — Projects preview
 * Slug: ik2/home-projects-preview
 * Categories: ik2-home
 * Description: Three-card grid of recent project highlights.
 */
?>
<!-- wp:group {"className":"ik-section ik-section--muted","layout":{"type":"constrained"}} -->
<section class="wp-block-group ik-section ik-section--muted">
	<div class="container-full">
		<div class="ik-section__head">
			<div>
				<!-- wp:paragraph {"className":"ik-section__eyebrow"} --><p class="ik-section__eyebrow">// THINGS I&rsquo;VE BUILT</p><!-- /wp:paragraph -->
				<!-- wp:heading {"level":2,"className":"ik-section__title"} --><h2 class="wp-block-heading ik-section__title">Projects</h2><!-- /wp:heading -->
			</div>
			<!-- wp:paragraph {"className":"ik-section__more"} --><p class="ik-section__more"><a href="/projects">All projects →</a></p><!-- /wp:paragraph -->
		</div>

		<!-- wp:html -->
		<div class="ik-project-grid">
			<article class="ik-project">
				<div class="ik-project__head">
					<h3 class="ik-project__name"><a href="/projects/ivankristianto-theme">ivankristianto-theme</a></h3>
					<span class="ik-project__status" data-status="building">building</span>
				</div>
				<p class="ik-project__blurb">This very site. A block theme with FSE, design tokens, and the Interactivity API for command-palette search.</p>
				<div class="ik-project__tech"><span>WordPress</span><span>PHP 8.4</span><span>wp-scripts</span><span>SCSS</span></div>
			</article>

			<article class="ik-project">
				<div class="ik-project__head">
					<h3 class="ik-project__name"><a href="/projects/wp-perf-toolkit">wp-perf-toolkit</a></h3>
					<span class="ik-project__status" data-status="shipped">shipped</span>
				</div>
				<p class="ik-project__blurb">A small mu-plugin for measuring real-user query and template performance — designed for big editorial sites.</p>
				<div class="ik-project__tech"><span>WordPress</span><span>PHP</span><span>SQLite</span></div>
			</article>

			<article class="ik-project">
				<div class="ik-project__head">
					<h3 class="ik-project__name"><a href="/projects/ai-editor-helpers">ai-editor-helpers</a></h3>
					<span class="ik-project__status" data-status="exploring">exploring</span>
				</div>
				<p class="ik-project__blurb">Block-editor side experiments using Claude + the WordPress REST API for drafting, summaries, and inline rewrites.</p>
				<div class="ik-project__tech"><span>JavaScript</span><span>Anthropic API</span><span>WP REST</span></div>
			</article>
		</div>
		<!-- /wp:html -->
	</div>
</section>
<!-- /wp:group -->
```

- [ ] **Step 2: Create `_projects.scss`**

```scss
@use 'tokens' as *;

.ik-project-grid {
	display: grid;
	grid-template-columns: repeat(3, minmax(0, 1fr));
	gap: $space-5;

	@media (max-width: 960px) {
		grid-template-columns: repeat(2, minmax(0, 1fr));
	}

	@media (max-width: 600px) {
		grid-template-columns: 1fr;
	}
}

.ik-project {
	background: $color-surface;
	border: 1px solid $color-line;
	border-radius: $radius-md;
	padding: $space-5;
	transition: border-color 200ms ease, box-shadow 200ms ease;

	&:hover {
		border-color: $color-rule;
		box-shadow: $shadow-sm;
	}
}

.ik-project__head {
	display: flex;
	align-items: baseline;
	justify-content: space-between;
	gap: $space-3;
	margin-bottom: $space-3;
}

.ik-project__name {
	font-family: $font-mono;
	font-size: $size-md;
	margin: 0;

	a {
		color: $color-ink;
		text-decoration: none;

		&:hover {
			color: $color-signal;
		}
	}
}

.ik-project__status {
	font-family: $font-mono;
	font-size: $size-xs;
	text-transform: uppercase;
	letter-spacing: 0.06em;
	padding: $space-1 $space-2;
	border-radius: $radius-sm;
	color: $color-graphite;
	background: $color-soft-paper;

	&[data-status='building'] { color: $color-amber; background: rgba(180, 83, 9, 0.08); }
	&[data-status='shipped']  { color: $color-build-green; background: rgba(21, 128, 61, 0.08); }
	&[data-status='exploring']{ color: $color-signal; background: $color-signal-soft; }
}

.ik-project__blurb {
	color: $color-graphite;
	font-size: $size-sm;
	line-height: 1.55;
	margin: 0 0 $space-4;
}

.ik-project__tech {
	display: flex;
	flex-wrap: wrap;
	gap: $space-2;

	span {
		font-family: $font-mono;
		font-size: $size-xs;
		color: $color-graphite;
		background: $color-code-paper;
		padding: $space-1 $space-2;
		border-radius: $radius-sm;
	}
}
```

- [ ] **Step 3: Wire and verify**

Add `@use 'styles/projects';` to `style.scss`.
Run: `docker compose --profile tools run --rm pnpm build`
Run: `docker compose --profile tools run --rm composer quality`
Expected: pass.

- [ ] **Step 4: Commit**

```bash
git add wp-content/themes/ik2/patterns/home-projects-preview.php wp-content/themes/ik2/src
git commit -m "Add projects-preview pattern with 3-card grid"
```

---

## Task 13: Speaking preview pattern + styles

**Files:**
- Create: `wp-content/themes/ik2/patterns/home-speaking-preview.php`
- Create: `wp-content/themes/ik2/src/styles/_speaking.scss`
- Modify: `wp-content/themes/ik2/src/style.scss`

- [ ] **Step 1: Create the pattern**

```php
<?php
/**
 * Title: Home — Speaking preview
 * Slug: ik2/home-speaking-preview
 * Categories: ik2-home
 * Description: Four-row list of recent talks.
 */
?>
<!-- wp:group {"className":"ik-section","layout":{"type":"constrained"}} -->
<section class="wp-block-group ik-section">
	<div class="container-full">
		<div class="ik-section__head">
			<div>
				<!-- wp:paragraph {"className":"ik-section__eyebrow"} --><p class="ik-section__eyebrow">// SPEAKING &amp; COMMUNITY</p><!-- /wp:paragraph -->
				<!-- wp:heading {"level":2,"className":"ik-section__title"} --><h2 class="wp-block-heading ik-section__title">Recent talks</h2><!-- /wp:heading -->
			</div>
			<!-- wp:paragraph {"className":"ik-section__more"} --><p class="ik-section__more"><a href="/speaking">All talks →</a></p><!-- /wp:paragraph -->
		</div>

		<!-- wp:html -->
		<div class="ik-talks-list">
			<div class="ik-talk">
				<span class="ik-talk__date">Mar 22, 2026</span>
				<div>
					<div class="ik-talk__title">Shipping WordPress at scale with AI in the editor</div>
					<div class="ik-talk__venue">WordCamp Asia — Manila</div>
				</div>
				<span class="ik-talk__kind">keynote</span>
			</div>
			<div class="ik-talk">
				<span class="ik-talk__date">Feb 14, 2026</span>
				<div>
					<div class="ik-talk__title">Performance budgets for editorial WordPress</div>
					<div class="ik-talk__venue">Big Media Devs Slack — remote</div>
				</div>
				<span class="ik-talk__kind">workshop</span>
			</div>
			<div class="ik-talk">
				<span class="ik-talk__date">Nov 09, 2025</span>
				<div>
					<div class="ik-talk__title">A pragmatic Interactivity API tour</div>
					<div class="ik-talk__venue">JakartaJS — Jakarta</div>
				</div>
				<span class="ik-talk__kind">talk</span>
			</div>
			<div class="ik-talk">
				<span class="ik-talk__date">Sep 27, 2025</span>
				<div>
					<div class="ik-talk__title">From mu-plugins to platform engineering</div>
					<div class="ik-talk__venue">WordCamp US — Portland</div>
				</div>
				<span class="ik-talk__kind">talk</span>
			</div>
		</div>
		<!-- /wp:html -->
	</div>
</section>
<!-- /wp:group -->
```

- [ ] **Step 2: Create `_speaking.scss`**

```scss
@use 'tokens' as *;

.ik-talks-list {
	max-width: $width-content;
	display: flex;
	flex-direction: column;
}

.ik-talk {
	display: grid;
	grid-template-columns: 9rem 1fr auto;
	gap: $space-5;
	padding: $space-4 0;
	border-top: 1px solid $color-line;
	align-items: baseline;

	&:first-child {
		border-top: 0;
	}

	@media (max-width: 600px) {
		grid-template-columns: 1fr;
		gap: $space-1;
	}
}

.ik-talk__date {
	font-family: $font-mono;
	font-size: $size-xs;
	color: $color-graphite;
}

.ik-talk__title {
	color: $color-ink;
	font-weight: 600;
	line-height: 1.35;
}

.ik-talk__venue {
	color: $color-graphite;
	font-size: $size-sm;
	margin-top: $space-1;
}

.ik-talk__kind {
	font-family: $font-mono;
	font-size: $size-xs;
	color: $color-signal;
	letter-spacing: 0.06em;
	text-transform: uppercase;
}
```

- [ ] **Step 3: Wire and verify**

Add `@use 'styles/speaking';` to `style.scss`.
Run: `docker compose --profile tools run --rm pnpm build`
Run: `docker compose --profile tools run --rm composer quality`
Expected: pass.

- [ ] **Step 4: Commit**

```bash
git add wp-content/themes/ik2/patterns/home-speaking-preview.php wp-content/themes/ik2/src
git commit -m "Add speaking-preview pattern with talks list"
```

---

## Task 14: Front-page template + 404

**Files:**
- Create: `wp-content/themes/ik2/templates/front-page.html`
- Create: `wp-content/themes/ik2/templates/404.html`

- [ ] **Step 1: Create `templates/front-page.html`**

```html
<!-- wp:template-part {"slug":"header","tagName":"header"} /-->

<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->
<main class="wp-block-group">
	<!-- wp:pattern {"slug":"ik2/home-hero"} /-->
	<!-- wp:pattern {"slug":"ik2/home-featured-topics"} /-->
	<!-- wp:pattern {"slug":"ik2/home-evergreen-guides"} /-->
	<!-- wp:pattern {"slug":"ik2/home-latest-notes"} /-->
	<!-- wp:pattern {"slug":"ik2/home-projects-preview"} /-->
	<!-- wp:pattern {"slug":"ik2/home-speaking-preview"} /-->
</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
```

- [ ] **Step 2: Create `templates/404.html`**

```html
<!-- wp:template-part {"slug":"header","tagName":"header"} /-->

<!-- wp:group {"tagName":"main","className":"ik-section","layout":{"type":"constrained"}} -->
<main class="wp-block-group ik-section">
	<div class="container-full">
		<!-- wp:paragraph {"className":"ik-section__eyebrow"} --><p class="ik-section__eyebrow">// 404</p><!-- /wp:paragraph -->
		<!-- wp:heading {"level":1,"fontSize":"3-xl"} --><h1 class="wp-block-heading has-3-xl-font-size">Nothing at this URL.</h1><!-- /wp:heading -->
		<!-- wp:paragraph -->
		<p>The page you were looking for has either moved or never existed. Try the <a href="/">homepage</a> or browse <a href="/articles">all articles</a>.</p>
		<!-- /wp:paragraph -->
	</div>
</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
```

- [ ] **Step 3: Verify**

Run: `docker compose --profile tools run --rm composer quality`
Run: `docker compose --profile tools run --rm pnpm lint`
Expected: pass.

- [ ] **Step 4: Commit**

```bash
git add wp-content/themes/ik2/templates
git commit -m "Add front-page and 404 templates wired to home patterns"
```

---

## Task 15: Interactivity API scaffold

**Files:**
- Modify: `package.json`
- Create: `wp-content/themes/ik2/src/interactivity/cmd-palette/block.json`
- Create: `wp-content/themes/ik2/src/interactivity/cmd-palette/view.js`

- [ ] **Step 1: Add `@wordpress/interactivity` to devDependencies**

In `package.json`:

```json
"devDependencies": {
    "@wordpress/eslint-plugin": "^21.0.0",
    "@wordpress/interactivity": "^6.0.0",
    "@wordpress/prettier-config": "^4.0.0",
    "@wordpress/scripts": "^30.0.0",
    "@wordpress/stylelint-config": "^23.0.0",
    "sass": "^1.77.0"
}
```

- [ ] **Step 2: Install**

Run: `docker compose --profile tools run --rm pnpm install`
Expected: lockfile updated; `@wordpress/interactivity` resolves.

- [ ] **Step 3: Create stub block.json**

`wp-content/themes/ik2/src/interactivity/cmd-palette/block.json`:

```json
{
	"$schema": "https://schemas.wp.org/trunk/block.json",
	"apiVersion": 3,
	"name": "ik2/cmd-palette",
	"title": "Command Palette",
	"category": "theme",
	"description": "Site-wide ⌘K command palette. Not yet registered — scaffold only.",
	"textdomain": "ik2",
	"supports": {
		"interactivity": true,
		"html": false
	},
	"viewScriptModule": "file:./view.js"
}
```

- [ ] **Step 4: Create stub view.js**

`wp-content/themes/ik2/src/interactivity/cmd-palette/view.js`:

```js
/**
 * IK2 — Command palette (scaffold only).
 *
 * This file is intentionally a no-op. Real wiring (REST search,
 * keyboard handling) lands in a follow-up. Keeping the store
 * declaration here so the Interactivity API runtime is reachable
 * the moment we register the block.
 */
import { store } from '@wordpress/interactivity';

store( 'ik2/cmd-palette', {
	state: {
		isOpen: false,
		query: '',
	},
	actions: {
		toggle() {},
		close() {},
	},
} );
```

- [ ] **Step 5: Lint and build**

Run: `docker compose --profile tools run --rm pnpm lint:js`
Run: `docker compose --profile tools run --rm pnpm build`
Expected: both pass. Build won't emit a `cmd-palette` chunk because nothing imports `view.js`; that's fine for the scaffold.

- [ ] **Step 6: Commit**

```bash
git add package.json pnpm-lock.yaml wp-content/themes/ik2/src/interactivity
git commit -m "Scaffold cmd-palette Interactivity API block (not yet registered)"
```

---

## Task 16: Final QA — gates + visual verification

**Files:** (none modified — verification only)

- [ ] **Step 1: Run the full PHP gate**

Run: `docker compose --profile tools run --rm composer quality`
Expected: PHPCS + PHPStan both pass with zero new findings.

- [ ] **Step 2: Run the full JS/CSS gate**

Run: `docker compose --profile tools run --rm pnpm lint`
Expected: passes.

- [ ] **Step 3: Run a clean build**

Run:
```bash
rm -rf wp-content/themes/ik2/build
docker compose --profile tools run --rm pnpm build
```
Expected: `wp-content/themes/ik2/build/{index.js,index.css,editor.js,editor.css}` all exist; CSS is non-trivial in size (> 5 KB).

- [ ] **Step 4: Start the stack and seed sample content**

Run: `composer dev`
Wait for `composer dev:logs` to show `Apache/HTTPD ready` or equivalent.

Activate the theme and create seed posts:

```bash
composer dev:wp:cmd -- theme activate ik2
composer dev:wp:cmd -- term create category guide --slug=guide
composer dev:wp:cmd -- term create category note --slug=note
composer dev:wp:cmd -- post generate --count=3 --post_category=guide --post_title="Sample guide"
composer dev:wp:cmd -- post generate --count=6 --post_category=note --post_title="Sample note"
composer dev:wp:cmd -- option update show_on_front posts
```

Expected: theme activates without errors; categories created; 9 posts created.

- [ ] **Step 5: Visual verification in a browser**

Open `http://localhost` (or whatever the local stack exposes — check `docker compose ps` for the nginx port).

Confirm visually:
1. Header: wordmark with `~ $` prefix and blinking cursor, six nav links, "Search ⌘K" button, "Resume" button.
2. Hero: eyebrow, large headline, lede, two CTAs.
3. Featured topics: 3×2 grid of cards.
4. Evergreen guides: 2-up card grid showing the three sample guides.
5. Latest notes: list of six notes on the left, `/now` card on the right with a pulsing green dot.
6. Projects: 3-card grid with status pills (building/shipped/exploring).
7. Speaking: four talk rows.
8. Footer: 4-column layout with brand + social icons + three link columns; bottom bar with copyright and "Last published".
9. Resize to 768 and 375 widths — layouts collapse cleanly. Header `⌘K` button hides on mobile.

If anything is visually broken, file specific notes — but don't fix them in this task. Open follow-up tasks instead.

- [ ] **Step 6: Confirm Site Editor sees patterns**

Visit `/wp-admin/site-editor.php` → Patterns. Expected: a category "IK2 — Home" with six patterns listed.

- [ ] **Step 7: Final commit (only if any small fixes were needed)**

If visual verification revealed a one-line CSS tweak, fix it and commit:

```bash
git add wp-content/themes/ik2/src
git commit -m "Tighten <component> spacing after visual review"
```

Otherwise, no commit needed — the previous tasks already cover everything.

---

## Self-review notes (engineer can skip)

- **Spec coverage:** Build pipeline (T1), inc/* split (T2), theme.json sync (T3), SCSS foundation (T4), wordmark (T5), header + nav fallback (T6), footer + icons (T7), six homepage patterns (T8–T13), front-page + 404 templates (T14), Interactivity scaffold (T15), QA + visual (T16). Acceptance criteria from the spec all map.
- **Type/name consistency:** Class names match the prototype (`.ik-header`, `.ik-section`, `.ik-now`, `.ik-talk`, …). Pattern slugs match the spec exactly (`ik2/home-hero`, etc.). Pattern category slug is `ik2-home` (declared once in T2, referenced in every pattern docblock).
- **No placeholders:** Every step shows the exact code, command, or expected output.
