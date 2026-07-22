#!/bin/sh

php artisan config:clear
php artisan cache:clear

php artisan migrate --force

php artisan storage:link || true

apache2-foreground