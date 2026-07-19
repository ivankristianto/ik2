# ivankristianto.com

The WordPress project behind [ivankristianto.com](https://www.ivankristianto.com/) — Ivan Kristianto's personal engineering blog. WordPress, plugins, and the **IK2** block theme, with the _"Ink, Paper, and Signal"_ design system as the single source of truth for visual tokens.

## Repository layout

```
.
├── composer.json              # PHP 8.4, plugins via wpackagist, PHPCS + PHPStan
├── package.json               # pnpm, @wordpress/scripts, ESLint/Stylelint/Prettier
├── phpcs.xml.dist             # WordPress Coding Standards ruleset
├── phpstan.neon.dist          # PHPStan level 6 + phpstan-wordpress
├── design-system/             # "Ink, Paper, and Signal" — tokens, previews, UI kit, Claude skill
├── samples/                   # (gitignored) extended prototype sandbox
└── wp-content/
    ├── plugins/               # composer-managed (gitignored)
    ├── mu-plugins/            # custom must-use plugins (gitignored except own code)
    └── themes/
        └── ik2/               # the block theme
```

WordPress core itself is **not** managed in this repo — point your local WordPress install at this `wp-content/` directory (e.g. via `WP_CONTENT_DIR` in `wp-config.php`), or symlink the theme into your existing install.

## Requirements

-   **Docker** + **Docker Compose v2** (the only hard requirement for local dev)
-   Optional, if running PHP/Node directly on the host: **PHP 8.4**, **Node 24+**, **pnpm 9+** (`corepack enable && corepack prepare pnpm@latest --activate`), **Composer 2+**

## Local development

The whole toolchain runs in Docker — no PHP/Node/Composer/pnpm needed on the host. Helper services (`composer`, `pnpm`, `wp-cli`) live under the `tools` profile, so they're available via `docker compose run --rm <service>` without cluttering the always-on stack.

### First-time setup

```bash
cp .env.example .env
docker compose --profile tools run --rm composer install
docker compose --profile tools run --rm pnpm install
composer dev:build                                    # build images + start the stack
# WordPress  → http://localhost:8080
# DB         → localhost:3306 (user: wordpress / pw: wordpress)
```

First image build is 3–5 min (composer + pnpm + xdebug install inside the image). After that, **almost nothing needs a rebuild** — `wp-content/themes/`, `wp-content/mu-plugins/`, and `vendor/` are bind-mounted into the container.

### Composer dev commands

Composer wraps the most common Docker actions. Run these from the host (requires Composer 2+ installed locally — `brew install composer` on macOS):

```bash
composer dev                    # start the stack (app + nginx + db) in the background
composer dev:build              # rebuild images, then start
composer dev:logs               # tail logs from all services
composer dev:shell              # shell into the running app container
composer dev:wp                 # shell into persistent wp-cli container (then run `wp ...`)
composer dev:wp:cmd -- plugin list   # one-shot wp-cli command (note the `--`)
composer dev:down               # stop the stack (preserves DB and uploads)
composer dev:reset              # wipe DB + uploads, rebuild, start fresh
```

If you don't have Composer on the host, the underlying `docker compose ...` commands still work — see _Stack control_ below.

### The day-to-day loop

| You change…                              | What to run                                                | Need to rebuild? |
| :--------------------------------------- | :--------------------------------------------------------- | :--------------- |
| Theme PHP / mu-plugin PHP                | Reload browser                                             | No               |
| Theme CSS/JS source                      | Start the watcher (see below)                              | No               |
| `composer.json` (new plugin or PHP dep)  | `composer install` then `docker compose up -d --build app` | Yes              |
| `package.json` (new JS dep)              | `pnpm install`                                             | No               |
| `Dockerfile`, `docker/*`, PHP extensions | `docker compose up -d --build`                             | Yes              |
| `theme.json`                             | Reload browser                                             | No               |

### CSS / JS watch

`@wordpress/scripts` writes to `wp-content/themes/ik2/build/`, which is inside the bind mount — the container picks it up automatically.

```bash
docker compose --profile tools run --rm --service-ports pnpm start
```

Leave that terminal open while developing. Refresh browser to see changes.

### Helper services

All run via `docker compose --profile tools run --rm <service> <args>`:

```bash
# Composer
docker compose --profile tools run --rm composer install
docker compose --profile tools run --rm composer require wpackagist-plugin/wordpress-seo
docker compose --profile tools run --rm composer lint

# pnpm
docker compose --profile tools run --rm pnpm install
docker compose --profile tools run --rm pnpm build
docker compose --profile tools run --rm pnpm lint:js
docker compose --profile tools run --rm pnpm format

# WP-CLI
docker compose --profile tools run --rm wp-cli core install --url=http://localhost:8080 --title="Ivan" --admin_user=ivan --admin_email=ivan@example.com --admin_password=changeme
docker compose --profile tools run --rm wp-cli plugin list
docker compose --profile tools run --rm wp-cli theme activate ik2
```

If you prefer, alias the long form:

```bash
alias dc='docker compose'
alias dct='docker compose --profile tools run --rm'
# then:  dct composer install   ·   dct pnpm start   ·   dct wp-cli plugin list
```

### Stack control

```bash
docker compose up -d                       # start app + nginx + db
docker compose down                        # stop (preserves volumes)
docker compose down -v                     # stop + wipe DB and uploads
docker compose logs -f app                 # tail PHP-FPM logs
docker compose logs -f nginx               # tail nginx logs
docker compose exec app sh                 # shell into the running app container
docker compose exec db mariadb -u wordpress -pwordpress wordpress    # DB shell
docker compose restart app                 # restart PHP-FPM (e.g. after php.ini change)
```

### Xdebug

Xdebug is baked into the dev image (`INSTALL_XDEBUG=true` in `.env`). To activate at runtime:

```bash
XDEBUG_MODE=debug docker compose up -d app
```

Then point your IDE at `host.docker.internal:9003`, idekey `VSCODE`, and trigger requests with the _Xdebug Helper_ browser extension.

## Quality gates

Four linters run locally and in CI. **Third-party code under `wp-content/plugins/` is excluded from all four** — those plugins are composer-managed and we never modify them.

| Tool                                          | Scope                                            | Config                              | Excludes                                                  |
| :-------------------------------------------- | :----------------------------------------------- | :---------------------------------- | :-------------------------------------------------------- |
| **PHPCS** (WordPress Coding Standards)        | `wp-content/themes/ik2`, `wp-content/mu-plugins` | `phpcs.xml.dist`                    | `wp-content/plugins/*`, `vendor`, `node_modules`, `build` |
| **PHPStan** (level 6 + phpstan-wordpress)     | same as PHPCS                                    | `phpstan.neon.dist`                 | same as PHPCS                                             |
| **ESLint** (`@wordpress/eslint-plugin`)       | `wp-content/{themes,mu-plugins}/**/*.{js,jsx}`   | `.eslintrc.json` + `.eslintignore`  | same                                                      |
| **Stylelint** (`@wordpress/stylelint-config`) | `wp-content/{themes,mu-plugins}/**/*.{css,scss}` | `package.json` + `.stylelintignore` | same                                                      |

### Run all gates locally

```bash
docker compose --profile tools run --rm composer quality      # PHPCS + PHPStan
docker compose --profile tools run --rm pnpm lint             # ESLint + Stylelint
```

Individually:

```bash
docker compose --profile tools run --rm composer lint         # PHPCS only
docker compose --profile tools run --rm composer lint:fix     # PHPCBF auto-fix
docker compose --profile tools run --rm composer analyse      # PHPStan only
docker compose --profile tools run --rm pnpm lint:js
docker compose --profile tools run --rm pnpm lint:js:fix
docker compose --profile tools run --rm pnpm lint:css
docker compose --profile tools run --rm pnpm lint:css:fix
```

### CI

`.github/workflows/quality.yml` runs the four gates on every push to `main` and every pull request (two parallel jobs: `php` and `js`). To make these blocking, set GitHub **branch protection** on `main` to require both `Quality / PHP (PHPCS + PHPStan)` and `Quality / JS + CSS (ESLint + Stylelint)` to pass before merge.

The `Build & push images` workflow runs in parallel — it only **pushes** to GHCR on push to `main` (never on PR), so a failing quality gate blocks the merge before any image is tagged `latest`.

### Running tools on the host instead

If you do have PHP 8.4 / Node 24 / pnpm / Composer locally, the helper services are optional — `composer install`, `pnpm start`, etc. work natively and write to the same paths the bind mounts pick up.

## Common commands

### PHP

```bash
composer lint          # phpcs against wp-content/themes/ik2 and mu-plugins
composer lint:fix      # phpcbf auto-fix
composer analyse       # phpstan analyse (level 6)
```

### JS / CSS

```bash
pnpm start             # wp-scripts dev (watch)
pnpm build             # wp-scripts production build
pnpm lint:js           # ESLint on theme JS/JSX
pnpm lint:css          # Stylelint on theme CSS
pnpm format            # wp-prettier across the tree
```

### Adding plugins

Plugins are installed via [wpackagist](https://wpackagist.org/):

```bash
composer require wpackagist-plugin/wordpress-seo
composer require wpackagist-plugin/wp-super-cache
```

They land in `wp-content/plugins/{name}/` (routed by `composer/installers`) and stay out of git.

## Deployment

This project deploys to [Dokploy](https://dokploy.com/) as **two Docker images**, both built and pushed by GitHub Actions on every push to `main`:

| Image                                 | Contents                                                  | Base                          |
| :------------------------------------ | :-------------------------------------------------------- | :---------------------------- |
| `ghcr.io/ivankristianto/ik2org-app`   | PHP-FPM + WP core + `wp-content` + `vendor` + theme build | `wordpress:php8.4-fpm-alpine` |
| `ghcr.io/ivankristianto/ik2org-nginx` | nginx + the same public files (for static asset serving)  | `nginx:1.27-alpine`           |

### Image build pipeline

```
composer install (no-dev) ─┐
pnpm build                 ├─► production app image ─► nginx image
wordpress:php8.4-fpm-alpine┘
```

The `Dockerfile` has two final targets: `development` (with Xdebug, used by `docker-compose.yml`) and `production` (locked-down, used by GH Actions).

### GitHub Actions

`.github/workflows/build.yml` builds and pushes both images on push to `main` / version tag / manual dispatch. Set repo secret **`DOKPLOY_WEBHOOK_URL`** to auto-trigger a redeploy after each successful build.

### Dokploy

`docker-compose.prod.yml` is a reference compose for the Dokploy service. Set the following environment variables in the Dokploy UI:

-   `WP_HOME`, `WP_SITEURL`
-   `WORDPRESS_DB_HOST`, `WORDPRESS_DB_USER`, `WORDPRESS_DB_PASSWORD`, `WORDPRESS_DB_NAME`
-   8 × WordPress salts (generate via `curl https://api.wordpress.org/secret-key/1.1/salt/`)

`wp-content/uploads` is a Dokploy-managed persistent volume — everything else in the image is immutable and replaced on each deploy.

The MariaDB instance is **separate** (a Dokploy database service), not bundled in the app stack, so it survives redeploys independently.

### Caching

Two cache layers are baked into the image at build time — the production webroot is immutable, so nothing is written or configured at runtime:

-   **Object cache — `wp-redis`.** The `object-cache.php` drop-in is symlinked in during the build and the PhpRedis extension is compiled into the image. Connection details come from the `REDIS_SERVER` env var (parsed by `docker/wordpress/wp-config-redis.php`).
-   **Page cache — WP Super Cache.** The plugin normally writes its drop-ins when you activate it, but that can't happen against an immutable filesystem. So the `Dockerfile` bakes them from the plugin's shipped samples: `wp-content/advanced-cache.php` and `wp-content/wp-cache-config.php`, a writable `wp-content/cache/`, plus `WP_CACHE` + `WPCACHEHOME` defined in `wp-config.php` (via `docker/wordpress/wp-config-cache.php`) — without those two constants the drop-in never loads. **These are only needed at image build time**; removing the plugin from `composer.json` makes the build skip them cleanly.

WP Super Cache ships in Simple mode (no nginx rewrite rules needed) with caching **off** (`$cache_enabled = false`). Turn it on at **Settings → WP Super Cache → Caching On** once the plugin is active. It's a filesystem-backed page cache — it does not use Redis, but coexists with the Redis object cache above (they cache different layers).

## Theme

The **IK2** block theme at `wp-content/themes/ik2/` consumes the design system:

-   Slug / text-domain / asset handle: `ik2`. PHP namespace: `IK2\Theme`. Display name: "IK2".
-   `theme.json` is **a copy** of `design-system/theme.json`. When you change tokens in the design system, copy the file across (or wire up a sync script — TBD).
-   `style.css` is the theme header; visual styles come from `theme.json` plus any block CSS you add.
-   PHP uses `declare(strict_types=1)` and modern namespacing. PHPCS rules are relaxed where they fight modern PHP (short arrays, namespaces).

The theme expects WordPress 6.6+.

## Design system

`design-system/` is a self-contained Claude skill (`SKILL.md`) and human-readable design reference (`README.md`). It owns the visual rules — colors, type, spacing, voice, iconography. Read it before writing UI code. The token file (`colors_and_type.css`) and `theme.json` are the canonical sources; nothing else may hardcode colors or spacing.

See [`design-system/README.md`](./design-system/README.md) and [`CLAUDE.md`](./CLAUDE.md).

## Conventions

-   One accent color: **Signal Blue `#2563EB`**. Status colors (green/amber/red) are for status only.
-   System fonts only — no webfonts.
-   Borders + whitespace do the hierarchy work. No drop shadows beyond card hover.
-   First-person, working-engineer voice in any prose.

Full design rules live in `design-system/README.md`. Code-level guidance for AI assistants lives in `CLAUDE.md`.

## License

Proprietary — © Ivan Kristianto.
