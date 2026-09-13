#!/usr/bin/env bash
# Pre-deploy / release hook for the Railway App service.
# Make executable: chmod +x railway/init-app.sh
set -euo pipefail

mkdir -p \
  storage/framework/{cache,sessions,views} \
  storage/framework/cache/data \
  storage/logs \
  storage/app/public \
  bootstrap/cache

# Public disk symlink (avatars, company logos, platform branding).
# Prefer a Railway Volume mounted at storage/app/public, or FILESYSTEM_DISK=s3.
php artisan storage:link --force

php artisan migrate --force

# Fix accidental branding from an earlier Railway APP_NAME mistake.
# Platform name is stored in DB and overrides config('app.name') at runtime.
php artisan tinker --execute="\\Illuminate\\Support\\Facades\\DB::table('platform_settings')->where('key', 'platform_name')->where('value', 'Hotel Compete CRM')->update(['value' => 'Algos CRM', 'updated_at' => now()]); \\Illuminate\\Support\\Facades\\Cache::forget(\\App\\Services\\SuperAdmin\\PlatformSettingsService::CACHE_KEY);"

php artisan optimize:clear

php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache
