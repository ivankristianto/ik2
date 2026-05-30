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


# ---------------------------------------------------------------------------
# Stage 2 — frontend build (pnpm + @wordpress/scripts)
# ---------------------------------------------------------------------------
FROM node:24-alpine AS node-build

RUN corepack enable && corepack prepare pnpm@9.12.0 --activate

WORKDIR /app

COPY package.json pnpm-lock.yaml* ./

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

# PHP configuration
COPY docker/php/php.ini    /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/www.conf   /usr/local/etc/php-fpm.d/zz-www.conf

# wp-cli — the prod wp-cli service runs this same image (it has the code +
# wp-config), so `wp` must be on PATH. Runs as www-data, no --allow-root needed.
RUN curl -fsSL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
        -o /usr/local/bin/wp \
    && chmod +x /usr/local/bin/wp

# WordPress core. The official image only populates /var/www/html at container
# startup (via its entrypoint), so the nginx image — built FROM this one —
# would otherwise copy a webroot with no index.php or wp-includes and 404 every
# request. Baking core in at build time makes the image self-contained; at
# runtime the entrypoint sees index.php and skips its core copy.
#
# wp-config.php is baked from the env-driven wp-config-docker.php so the image
# is usable without the entrypoint (the wp-cli service overrides the entrypoint
# and would otherwise have no config). The app's entrypoint sees it exists and
# skips its own generation; all values still resolve from env at runtime.
RUN cp -a /usr/src/wordpress/. /var/www/html/ \
    && cp /var/www/html/wp-config-docker.php /var/www/html/wp-config.php \
    && rm -rf /var/www/html/wp-content/plugins/* \
              /var/www/html/wp-content/themes/*

# Composer plugins + vendor
COPY --from=composer-build --chown=www-data:www-data /app/wp-content/plugins    /var/www/html/wp-content/plugins
COPY --from=composer-build --chown=www-data:www-data /app/wp-content/mu-plugins /var/www/html/wp-content/mu-plugins
COPY --from=composer-build --chown=www-data:www-data /app/vendor                /var/www/html/vendor

# Theme source
COPY --chown=www-data:www-data wp-content/themes    /var/www/html/wp-content/themes
COPY --chown=www-data:www-data wp-content/mu-plugins /var/www/html/wp-content/mu-plugins

# Built theme assets (from pnpm build)
COPY --from=node-build --chown=www-data:www-data /app/wp-content /var/www/html/wp-content

WORKDIR /var/www/html


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
RUN find /var/www/html/wp-content -type d -exec chmod 755 {} \; \
    && find /var/www/html/wp-content -type f -exec chmod 644 {} \;

# Healthcheck — PHP-FPM ping
HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 \
    CMD php-fpm -t || exit 1
