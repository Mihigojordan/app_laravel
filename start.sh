#!/bin/sh
set -e

if [ ! -f .env ] && [ -f .env.example ]; then
    cp .env.example .env
fi

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
chown www-data:www-data .env 2>/dev/null || true
chmod 664 .env 2>/dev/null || true

php artisan key:generate --force || true

php artisan package:discover || true

php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true

php artisan storage:link || true

php artisan migrate --force || true

apache2-foreground