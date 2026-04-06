LanEntrance Software Product Specification (MIL-STD-498 Adapted)
===

# 1. Scope

## 1.1 Identification

* Title: LanEntrance Software Product Specification
* Short Name: LanEntrance SPS
* System Name: LanEntrance
* Ecosystem: LanSoftware
* Version: Draft v0.1
* Status: Working Draft

This document describes the executable software product, its configuration, dependencies, and deployment specification.

## 1.2 System overview

See SSS Section 1.2. LanEntrance is delivered as a Docker container running a Laravel 13 backend with pre-built Vue 3 frontend assets.

## 1.3 Document overview

This document covers the software inventory, build process, runtime configuration, deployment, and operational dependencies.

---

# 2. Referenced documents

| ID      | Title                | Version    |
| ------- | -------------------- | ---------- |
| REF-001 | LanEntrance SDD      | Draft v0.1 |
| REF-002 | LanEntrance SSDD     | Draft v0.1 |
| REF-003 | LanEntrance SDP      | Draft v0.1 |

---

# 3. Software inventory

## 3.1 Runtime components

| Component          | Technology       | Version    | Source       |
| ------------------ | ---------------- | ---------- | ------------ |
| PHP Runtime        | PHP              | 8.5        | Docker image |
| Application Framework | Laravel       | 13.0       | Packagist    |
| Authentication     | Laravel Fortify  | 1.34       | Packagist    |
| SPA Bridge         | Inertia Laravel  | 3.0        | Packagist    |
| Route Generation   | Laravel Wayfinder| 0.1.14     | Packagist    |
| Frontend Framework | Vue.js           | 3.5        | npm          |
| Frontend Bridge    | Inertia Vue 3    | 3.0        | npm          |
| CSS Framework      | Tailwind CSS     | 4.1        | npm          |
| UI Primitives      | Reka UI          | 2.x        | npm          |
| QR Scanning        | vue-qrcode-reader| 5.7        | npm          |
| Icons              | Lucide Vue Next  | latest     | npm          |
| Build Tool         | Vite             | 6.x        | npm          |

## 3.2 Development/test components

| Component          | Technology       | Version    | Purpose         |
| ------------------ | ---------------- | ---------- | --------------- |
| Test Framework     | Pest             | 4.4        | PHP testing     |
| Component Tests    | Vitest           | 4.1        | Vue testing     |
| E2E Tests          | Playwright       | 1.52       | Browser testing |
| Vue Test Utils     | vue-test-utils   | 2.4        | Component mount |
| Dev Environment    | Laravel Sail     | latest     | Docker dev env  |
| Linter (PHP)       | Laravel Pint     | latest     | PSR-12 style    |
| Linter (JS)        | ESLint           | latest     | JS/Vue style    |
| Formatter          | Prettier         | latest     | Code formatting |

---

# 4. Build process

## 4.1 Backend build

```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Generate application key (first deploy only)
php artisan key:generate

# Run database migrations
php artisan migrate --force

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 4.2 Frontend build

```bash
# Install Node.js dependencies
npm ci

# Generate typed routes (Wayfinder)
php artisan wayfinder:generate

# Production build
npm run build
```

**Output**: Built assets in `public/build/` (Vite manifest + hashed JS/CSS bundles).

## 4.3 Docker image build

The Docker image is built via CI (`docker-publish.yml`) and published to GitHub Container Registry (GHCR).

```dockerfile
# Multi-stage build (conceptual)
FROM php:8.5-fpm AS base
# Install PHP extensions, Composer dependencies
# Copy application code

FROM node:22 AS frontend
# Install npm dependencies, build assets

FROM base AS production
# Copy built frontend assets
# Configure PHP-FPM, Nginx/Caddy
```

---

# 5. Runtime configuration

## 5.1 Environment variables

All configuration is via environment variables (`.env` file or container environment).

### Application configuration

| Variable          | Required | Default                    | Description                     |
| ----------------- | -------- | -------------------------- | ------------------------------- |
| `APP_NAME`        | No       | `LanEntrance`             | Application display name        |
| `APP_ENV`         | Yes      | `production`               | Environment (local/production)  |
| `APP_KEY`         | Yes      | —                          | Encryption key (base64:...)     |
| `APP_DEBUG`       | No       | `false`                    | Debug mode                      |
| `APP_URL`         | Yes      | —                          | Public application URL          |

### Database configuration

| Variable          | Required | Default     | Description                     |
| ----------------- | -------- | ----------- | ------------------------------- |
| `DB_CONNECTION`   | No       | `mysql`     | Database driver                 |
| `DB_HOST`         | Yes      | —           | Database host                   |
| `DB_PORT`         | No       | `3306`      | Database port                   |
| `DB_DATABASE`     | Yes      | —           | Database name                   |
| `DB_USERNAME`     | Yes      | —           | Database user                   |
| `DB_PASSWORD`     | Yes      | —           | Database password               |

### LanCore integration

| Variable                       | Required | Default                        | Description                       |
| ------------------------------ | -------- | ------------------------------ | --------------------------------- |
| `LANCORE_ENABLED`              | No       | `false`                        | Enable LanCore SSO integration    |
| `LANCORE_BASE_URL`             | Yes*     | `http://lancore.lan`           | LanCore public URL (for SSO redirects) |
| `LANCORE_INTERNAL_URL`         | No       | (falls back to BASE_URL)       | LanCore internal URL (service-to-service) |
| `LANCORE_TOKEN`                | Yes*     | —                              | Bearer token for LanCore API      |
| `LANCORE_APP_SLUG`             | No       | `lanentrance`                  | Application slug in LanCore       |
| `LANCORE_CALLBACK_URL`         | No       | `{APP_URL}/auth/callback`      | SSO callback URL                  |
| `LANCORE_ROLES_WEBHOOK_SECRET` | Yes*     | —                              | HMAC secret for role webhooks     |
| `LANCORE_TIMEOUT`              | No       | `5`                            | API timeout in seconds            |
| `LANCORE_RETRIES`              | No       | `2`                            | API retry count                   |
| `LANCORE_RETRY_DELAY`          | No       | `100`                          | API retry delay in milliseconds   |

*Required when `LANCORE_ENABLED=true`

### Session and cache

| Variable            | Required | Default    | Description                     |
| ------------------- | -------- | ---------- | ------------------------------- |
| `SESSION_DRIVER`    | No       | `database` | Session storage driver          |
| `SESSION_LIFETIME`  | No       | `120`      | Session lifetime in minutes     |
| `CACHE_STORE`       | No       | `database` | Cache storage driver            |

### Fortify / authentication

| Variable                      | Required | Default | Description                  |
| ----------------------------- | -------- | ------- | ---------------------------- |
| `FORTIFY_FEATURES_REGISTRATION` | No    | `true`  | Enable user registration     |
| `FORTIFY_FEATURES_TWO_FACTOR`   | No    | `true`  | Enable 2FA                   |

---

## 5.2 Configuration files

| File                   | Purpose                              |
| ---------------------- | ------------------------------------ |
| `config/lancore.php`   | LanCore integration configuration    |
| `config/fortify.php`   | Authentication feature flags         |
| `config/auth.php`      | Auth guards and providers            |
| `config/app.php`       | Application settings                 |
| `config/database.php`  | Database connections                 |
| `config/session.php`   | Session configuration                |

---

## 5.3 Environment variable checklist

Quick reference for deployment. Variables marked **Prod** are required in production; **Dev** are only needed for local development.

| Variable                       | Prod | Dev | Notes                                     |
| ------------------------------ | ---- | --- | ----------------------------------------- |
| `APP_KEY`                      | Yes  | Yes | Generate with `php artisan key:generate`  |
| `APP_ENV`                      | Yes  | Yes | `production` / `local`                    |
| `APP_URL`                      | Yes  | Yes | Must be HTTPS in production (camera API)  |
| `DB_HOST`                      | Yes  | Yes | Database server hostname                  |
| `DB_DATABASE`                  | Yes  | Yes | Database name                             |
| `DB_USERNAME`                  | Yes  | Yes | Database credentials                      |
| `DB_PASSWORD`                  | Yes  | Yes | Database credentials                      |
| `LANCORE_ENABLED`              | Yes  | Yes | `true` to enable SSO + entrance features  |
| `LANCORE_BASE_URL`             | Yes  | Yes | Public LanCore URL (SSO redirects)        |
| `LANCORE_TOKEN`                | Yes  | Yes | API bearer token                          |
| `LANCORE_ROLES_WEBHOOK_SECRET` | Yes  | Yes | HMAC secret for role webhooks             |
| `LANCORE_INTERNAL_URL`         | Rec  | Rec | Service-to-service URL (Docker network)   |
| `LANCORE_CALLBACK_URL`         | Rec  | No  | Override if APP_URL differs from callback |
| `LANCORE_TIMEOUT`              | No   | No  | Default: 5s                               |
| `LANCORE_RETRIES`              | No   | No  | Default: 2                                |
| `LANCORE_RETRY_DELAY`          | No   | No  | Default: 100ms                            |
| `APP_NAME`                     | No   | No  | Default: LanEntrance                      |
| `APP_DEBUG`                    | No   | Yes | `true` for local only                     |
| `SESSION_DRIVER`               | No   | No  | Default: database                         |
| `SESSION_LIFETIME`             | No   | No  | Default: 120 min                          |
| `VITE_PORT`                    | No   | Yes | Default: 5177 (dev server only)           |
| `APP_PORT`                     | No   | Yes | Default: 84 (Sail port mapping)           |

**Rec** = Recommended but has a working fallback default.

---

# 6. Deployment

## 6.1 Docker Compose (development)

```yaml
# compose.yaml — existing configuration
services:
  laravel.test:
    container_name: lanentrance.test
    image: sail-8.5/app
    ports:
      - "${APP_PORT:-84}:80"
      - "${VITE_PORT:-5177}:${VITE_PORT:-5177}"
    networks:
      - lanparty
    volumes:
      - ".:/var/www/html"
```

Start command: `./vendor/bin/sail up -d`

## 6.2 Production deployment

Production deployment uses the Docker image published to GHCR:

1. Pull latest image from GHCR
2. Configure environment variables
3. Run database migrations (`php artisan migrate --force`)
4. Start container(s) behind reverse proxy

### Health check

The application provides a health endpoint for monitoring:

```
GET /up → 200 OK (Laravel default health check)
```

## 6.3 Network requirements

| Connection                     | Protocol | Port | Required |
| ------------------------------ | -------- | ---- | -------- |
| Client → LanEntrance           | HTTPS    | 443  | Yes      |
| LanEntrance → LanCore          | HTTPS    | 443  | Yes*     |
| LanEntrance → Database         | TCP      | 3306 | Yes      |

*Via internal Docker network in containerized deployments

---

# 7. Database schema

## 7.1 Managed tables

LanEntrance manages the following database tables via Laravel migrations:

| Table                     | Purpose                              | Owner        |
| ------------------------- | ------------------------------------ | ------------ |
| `users`                   | Operator accounts                    | LanEntrance  |
| `password_reset_tokens`   | Password reset flow                  | Laravel      |
| `sessions`                | Session storage                      | Laravel      |
| `cache`                   | Cache storage                        | Laravel      |
| `cache_locks`             | Cache lock management                | Laravel      |
| `jobs` / `job_batches`    | Queue jobs (if used)                 | Laravel      |
| `failed_jobs`             | Failed job tracking                  | Laravel      |

## 7.2 Users table schema

| Column                        | Type              | Notes                          |
| ----------------------------- | ----------------- | ------------------------------ |
| `id`                          | bigint (PK)       | Auto-increment                 |
| `lancore_user_id`             | bigint (unique)   | Nullable; LanCore user ID      |
| `name`                        | string            | Display name                   |
| `email`                       | string (unique)   | Email address                  |
| `email_verified_at`           | timestamp         | Nullable                       |
| `password`                    | string            | Hashed                         |
| `role`                        | string            | Default: 'user'; UserRole enum |
| `two_factor_secret`           | text              | Nullable; encrypted            |
| `two_factor_recovery_codes`   | text              | Nullable; encrypted            |
| `two_factor_confirmed_at`     | timestamp         | Nullable                       |
| `remember_token`              | string(100)       | Nullable                       |
| `created_at`                  | timestamp         | —                              |
| `updated_at`                  | timestamp         | —                              |

---

# 8. Notes

## 8.1 No authoritative state storage

Per SSS requirement LENT-3.5-001, LanEntrance does not store ticket validity or check-in state. The database contains only operator accounts and framework-managed tables.

## 8.2 Versioning

Software versions follow semantic versioning. Container images are tagged with:
* `latest` — most recent build from main
* `v{major}.{minor}.{patch}` — release tags
* `sha-{commit}` — commit-specific builds
