#!/bin/sh

# Clear and cache configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations (forces it in production)
php artisan migrate --force

# Start PHP-FPM in the background
php-fpm -D

# Start Nginx in the foreground (this keeps the container running)
nginx -g "daemon off;"
