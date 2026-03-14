#!/bin/bash
# shellcheck disable=SC2164
cd /var/www/html/top-worker-backend ;
export COMPOSER_ALLOW_SUPERUSER=1;
rm -rf .env;
cp .env.server .env;
git pull origin main --no-ff;
git reset --hard origin/main;
composer install ;
php artisan migrate --seed;
php artisan config:clear;
php artisan clear;
php artisan route:clear;
sudo chmod -R 777 storage public bootstrap/cache;