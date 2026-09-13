#!/bin/sh

set -u

cd /var/www

export PORT="${PORT:-10000}"
export LOG_CHANNEL=stderr
export LOG_EMERGENCY_PATH=php://stderr

case "$PORT" in
    ''|*[!0-9]*)
        echo "Invalid PORT value: $PORT" >&2
        exit 1
        ;;
esac

if [ -f /etc/nginx/sites-enabled/default ]; then
    sed -i \
        -e "s/listen 80;/listen ${PORT};/g" \
        -e "s/listen \[::\]:80;/listen [::]:${PORT};/g" \
        /etc/nginx/sites-enabled/default
fi

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

# Remove stale cache files directly, then bind the web port as early as possible.
rm -f bootstrap/cache/config.php bootstrap/cache/routes-*.php bootstrap/cache/views.php

# Start PHP-FPM in the background.
php-fpm -D

# Run Laravel maintenance after PHP-FPM starts. Nginx binds to the configured
# port immediately below while these slower tasks continue in the background.
(
    echo "Running container startup maintenance." >&2

    # Run skipped composer scripts.
    php artisan package:discover --ansi || true

    # Clear stale framework state before schema work.
    php artisan config:clear || true
    php artisan cache:clear || true
    php artisan view:clear || true
    php artisan route:clear || true

    php artisan app:ensure-ai-provider-schema --force --create-missing || true
    php artisan app:ensure-voice-schema --force --create-missing || true
    php artisan app:ensure-question-schema --force --create-missing || true
    php artisan app:ensure-interview-answer-schema --force --create-missing || true
    php artisan app:ensure-score-schema --force --create-missing || true
    php artisan app:ensure-feedback-schema --force --create-missing || true

    # Create storage symlink for public uploads.
    php artisan storage:link --force || true

    php artisan migrate --force || true
    php artisan app:ensure-ai-provider-schema --force --create-missing || true
    php artisan app:ensure-voice-schema --force --create-missing || true
    php artisan app:ensure-question-schema --force --create-missing || true
    php artisan app:ensure-interview-answer-schema --force --create-missing || true
    php artisan app:ensure-score-schema --force --create-missing || true
    php artisan app:ensure-feedback-schema --force --create-missing || true
    php artisan app:ensure-game-schema --force || true

    if [ "${REPAIR_FEEDBACK_ON_START:-false}" = "true" ]; then
        php artisan app:repair-feedback-coaching --limit="${REPAIR_FEEDBACK_LIMIT:-250}" || true
    else
        echo "Skipping optional feedback coaching repair on startup." >&2
    fi

    # Seed the database automatically. Seeders use idempotent writes where needed.
    php artisan db:seed --force || true

    # Rebuild optimized caches after schema and environment repairs complete.
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true

    echo "Container startup maintenance complete." >&2
) &

# Start Nginx in the foreground. This keeps the container running.
nginx -g "daemon off;"
