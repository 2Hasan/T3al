#!/usr/bin/env bash
# Run by Plesk after each Git pull (Hosting & DNS > Git > Additional deployment actions).
# Idempotent: safe to run on every deploy. Requires PHP 8.3+, Composer, Node 20+ on the server.
set -euo pipefail

cd "$(dirname "$0")/.."

if [ ! -f .env ]; then
    echo "Missing .env. Copy deploy/env.production.example to .env and fill it in first." >&2
    exit 1
fi

echo "==> PHP dependencies (no dev packages)"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

echo "==> Front-end build"
npm ci --no-audit --no-fund
npm run build

echo "==> Database migrations"
php artisan migrate --force

echo "==> Filament assets and storage link"
php artisan filament:assets
php artisan storage:link 2>/dev/null || true

echo "==> Caches"
php artisan optimize:clear
php artisan optimize

echo "==> Done"
