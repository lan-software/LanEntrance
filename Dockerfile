#syntax=docker/dockerfile:1.7

# =============================================================================
# LanEntrance — Production Docker image
# =============================================================================
#
# Three-stage build (inherits LanBase for runtime, no Octane):
#   1. deps      — composer install + Wayfinder TypeScript generation
#   2. frontend  — Vite asset build (Node 22)
#   3. production — ghcr.io/lan-software/lanbase with FLAVOR=server
#
# Runtime env (handled by LanBase entrypoint — see LanBase/README.md):
#   FLAVOR        = server                (overridden below — no Octane)
#   ROLE          = web | worker | all    (default: all)
#   SKIP_MIGRATE  = 0 | 1                 (default: 1 — safe)
#
# See LanCore/docs/mil-std-498/SIP.md §3.4 for deployment patterns and
# LanCore/docs/mil-std-498/SSDD.md §3.1.1.5 for the per-app topology matrix.

# =============================================================================
# Stage 1: PHP dependency install + Wayfinder type generation
# =============================================================================
FROM composer:2 AS deps

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

COPY . .
RUN mkdir -p bootstrap/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/framework/cache \
        storage/logs
RUN composer dump-autoload --optimize --classmap-authoritative

ARG BUILD_APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=
RUN APP_KEY=${BUILD_APP_KEY} \
    APP_ENV=local \
    DB_CONNECTION=sqlite \
    DB_DATABASE=:memory: \
    php artisan wayfinder:generate

# =============================================================================
# Stage 2: Frontend asset build
# =============================================================================
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --frozen-lockfile

COPY . .

COPY --from=deps /app/resources/js/actions   ./resources/js/actions
COPY --from=deps /app/resources/js/routes    ./resources/js/routes
COPY --from=deps /app/resources/js/wayfinder ./resources/js/wayfinder

RUN printf '#!/bin/sh\nexit 0\n' > /usr/local/bin/php && chmod +x /usr/local/bin/php

RUN npm run build

# =============================================================================
# Stage 3: Production image (LanBase — FrankenPHP server mode)
# =============================================================================
FROM ghcr.io/lan-software/lanbase:php8.5 AS production

LABEL org.opencontainers.image.title="LanEntrance" \
      org.opencontainers.image.description="LanEntrance — Ticket validation / entry gate app for the Lan-Software ecosystem" \
      org.opencontainers.image.url="https://lan-software.de" \
      org.opencontainers.image.source="https://github.com/lan-software/lanentrance" \
      org.opencontainers.image.vendor="Lan-Software.de" \
      org.opencontainers.image.authors="Markus Kohn <post@markus-kohn.de>" \
      org.opencontainers.image.licenses="AGPL-3.0"

COPY --from=deps     /app              /var/www/html
COPY --from=frontend /app/public/build /var/www/html/public/build

RUN chown -R www-data:www-data storage bootstrap/cache

# Non-Octane app: run FrankenPHP in server mode via LanBase's supervisor configs.
ENV FLAVOR=server
