# syntax=docker/dockerfile:1.7

# ---------------------------------------------------------------------------
# Stage 1 — composer dependencies (no-dev, optimised autoloader)
# ---------------------------------------------------------------------------
FROM composer:2 AS composer-build

WORKDIR /app

COPY composer.json composer.lock* ./

RUN --mount=type=cache,target=/tmp/cache \
    composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --ignore-platform-req=ext-* \
    && composer dump-autoload --optimize --classmap-authoritative

# Ensure the wp-content paths exist even if composer installed nothing into them,
# so the COPY --from=composer-build lines in the runtime stage never fail.
RUN mkdir -p wp-content/plugins wp-content/mu-plugins

# The mcp-adapter plugin carries its OWN composer.json (runtime dependency:
# wordpress/php-mcp-schema) and bootstraps from its own vendor/autoload.php via
# includes/Autoloader.php — without it the plugin bails out with an admin notice
# and registers nothing. The top-level install above places the plugin but does
# not resolve a wordpress-plugin package's nested dependencies, so install them
# explicitly and fail loudly if the autoloader never materialises.
RUN --mount=type=cache,target=/tmp/cache \
    composer install \
        --no-dev \
        --no-scripts \
        --prefer-dist \
        --ignore-platform-req=ext-* \
        --optimize-autoloader \
        --working-dir=wp-content/plugins/mcp-adapter \
    && test -f wp-content/plugins/mcp-adapter/vendor/autoload.php


# ---------------------------------------------------------------------------
# Stage 2 — frontend build (pnpm + @wordpress/scripts)
# ---------------------------------------------------------------------------
FROM node:24-alpine AS node-build

RUN corepack enable && corepack prepare pnpm@11.14.0 --activate

WORKDIR /app

# pnpm-workspace.yaml carries the dependency build-script approvals — without
# it pnpm 11 hard-errors on ignored builds under CI.
COPY package.json pnpm-lock.yaml* pnpm-workspace.yaml* ./

RUN --mount=type=cache,id=pnpm-store,target=/root/.local/share/pnpm/store \
    pnpm install --frozen-lockfile

COPY wp-content ./wp-content

# Build the theme assets and fail loudly if the expected output is missing —
# a silently empty build would ship an unstyled site (assets.php enqueues are
# guarded by file_exists).
RUN pnpm build \
    && test -s wp-content/themes/ik2/build/style-index.css \
    && test -s wp-content/themes/ik2/build/index.js \
    && test -f wp-content/themes/ik2/build/editor.css


# ---------------------------------------------------------------------------
# Stage 3 — base runtime (shared between dev + prod)
# ---------------------------------------------------------------------------
FROM wordpress:7-php8.5-fpm-alpine AS base

# OS deps for image handling, healthcheck
RUN apk add --no-cache \
        bash \
        curl \
        less \
        git \
        mariadb-client

# PhpRedis — required by the wp-redis object cache drop-in; without it the
# plugin silently falls back to a non-persistent in-process cache.
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# PHP configuration
COPY docker/php/php.ini    /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/www.conf   /usr/local/etc/php-fpm.d/zz-www.conf

# WordPress core, baked at /var/www/app — NOT /var/www/html. The wordpress base
# image declares `VOLUME /var/www/html`, so any code baked there becomes an
# anonymous volume at runtime that an orchestrator (Dokploy/Compose) preserves
# across redeploys — every deploy after the first keeps serving the FIRST
# deploy's volume snapshot instead of the new image. Same root cause (and same
# fix) as the `cli` stage below: never put code at the volume path. The
# inherited /var/www/html anonymous volume is left empty and unused.
#
# Baking core at build time (instead of the entrypoint's startup copy) also
# keeps the image self-contained, so the nginx image — built FROM this one —
# gets a complete webroot. The entrypoint operates on WORKDIR, sees index.php
# there, and skips its core copy.
#
# wp-config.php is baked from the env-driven wp-config-docker.php so the image
# is usable without the entrypoint (the `cli` image below copies this webroot
# wholesale and would otherwise have no config). The app's entrypoint sees it
# exists and skips its own generation; all values still resolve from env.
RUN mkdir -p /var/www/app \
    && cp -a /usr/src/wordpress/. /var/www/app/ \
    && cp /var/www/app/wp-config-docker.php /var/www/app/wp-config.php \
    && rm -rf /var/www/app/wp-content/plugins/* \
              /var/www/app/wp-content/themes/* \
    && chown www-data:www-data /var/www/app

# Object cache config: parse REDIS_SERVER (redis://user:pass@host:port/db) into
# the $redis_server global wp-redis reads. Hooked into wp-config.php right after
# its opening <?php so it runs for every request; fail the build loudly if the
# hook didn't land.
COPY --chown=www-data:www-data docker/wordpress/wp-config-redis.php /var/www/app/wp-config-redis.php
RUN sed -i "1a require_once __DIR__ . '/wp-config-redis.php';" /var/www/app/wp-config.php \
    && grep -q "wp-config-redis.php" /var/www/app/wp-config.php

# Composer plugins + vendor
COPY --from=composer-build --chown=www-data:www-data /app/wp-content/plugins    /var/www/app/wp-content/plugins
COPY --from=composer-build --chown=www-data:www-data /app/wp-content/mu-plugins /var/www/app/wp-content/mu-plugins
COPY --from=composer-build --chown=www-data:www-data /app/vendor                /var/www/app/vendor

# Theme source + first-party plugin (committed to this repo, unlike the
# composer-managed third-party plugins copied above).
COPY --chown=www-data:www-data wp-content/themes       /var/www/app/wp-content/themes
COPY --chown=www-data:www-data wp-content/plugins/ik2  /var/www/app/wp-content/plugins/ik2
COPY --chown=www-data:www-data wp-content/mu-plugins   /var/www/app/wp-content/mu-plugins

# Built theme assets (from pnpm build)
COPY --from=node-build --chown=www-data:www-data /app/wp-content /var/www/app/wp-content

# Enable the wp-redis object cache drop-in. Relative symlink so it resolves in
# every image that copies this webroot (cli, nginx) and under the dev bind
# mount of ./wp-content/plugins. Skipped when the plugin isn't installed (e.g.
# removed from composer.json) — WordPress treats a missing/dangling
# object-cache.php as "no drop-in" and falls back to its internal cache.
RUN if [ -s /var/www/app/wp-content/plugins/wp-redis/object-cache.php ]; then \
        ln -s plugins/wp-redis/object-cache.php /var/www/app/wp-content/object-cache.php \
        && chown -h www-data:www-data /var/www/app/wp-content/object-cache.php; \
    else \
        echo "wp-redis drop-in not found — skipping object-cache.php symlink"; \
    fi

# The official entrypoint operates on the current directory, so pointing
# WORKDIR at the non-volume webroot is all it takes for php-fpm + entrypoint
# to run this build's code.
WORKDIR /var/www/app


# ---------------------------------------------------------------------------
# Stage 4a — development (xdebug + writable filesystem)
# ---------------------------------------------------------------------------
FROM base AS development

ARG INSTALL_XDEBUG=true

RUN if [ "$INSTALL_XDEBUG" = "true" ]; then \
        apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers \
        && pecl install xdebug \
        && docker-php-ext-enable xdebug \
        && apk del .build-deps; \
    fi

COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/zz-xdebug.ini
COPY docker/php/dev.ini    /usr/local/etc/php/conf.d/zz-dev.ini


# ---------------------------------------------------------------------------
# Stage 4b — production (immutable, opcache primed)
# ---------------------------------------------------------------------------
FROM base AS production

# Tighten filesystem permissions
RUN find /var/www/app/wp-content -type d -exec chmod 755 {} \; \
    && find /var/www/app/wp-content -type f -exec chmod 644 {} \;

# Healthcheck — PHP-FPM ping
HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 \
    CMD php-fpm -t || exit 1


# ---------------------------------------------------------------------------
# Stage 4c — wp-cli (standalone, self-contained, no runtime volume writes)
# ---------------------------------------------------------------------------
# A dedicated wp-cli image so the prod wp-cli service no longer reuses the app
# (php-fpm) image. It carries the same WordPress core / theme / plugins /
# mu-plugins / vendor / baked wp-config as the app — copied wholesale from
# `base` — on the lean wordpress:cli runtime. `wp` ships with this base image,
# on PATH, run as www-data (uid 82), no --allow-root needed.
#
# wordpress:cli inherits `VOLUME /var/www/html` from its base. ANY webroot baked
# at that path becomes an anonymous volume at runtime that an orchestrator
# (Dokploy/Compose) preserves across redeploys — shadowing the image with stale,
# often root-owned content. That is what caused the earlier
# "cp: can't create directory '.../wp-content/themes': Permission denied" restart
# loop: a previous entrypoint tried to refresh code INSIDE that persisted volume
# as www-data, which could not write into a root-owned wp-content. No amount of
# build-time seeding fixes an ALREADY-persisted volume.
#
# Fix: never use the volume path for code. The webroot lives at /var/www/app
# (NOT a volume, same path as the app and nginx images); wp runs from there;
# only wp-content/uploads is a mounted volume. Nothing is written into a volume
# at startup, so there is no permission problem and the cli always runs THIS
# build's code, fresh per deploy. The inherited /var/www/html anonymous volume
# is left empty and unused.
FROM wordpress:cli-php8.5 AS cli

USER root

# Match the app's PHP runtime config so wp-cli bootstraps WordPress (and its
# plugins/mu-plugins) identically to php-fpm.
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini

# PhpRedis, same as the app image — so `wp redis info` / `wp cache flush`
# operate on the real Redis backend instead of the in-process fallback.
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Full, ready-to-run webroot (core + vendor + plugins + theme build + wp-config),
# baked at a NON-volume path, owned by www-data (uid 82). Pre-create
# wp-content/uploads so the uploads volume's mountpoint exists and is
# www-data-owned (the volume is mounted here, not at /var/www/html).
COPY --from=base --chown=82:82 /var/www/app /var/www/app
RUN mkdir -p /var/www/app/wp-content/uploads \
    && chown -R 82:82 /var/www/app

# Run as www-data (uid 82) from the baked, non-volume webroot. The inherited
# wordpress:cli entrypoint (prepends `wp` to wp-cli subcommands) is reused as-is.
USER www-data
WORKDIR /var/www/app
