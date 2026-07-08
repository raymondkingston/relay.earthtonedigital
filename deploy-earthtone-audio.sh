cd /home/ploi/relay.earthtonedigital.com

# update code
git pull origin main

# remove the src directory if it exists
rm -rf src

# go into the Laravel subdir
cd audio-app

# install PHP deps
if [ -f composer.json ]; then
    composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
fi

# install/build frontend (if you want Vite builds on prod)
if [ -f package.json ]; then
    npm install
    npm run build
fi

# Laravel housekeeping
php artisan key:generate --force
php artisan migrate --force
php artisan optimize
