#!/usr/bin/env bash
# Maintainer checks that match the GitHub Actions release job (without
# overwriting a local .env). Isolated migrate/seed/super-admin runs in CI.
set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"
cd "$root"

echo "==> composer validate"
composer validate --no-check-publish

if [[ ! -d vendor ]]; then
    echo "==> composer install"
    composer install --no-interaction --prefer-dist --no-progress
fi

if [[ "${SKIP_FRONTEND:-0}" != "1" ]]; then
    echo "==> npm ci"
    npm ci
    echo "==> npm run build"
    npm run build
fi

echo "==> php artisan test"
php artisan config:clear --ansi
php artisan test
