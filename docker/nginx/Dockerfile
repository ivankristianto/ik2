# syntax=docker/dockerfile:1.7

# The nginx image carries its own copy of WP core + wp-content so it can
# serve static assets without sharing a volume with the PHP-FPM container.
# It is built AFTER the app image and references it via build arg.

ARG APP_IMAGE=ghcr.io/ivankristianto/ik2-app:latest

FROM ${APP_IMAGE} AS app

FROM nginx:1.27-alpine

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Same path as the app image's webroot: SCRIPT_FILENAME is built from nginx's
# $document_root, and php-fpm resolves that path on ITS OWN filesystem — the
# two images must agree on the string. /var/www/app is also not a VOLUME path,
# so the served files always come from this build (see the app Dockerfile).
COPY --from=app /var/www/app /var/www/app

# Use 127.0.0.1, not localhost: localhost resolves to ::1 first, but nginx
# listens only on IPv4 (the base image's ipv6 listener is skipped because our
# default.conf differs from the packaged one), so localhost would 404/refuse.
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD wget -q -O - http://127.0.0.1/healthz || exit 1
