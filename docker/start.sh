#!/bin/sh

set -e

echo "Starting Laravel application..."

php artisan config:clear

php artisan migrate --force

php artisan cache:clear

php artisan config:cache

php artisan route:cache

php artisan view:cache

# Render provides the HTTP port through PORT.
# Use 10000 locally if PORT is not provided.
PORT="${PORT:-10000}"

echo "Starting Nginx on port ${PORT}..."

sed -i "s/listen 10000;/listen ${PORT};/" /etc/nginx/sites-available/default

php-fpm -D

nginx -g "daemon off;"
