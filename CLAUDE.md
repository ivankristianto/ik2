# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repo is

The **WordPress project + design system** behind [ivankristianto.com](https://www.ivankristianto.com/) — Ivan Kristianto's personal engineering blog. PHP 8.4 / pnpm / Composer / Docker. Two-image deployment to Dokploy via GitHub Actions: an `app` image (PHP-FPM) and an `nginx` image with the same public files baked in — **no shared volume in prod**. Production deploys only when a release is cut (see **Releasing**); merging to `main` does not deploy.

The design system was historically the _only_ thing in the repo. It's now one part of a real WordPress site. `samples/` is gitignored.

## Commands

### Day-to-day

The `composer dev*` scripts wrap the most common Docker actions and are run **from the host** — see the `scripts` block in `composer.json` for the full list (`dev`, `dev:logs`, `dev:shell`, `dev:wp`, `dev:wp:cmd`, `dev:down`, `dev:reset`, `dev:build`).

For things without a composer shortcut, drop to Docker directly:

```bash
docker compose --profile tools run --rm pnpm start                # CSS/JS watch
docker compose --profile tools run --rm composer require <pkg>    # add a PHP dep
```

**No rebuild is needed** for theme PHP, mu-plugin PHP, `theme.json`, or theme CSS/JS — `wp-content/themes`, `wp-content/mu-plugins`, and `vendor` are bind-mounted. Rebuild only when `Dockerfile`/PHP extensions change, or `composer.json` adds a new wpackagist plugin (composer-installed plugins live under `wp-content/plugins/` which is **not** bind-mounted).

### Quality gates

Four linters, all configured to skip `wp-content/plugins/` (composer-managed third-party code). Every gate runs through the tools profile — `docker compose --profile tools run --rm composer <script>` for PHP (`quality`, `lint`, `lint:fix`, `analyse`) and `... run --rm pnpm <script>` for JS/CSS (`lint`, `lint:js`, `lint:css`, `format`):

```bash
docker compose --profile tools run --rm composer quality     # PHPCS + PHPStan
docker compose --profile tools run --rm pnpm lint            # ESLint + Stylelint
```

CI: `.github/workflows/quality.yml` runs all four on push/PR. **Always run the relevant gate before finishing a task** — `composer quality` for PHP changes, `pnpm lint` for JS/CSS changes. PHPCS rules are relaxed for modern PHP (short arrays, namespaces); don't add `// phpcs:disable` comments without checking `phpcs.xml.dist` first.

## Design system status

`design-system/` is **both** a reference and a self-contained Claude skill (`SKILL.md`, `name: ivankristianto-design`) that can be loaded as a skill. Its token files (`colors_and_type.css`, `theme.json`) are **mirrors** of `wp-content/themes/ik2/theme.json`, which is the source of truth. Keep them in sync manually until we add a sync script.

## Architecture

`wp-content/plugins/` is **composer-managed via wpackagist**. Don't commit plugin code into that directory; add it to `composer.json` and rebuild the app image.

### Prototype kits

`design-system/ui_kits/blog/` and `samples/` are two separate, diverged prototype kits — neither is the production theme, which is `wp-content/themes/ik2/`. Details in `design-system/CLAUDE.md` (loads when you work in that directory).

### Token flow

`wp-content/themes/ik2/theme.json` is the single source of truth for every design token. Its presets (`color.palette`, `typography.fontSizes`, `spacing.spacingSizes`, `border.radiusSizes` sm 4px / md 6px / lg 8px / pill / round, `shadow.presets` sm / md) become `--wp--preset--<type>--<slug>`. Its `settings.custom` groups (`fontWeight`, `lineHeight`, `letterSpacing`, `transition`, `focus`, `color.scrim`, `color.categoryTint`, `color.windowDot`, `width.full`, `fontSize.numeral`) become `--wp--custom--<group>--<key>` in kebab-case. SCSS partials reach them through the `$` aliases in `src/styles/_tokens.scss`, which holds aliases only, no values. Plain block `style.css` files use `var(--wp--preset--*)` / `var(--wp--custom--*)` directly. `design-system/theme.json` is a straight copy, `design-system/colors_and_type.css` mirrors the values under the prototype kits' `--color-*` names, and `samples/assets/tokens.css` is a copy + extensions of that. Do **not** introduce new values inline: add the token to `theme.json`, alias it in `_tokens.scss` if SCSS needs it, then reference it. The Tokens section of `DESIGN.md` has the full map.

### Iconography

Brand/social icons are inline SVG in `design-system/assets/icons/` and `samples/assets/icons/`. UI-affordance icons pull from Lucide CDN. Do not introduce an icon font or sprite.

### CSS delivery (theme)

The theme's front-end CSS is split for load performance — there is **no monolithic stylesheet**. Do not reintroduce one.

- **Critical CSS** — `src/critical.scss` (reset, typography, layout, skip link, wordmark, header nav, footer) compiles to `build/critical.css` and is **inlined into `<head>` on every page** by `inc/assets.php`. On the front page the home section styles are inlined alongside it, so the LCP page ships no render-blocking theme stylesheet.
- **Block styles** — each theme block owns a plain (hand-authored, no build step) `blocks/<name>/style.css` referenced from its `block.json` `style` field, so WordPress loads it only when the block is on the page. The plugin's `project-card` block works the same way. This is the pattern to follow for any new block.
- **Section styles** — page/template compositions that aren't blocks live in `src/styles/_*.scss`, are bundled by `src/sections/*.scss` (or compiled per-partial) to `build/section-*.css`, and are enqueued per template by `section_slugs_for_request()` in `inc/assets.php`.
- **Command palette** — `build/palette.css` loads asynchronously (never render-blocking).

`src/styles/_tokens.scss` aliases the `theme.json` custom properties to SCSS vars so partials read naturally; block `style.css` files reference the `var(--wp--preset--*)` custom properties directly.

**Build:** `pnpm build` (`pnpm start` to watch) runs one webpack pass with an entry per output — see `wp-content/themes/ik2/webpack.config.js`. Block `style.css` files are plain CSS and need no build (bind-mounted).

## Design rules that constrain code changes

These are enforced by the design brief (see `design-system/README.md` and `SKILL.md`). They affect what you may add, not just what you write:

- **One accent color**: Terracotta `#C2410C` (palette slug `signal`). Anything that looks "accent-y" must be `var(--wp--preset--color--signal)` in CSS or `$color-signal` in SCSS (`var(--color-accent)` in the prototype kits). The old blue accent is retired. Status colors (green/amber/red) are for status only.
- **Light only.** No dark-mode CSS and no `prefers-color-scheme` blocks. If a dark mode comes later, it will be added through `theme.json` (a style variation or settings).
- **Warm paper `#F8F7F3`** is the page background; pure white is reserved for cards.
- **System fonts only.** Do not add a `@font-face` or load a webfont unless the user asks.
- **No shadows** beyond `--shadow-sm` (card hover) and `--shadow-md` (modal/palette). No gradients, no glassmorphism, no grain, no marketing-style hero imagery.
- **No emoji in chrome.** Body content is the writer's call.
- **No `!important`.** Fix specificity, source order, or the markup instead of forcing overrides.
- **Focus-visible is non-negotiable**: `2px solid` accent, `outline-offset: 3px` (`outline: $focus; outline-offset: $focus-offset;` in SCSS).
- **Sentence case** for body and most UI; tags in lowercase; dates in monospace (e.g. `July 8, 2020`).
- **Article max-width 720px**; container 1080px; full-bleed chrome 1280px.
- **No transforms / scales / bounces** on hover. Transitions are `200ms ease` (`$transition-base`, `var(--wp--custom--transition--base)`) on `color`/`background`/`border`/`box-shadow` only.

When in doubt, widen margins instead of adding a shadow; tighten type instead of adding an icon.

## When the user asks to "build a page" or "add a component"

1. Decide the target: WordPress production theme (`wp-content/themes/ik2/`) or one of the prototype kits (`design-system/ui_kits/blog/`, `samples/`).
2. Reference tokens — never hardcode hex/px.
3. Mirror the patterns in `design-system/preview/` and the existing JSX/block templates.
4. For prototype kits: register the new JSX file in `index.html` (order matters — components attach to globals).
5. For the WP theme: prefer block templates (`templates/*.html`, `parts/*.html`) over PHP templates. PHP goes in `inc/` under the `IK2\Theme` namespace.
6. If you add a token, add it to `wp-content/themes/ik2/theme.json` first, alias it in `src/styles/_tokens.scss` if SCSS uses it, then mirror it into `design-system/theme.json` **and** `design-system/colors_and_type.css`.

## PHP conventions

- **Prefer named functions and class methods over anonymous closures and arrow functions.** Hook callbacks (`add_action` / `add_filter`), `auth_callback`, `sanitize_callback`, and non-trivial `usort` / `array_map` / `array_filter` callbacks should be named static methods on the registering class — pass `[ self::class, 'method_name' ]`. Local closures inside `render.php` / template files should be namespaced functions in the same file. Named callables show up in stack traces, can be removed by `remove_action` / `remove_filter`, and are easier to test.

## When the user asks to "add a plugin"

```bash
docker compose --profile tools run --rm composer require wpackagist-plugin/<slug>
docker compose up -d --build app
```

Never download a plugin zip into `wp-content/plugins/`. That directory is gitignored and managed entirely by Composer (`installer-paths` in `composer.json` routes `type:wordpress-plugin` packages there).

## Voice (matters for any copy you write into mocks)

First-person, working-engineer, conversational. "I use Cloudflare CDN…", not "We are delighted to announce." Straight quotes, no em-dash flourishes, no exclamation marks unless something genuinely deserves one. CTAs are verb-first with no period: **Browse Guides**, **Read more**, **Subscribe via RSS**.

## Committing

**Always use atomic commits.** One logical change per commit. Never bundle unrelated work into a single commit, even when several files happen to be modified at the same time.

- Group by intent, not by file or directory. A template change + the SCSS that styles it is one commit; a Docker config tweak is a separate commit; an unrelated pattern fix is its own commit.
- Stage explicitly (`git add <files>`), never `git add -A` / `git add .`. If unrelated changes share a file, split with `git add -p`.
- Commit messages follow Conventional Commits: `type(scope): summary`. Types in use here: `feat`, `fix`, `chore`, `refactor`, `docs`, `style`, `test`, `ci`. Scope is the affected area (`theme`, `single`, `articles`, `compose`, `ci`, etc.). Subject is imperative, lowercase, no trailing period, under ~72 chars.
- One commit should ideally pass lint/build on its own. If a commit needs a follow-up to compile, the split is wrong — fold them, or restructure the changes.
- Untracked debug artifacts (screenshots, dumps, `artifacts/`) are never committed without an explicit ask.

## Releasing

`build.yml` does not run on pushes to `main` or on PRs. It runs only when a `v*` tag is pushed or when triggered by hand (`workflow_dispatch`, which pushes `<branch>` + `sha-*` images and does not deploy). On a tag it builds the semver images, moves `latest`, and calls the Dokploy webhook. `docker-compose.prod.yml` pulls `${IMAGE_TAG:-latest}`.

Cut a release from the host (it needs your git and `gh` credentials, so not from the tools container):

```bash
pnpm release patch --dry-run   # check the guards and the computed version first
pnpm release patch             # or minor / major / an explicit X.Y.Z
```

`scripts/release.mjs` refuses to run unless you're on a clean `main` that matches `origin/main`, the tag is new, and the Quality workflow passed for `HEAD`. It then bumps `version` in `package.json`, commits `chore(release): vX.Y.Z`, tags, pushes both atomically, and creates the GitHub release with generated notes (built from the Conventional Commit subjects, so write them well). `node scripts/release.mjs <bump>` does the same without pnpm.

Semver for a website: **patch** for fixes and dependency bumps, **minor** for new features, templates, or blocks, **major** for breaking changes to infrastructure or content structure (PHP major, URL scheme, data migrations).

Only cut a release when the user asks. After the tag is pushed, watch the run with `gh run watch` and confirm the Dokploy deploy. To roll back, set `IMAGE_TAG` to the previous version in the Dokploy UI and redeploy; don't delete or move tags.

## Memory

Project-specific memory lives in `.claude/memory/`. The index is `.claude/memory/MEMORY.md` — read it at the start of each session and consult individual files when relevant. When saving new memories for this project, write them to `.claude/memory/`, not to the global memory store.

## Skill packaging

`design-system/` is itself a loadable Claude skill — `SKILL.md` has the front matter (`name: ivankristianto-design`, `user-invocable: true`). Edits to `README.md` / `SKILL.md` change skill behavior for anyone who has loaded this folder as a skill.
