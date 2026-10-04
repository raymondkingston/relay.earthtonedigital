#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/home/ploi/relay.earthtonedigital.com"
cd "$APP_DIR"

trap 'echo "Deployment failed near line $LINENO" >&2' ERR

# Prevent overlapping deployments using this script.
exec 9>"$APP_DIR/storage/deploy.lock"
flock -n 9 || {
    echo "Another deployment is running." >&2
    exit 1
}

# Production intentionally follows GitHub main.
git fetch origin main
git reset --hard origin/main

# Remove stale bootstrap caches before Composer boots Laravel.
rm -f bootstrap/cache/{config,events,routes-v7,services,packages,blade-icons}.php
rm -rf bootstrap/cache/filament

composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

npm ci
npm run build

php artisan migrate --force

# Create the storage link only when absent; reject unexpected paths.
if [ ! -e public/storage ] && [ ! -L public/storage ]; then
    php artisan storage:link
fi

if [ ! -L public/storage ] ||
   [ "$(readlink -f public/storage)" != "$(readlink -f storage/app/public)" ]; then
    echo "public/storage is not the expected storage symlink." >&2
    exit 1
fi

# Rebuild deployment caches once.
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache
php artisan filament:cache-components

# Refresh web workers and their OPcache.
sudo -n service php8.4-fpm reload

# Verify Laravel responds over HTTP.
curl --fail --silent --show-error \
    --retry 3 --retry-delay 2 --max-time 20 \
    https://relay.earthtonedigital.com/up >/dev/null

echo "Deployment completed: $(git rev-parse --short HEAD)"
