#!/usr/bin/env bash
# Run by Plesk after each Git pull (Hosting & DNS > Git > Additional deployment actions).
# Idempotent: safe to run on every deploy. Requires PHP 8.3+, Composer, Node 22 LTS on the server.
#
# On Plesk the default `php` can be older than 8.3. Point the script at the right binary:
#   PHP_BIN=/opt/plesk/php/8.3/bin/php bash deploy/plesk-deploy.sh
set -euo pipefail

cd "$(dirname "$0")/.."

PHP_BIN="${PHP_BIN:-php}"

if [ ! -f .env ]; then
    echo "Missing .env. Copy deploy/env.production.example to .env and fill it in first." >&2
    exit 1
fi

if ! "$PHP_BIN" -r 'exit(version_compare(PHP_VERSION, "8.3.0", ">=") ? 0 : 1);'; then
    echo "PHP 8.3 or newer is required. '$PHP_BIN' is $("$PHP_BIN" -r 'echo PHP_VERSION;')." >&2
    echo "Run: PHP_BIN=/opt/plesk/php/8.3/bin/php bash deploy/plesk-deploy.sh" >&2
    exit 1
fi

echo "==> PHP dependencies (no dev packages)"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

echo "==> Front-end build"
npm ci --no-audit --no-fund
npm run build

echo "==> Database migrations"
"$PHP_BIN" artisan migrate --force

echo "==> Filament assets and storage link"
"$PHP_BIN" artisan filament:assets
"$PHP_BIN" artisan storage:link 2>/dev/null || true

echo "==> Caches"
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan optimize

echo "==> Done"
