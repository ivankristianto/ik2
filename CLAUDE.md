# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repo is

The **WordPress project + design system** behind [ivankristianto.com](https://www.ivankristianto.com/) — Ivan Kristianto's personal engineering blog. PHP 8.4 / pnpm / Composer / Docker. Two-image deployment to Dokploy via GitHub Actions:

-   `Dockerfile` — multi-stage build producing the `app` image (PHP-FPM + WP core + `wp-content` + `vendor` + theme build)
-   `docker/nginx/Dockerfile` — builds the `nginx` image with the same public files baked in (no shared volume in prod)
-   `wp-content/themes/ik2/` — the block theme
-   `design-system/` — _"Ink, Paper, and Signal"_ tokens, previews, UI kit, and a self-contained Claude skill (`SKILL.md`)
-   `samples/` (gitignored) — extended HTML/JSX prototype sandbox

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

## Design rules that constrain code changes

These are enforced by the design brief (see `design-system/README.md` and `SKILL.md`). They affect what you may add, not just what you write:

-   **One accent color**: Signal Blue `#2563EB`. Anything that looks "accent-y" must be `var(--color-accent)`. Status colors (green/amber/red) are for status only.
-   **Warm paper `#F8F7F3`** is the page background; pure white is reserved for cards.
-   **System fonts only.** Do not add a `@font-face` or load a webfont unless the user asks.
-   **No shadows** beyond `--shadow-sm` (card hover) and `--shadow-md` (modal/palette). No gradients, no glassmorphism, no grain, no marketing-style hero imagery.
-   **No emoji in chrome.** Body content is the writer's call.
-   **Focus-visible is non-negotiable**: `2px solid var(--color-accent)`, `outline-offset: 3px`.
-   **Sentence case** for body and most UI; tags in lowercase; dates in monospace (e.g. `July 8, 2020`).
-   **Article max-width 720px**; container 1080px; full-bleed chrome 1280px.
-   **No transforms / scales / bounces** on hover. Transitions are `200ms ease` on `color`/`background`/`border`/`box-shadow` only.

When in doubt, widen margins instead of adding a shadow; tighten type instead of adding an icon.

## When the user asks to "build a page" or "add a component"

1. Decide the target: WordPress production theme (`wp-content/themes/ik2/`) or one of the prototype kits (`design-system/ui_kits/blog/`, `samples/`).
2. Reference tokens — never hardcode hex/px.
3. Mirror the patterns in `design-system/preview/` and the existing JSX/block templates.
4. For prototype kits: register the new JSX file in `index.html` (order matters — components attach to globals).
5. For the WP theme: prefer block templates (`templates/*.html`, `parts/*.html`) over PHP templates. PHP goes in `inc/` under the `IK2\Theme` namespace.
6. If you add a token, add it to **all** of `design-system/colors_and_type.css`, `design-system/theme.json`, **and** `wp-content/themes/ik2/theme.json`.

## When the user asks to "add a plugin"

```bash
docker compose --profile tools run --rm composer require wpackagist-plugin/<slug>
docker compose up -d --build app
```

Never download a plugin zip into `wp-content/plugins/`. That directory is gitignored and managed entirely by Composer (`installer-paths` in `composer.json` routes `type:wordpress-plugin` packages there).

## Voice (matters for any copy you write into mocks)

First-person, working-engineer, conversational. "I use Cloudflare CDN…", not "We are delighted to announce." Straight quotes, no em-dash flourishes, no exclamation marks unless something genuinely deserves one. CTAs are verb-first with no period: **Browse Guides**, **Read more**, **Subscribe via RSS**.

## Skill packaging

`design-system/` is itself a loadable Claude skill — `SKILL.md` has the front matter (`name: ivankristianto-design`, `user-invocable: true`). Edits to `README.md` / `SKILL.md` change skill behavior for anyone who has loaded this folder as a skill.
