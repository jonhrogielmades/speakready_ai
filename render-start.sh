#!/bin/sh

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
