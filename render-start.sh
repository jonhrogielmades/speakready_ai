#!/bin/sh

set -eu

export LOG_CHANNEL="${LOG_CHANNEL:-stderr}"
export LOG_EMERGENCY_PATH="${LOG_EMERGENCY_PATH:-php://stderr}"

mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

touch storage/logs/laravel.log || true

if command -v chown >/dev/null 2>&1; then
    chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
fi

chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true

# Run skipped composer scripts
php artisan package:discover --ansi

# Clear and cache configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage symlink for public uploads
php artisan storage:link

# Run database migrations (forces it in production)
php artisan migrate --force

# Seed the database automatically (uses firstOrCreate so it's safe to run multiple times)
php artisan db:seed --force

# Start PHP-FPM in the background
php-fpm -D

# Start Nginx in the foreground (this keeps the container running)
nginx -g "daemon off;"
