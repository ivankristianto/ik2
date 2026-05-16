# syntax=docker/dockerfile:1.7

# The nginx image carries its own copy of WP core + wp-content so it can
# serve static assets without sharing a volume with the PHP-FPM container.
# It is built AFTER the app image and references it via build arg.

ARG APP_IMAGE=ghcr.io/ivankristianto/ik2org-app:latest

FROM ${APP_IMAGE} AS app

FROM nginx:1.27-alpine

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

COPY --from=app /var/www/html /var/www/html

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD wget -q -O - http://localhost/healthz || exit 1
