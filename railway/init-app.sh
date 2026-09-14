#!/usr/bin/env bash
# Optional pre-deploy / release hook for container or PaaS app services.
# Make executable: chmod +x railway/init-app.sh
set -euo pipefail

mkdir -p \
  storage/framework/{cache,sessions,views} \
  storage/framework/cache/data \
  storage/logs \
  storage/app/public \
  bootstrap/cache

# Public disk symlink (avatars, company logos, platform branding).
# Persist storage/app/public, or set FILESYSTEM_DISK=s3.
php artisan storage:link --force

php artisan migrate --force

php artisan optimize:clear

php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache
