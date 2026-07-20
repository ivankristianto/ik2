# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repo is

The **WordPress project + design system** behind [ivankristianto.com](https://www.ivankristianto.com/) — Ivan Kristianto's personal engineering blog. PHP 8.4 / pnpm / Composer / Docker. Two-image deployment to Dokploy via GitHub Actions:

- `Dockerfile` — multi-stage build producing the `app` image (PHP-FPM + WP core + `wp-content` + `vendor` + theme build)
- `docker/nginx/Dockerfile` — builds the `nginx` image with the same public files baked in (no shared volume in prod)
- `wp-content/themes/ik2/` — the block theme
- `design-system/` — _"Ink, Paper, and Signal"_ tokens, previews, UI kit, and a self-contained Claude skill (`SKILL.md`)
- `samples/` (gitignored) — extended HTML/JSX prototype sandbox

The design system was historically the _only_ thing in the repo. It's now one part of a real WordPress site.

## Commands

### Day-to-day

Composer scripts wrap the most common Docker actions — run from the host:

```bash
composer dev                                                      # start the stack (app + nginx + db)
composer dev:logs                                                 # tail all service logs
composer dev:shell                                                # shell into the app container
composer dev:wp                                                   # shell into the persistent wp-cli container
composer dev:wp:cmd -- plugin list                                # one-shot wp-cli command
composer dev:down                                                 # stop the stack (preserves volumes)
composer dev:reset                                                # wipe DB + uploads, rebuild, restart
composer dev:build                                                # rebuild images, then start
```

For things without a composer shortcut, drop to Docker directly:

```bash
docker compose --profile tools run --rm pnpm start                # CSS/JS watch
docker compose --profile tools run --rm composer require <pkg>    # add a PHP dep
```

**No rebuild is needed** for theme PHP, mu-plugin PHP, `theme.json`, or theme CSS/JS — `wp-content/themes`, `wp-content/mu-plugins`, and `vendor` are bind-mounted. Rebuild only when `Dockerfile`/PHP extensions change, or `composer.json` adds a new wpackagist plugin (composer-installed plugins live under `wp-content/plugins/` which is **not** bind-mounted).

### Quality gates

Four linters, all configured to skip `wp-content/plugins/` (composer-managed third-party code):

```bash
docker compose --profile tools run --rm composer quality     # PHPCS + PHPStan
docker compose --profile tools run --rm pnpm lint            # ESLint + Stylelint

# Individually
docker compose --profile tools run --rm composer lint        # phpcs (WPCS)
docker compose --profile tools run --rm composer lint:fix    # phpcbf
docker compose --profile tools run --rm composer analyse     # phpstan level 6
docker compose --profile tools run --rm pnpm lint:js
docker compose --profile tools run --rm pnpm lint:css
docker compose --profile tools run --rm pnpm format          # wp-prettier
```

CI: `.github/workflows/quality.yml` runs all four on push/PR. **Always run the relevant gate before finishing a task** — `composer quality` for PHP changes, `pnpm lint` for JS/CSS changes. PHPCS rules are relaxed for modern PHP (short arrays, namespaces); don't add `// phpcs:disable` comments without checking `phpcs.xml.dist` first.

### Reset

```bash
docker compose down -v          # wipe DB + uploads (full reset)
docker compose build --no-cache app && docker compose up -d   # full rebuild
```

## Design system status

`design-system/` is **both** a reference and a Claude skill (`SKILL.md` is loaded as `ivankristianto-design`). The token files (`colors_and_type.css`, `theme.json`) are the source of truth. The active block theme has its own **copy** of `theme.json` at `wp-content/themes/ik2/theme.json` — keep them in sync manually until we add a sync script.

The `design-system/ui_kits/blog/` and `samples/` HTML prototypes still run standalone via React + Babel UMD (`open design-system/ui_kits/blog/index.html`). They have no build step; JSX `<script type="text/babel">` order matters because components attach to globals.

## Architecture

### Image layout

```
Dockerfile
├─ Stage 1: composer:2          → vendor/ + wp-content/plugins/ (composer-installed)
├─ Stage 2: node:24-alpine      → wp-content/themes/.../build/
└─ Stage 3: wordpress:php8.4-fpm-alpine
            ├─ target development → adds Xdebug (compose default)
            └─ target production  → locked-down, opcache primed (GHA default)

docker/nginx/Dockerfile         → FROM app to grab public files, then nginx:alpine
```

`wp-content/plugins/` is **composer-managed via wpackagist**. Don't commit plugin code into that directory; add it to `composer.json` and rebuild the app image.

### Two parallel design-system kits

`design-system/ui_kits/blog/` and `samples/` are **separate, both real**, and **diverged**. They share component names (Header, Hero, ArticleCard, …) and token philosophy, but the files are not symlinked. When you change a component, decide which kit you're editing and check whether the change should propagate to the other.

| Kit                           | Stylesheet(s)                                                    | Status                               |
| :---------------------------- | :--------------------------------------------------------------- | :----------------------------------- |
| `design-system/ui_kits/blog/` | `kit.css` only                                                   | Canonical, in git                    |
| `samples/`                    | `assets/tokens.css` + `assets/kit.css` + `assets/extensions.css` | Sandbox, gitignored, has extra pages |

Neither is the production theme. The production theme is `wp-content/themes/ik2/`.

### Token flow

`design-system/colors_and_type.css` is the source of truth for color / type / spacing / radii / shadow tokens. `samples/assets/tokens.css` is a copy + extensions. `wp-content/themes/ik2/theme.json` is a copy of `design-system/theme.json`. Do **not** introduce new color or spacing values inline — add a token, then reference it.

### Iconography

Brand/social icons are inline SVG in `design-system/assets/icons/` and `samples/assets/icons/`. UI-affordance icons pull from Lucide CDN. Do not introduce an icon font or sprite.

### CSS delivery (theme)

The theme's front-end CSS is split for load performance — there is **no monolithic stylesheet**. Do not reintroduce one.

- **Critical CSS** — `src/critical.scss` (reset, typography, layout, skip link, wordmark, header nav, footer) compiles to `build/critical.css` and is **inlined into `<head>` on every page** by `inc/assets.php`. On the front page the home section styles are inlined alongside it, so the LCP page ships no render-blocking theme stylesheet.
- **Block styles** — each theme block owns a plain (hand-authored, no build step) `blocks/<name>/style.css` referenced from its `block.json` `style` field, so WordPress loads it only when the block is on the page. The plugin's `project-card` block works the same way. This is the pattern to follow for any new block.
- **Section styles** — page/template compositions that aren't blocks live in `src/styles/_*.scss`, are bundled by `src/sections/*.scss` (or compiled per-partial) to `build/section-*.css`, and are enqueued per template by `section_slugs_for_request()` in `inc/assets.php`.
- **Command palette** — `build/palette.css` loads asynchronously (never render-blocking).

`src/styles/_tokens.scss` aliases the `theme.json` custom properties to SCSS vars so partials read naturally; block `style.css` files reference the `var(--wp--preset--*)` custom properties directly.

**Build:** `pnpm build` (and `pnpm start` for watch) runs a single webpack pass — `wp-content/themes/ik2/webpack.config.js` declares one entry per output: `index` (command palette JS), `editor` (`editor.css`), and one SCSS entry per stylesheet (`critical`, `section-*`, `palette`) that webpack's sass → autoprefixer → cssnano pipeline emits as `build/<name>.css`. `webpack-remove-empty-scripts` drops the empty `.js` a CSS-only entry would leave behind. Block `style.css` files are plain CSS and need no build (bind-mounted). The Dockerfile smoke-test asserts `build/critical.css` + `build/section-home.css` exist.

## Design rules that constrain code changes

These are enforced by the design brief (see `design-system/README.md` and `SKILL.md`). They affect what you may add, not just what you write:

- **One accent color**: Signal Blue `#2563EB`. Anything that looks "accent-y" must be `var(--color-accent)`. Status colors (green/amber/red) are for status only.
- **Warm paper `#F8F7F3`** is the page background; pure white is reserved for cards.
- **System fonts only.** Do not add a `@font-face` or load a webfont unless the user asks.
- **No shadows** beyond `--shadow-sm` (card hover) and `--shadow-md` (modal/palette). No gradients, no glassmorphism, no grain, no marketing-style hero imagery.
- **No emoji in chrome.** Body content is the writer's call.
- **No `!important`.** Fix specificity, source order, or the markup instead of forcing overrides.
- **Focus-visible is non-negotiable**: `2px solid var(--color-accent)`, `outline-offset: 3px`.
- **Sentence case** for body and most UI; tags in lowercase; dates in monospace (e.g. `July 8, 2020`).
- **Article max-width 720px**; container 1080px; full-bleed chrome 1280px.
- **No transforms / scales / bounces** on hover. Transitions are `200ms ease` on `color`/`background`/`border`/`box-shadow` only.

When in doubt, widen margins instead of adding a shadow; tighten type instead of adding an icon.

## When the user asks to "build a page" or "add a component"

1. Decide the target: WordPress production theme (`wp-content/themes/ik2/`) or one of the prototype kits (`design-system/ui_kits/blog/`, `samples/`).
2. Reference tokens — never hardcode hex/px.
3. Mirror the patterns in `design-system/preview/` and the existing JSX/block templates.
4. For prototype kits: register the new JSX file in `index.html` (order matters — components attach to globals).
5. For the WP theme: prefer block templates (`templates/*.html`, `parts/*.html`) over PHP templates. PHP goes in `inc/` under the `IK2\Theme` namespace.
6. If you add a token, add it to **all** of `design-system/colors_and_type.css`, `design-system/theme.json`, **and** `wp-content/themes/ik2/theme.json`.

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

## Memory

Project-specific memory lives in `.claude/memory/`. The index is `.claude/memory/MEMORY.md` — read it at the start of each session and consult individual files when relevant. When saving new memories for this project, write them to `.claude/memory/`, not to the global memory store.

## Skill packaging

`design-system/` is itself a loadable Claude skill — `SKILL.md` has the front matter (`name: ivankristianto-design`, `user-invocable: true`). Edits to `README.md` / `SKILL.md` change skill behavior for anyone who has loaded this folder as a skill.

<!-- rtk-instructions v2 -->
# RTK (Rust Token Killer) - Token-Optimized Commands

## Golden Rule

**Always prefix commands with `rtk`**. If RTK has a dedicated filter, it uses it. If not, it passes through unchanged. This means RTK is always safe to use.

**Important**: Even in command chains with `&&`, use `rtk`:
```bash
# ❌ Wrong
git add . && git commit -m "msg" && git push

# ✅ Correct
rtk git add . && rtk git commit -m "msg" && rtk git push
```

## RTK Commands by Workflow

### Build & Compile (80-90% savings)
```bash
rtk cargo build         # Cargo build output
rtk cargo check         # Cargo check output
rtk cargo clippy        # Clippy warnings grouped by file (80%)
rtk tsc                 # TypeScript errors grouped by file/code (83%)
rtk lint                # ESLint/Biome violations grouped (84%)
rtk prettier --check    # Files needing format only (70%)
rtk next build          # Next.js build with route metrics (87%)
```

### Test (60-99% savings)
```bash
rtk cargo test          # Cargo test failures only (90%)
rtk go test             # Go test failures only (90%)
rtk jest                # Jest failures only (99.5%)
rtk vitest              # Vitest failures only (99.5%)
rtk playwright test     # Playwright failures only (94%)
rtk pytest              # Python test failures only (90%)
rtk rake test           # Ruby test failures only (90%)
rtk rspec               # RSpec test failures only (60%)
rtk test <cmd>          # Generic test wrapper - failures only
```

### Git (59-80% savings)
```bash
rtk git status          # Compact status
rtk git log             # Compact log (works with all git flags)
rtk git diff            # Compact diff (80%)
rtk git show            # Compact show (80%)
rtk git add             # Ultra-compact confirmations (59%)
rtk git commit          # Ultra-compact confirmations (59%)
rtk git push            # Ultra-compact confirmations
rtk git pull            # Ultra-compact confirmations
rtk git branch          # Compact branch list
rtk git fetch           # Compact fetch
rtk git stash           # Compact stash
rtk git worktree        # Compact worktree
```

Note: Git passthrough works for ALL subcommands, even those not explicitly listed.

### GitHub (26-87% savings)
```bash
rtk gh pr view <num>    # Compact PR view (87%)
rtk gh pr checks        # Compact PR checks (79%)
rtk gh run list         # Compact workflow runs (82%)
rtk gh issue list       # Compact issue list (80%)
rtk gh api              # Compact API responses (26%)
```

### JavaScript/TypeScript Tooling (70-90% savings)
```bash
rtk pnpm list           # Compact dependency tree (70%)
rtk pnpm outdated       # Compact outdated packages (80%)
rtk pnpm install        # Compact install output (90%)
rtk npm run <script>    # Compact npm script output
rtk npx <cmd>           # Compact npx command output
rtk prisma              # Prisma without ASCII art (88%)
```

### Files & Search (60-75% savings)
```bash
rtk ls <path>           # Tree format, compact (65%)
rtk read <file>         # Code reading with filtering (60%)
rtk grep <pattern>      # Search grouped by file (75%). Format flags (-c, -l, -L, -o, -Z) run raw.
rtk find <pattern>      # Find grouped by directory (70%)
```

### Analysis & Debug (70-90% savings)
```bash
rtk err <cmd>           # Filter errors only from any command
rtk log <file>          # Deduplicated logs with counts
rtk json <file>         # JSON structure without values
rtk deps                # Dependency overview
rtk env                 # Environment variables compact
rtk summary <cmd>       # Smart summary of command output
rtk diff                # Ultra-compact diffs
```

### Infrastructure (85% savings)
```bash
rtk docker ps           # Compact container list
rtk docker images       # Compact image list
rtk docker logs <c>     # Deduplicated logs
rtk kubectl get         # Compact resource list
rtk kubectl logs        # Deduplicated pod logs
```

### Network (65-70% savings)
```bash
rtk curl <url>          # Compact HTTP responses (70%)
rtk wget <url>          # Compact download output (65%)
```

### Meta Commands
```bash
rtk gain                # View token savings statistics
rtk gain --history      # View command history with savings
rtk discover            # Analyze Claude Code sessions for missed RTK usage
rtk proxy <cmd>         # Run command without filtering (for debugging)
rtk init                # Add RTK instructions to CLAUDE.md
rtk init --global       # Add RTK to ~/.claude/CLAUDE.md
```

## Token Savings Overview

| Category | Commands | Typical Savings |
|----------|----------|-----------------|
| Tests | vitest, playwright, cargo test | 90-99% |
| Build | next, tsc, lint, prettier | 70-87% |
| Git | status, log, diff, add, commit | 59-80% |
| GitHub | gh pr, gh run, gh issue | 26-87% |
| Package Managers | pnpm, npm, npx | 70-90% |
| Files | ls, read, grep, find | 60-75% |
| Infrastructure | docker, kubectl | 85% |
| Network | curl, wget | 65-70% |

Overall average: **60-90% token reduction** on common development operations.
<!-- /rtk-instructions -->