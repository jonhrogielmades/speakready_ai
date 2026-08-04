#!/bin/sh

set -u

cd /var/www

export PORT="${PORT:-10000}"
export LOG_CHANNEL=stderr
export LOG_EMERGENCY_PATH=php://stderr

if [ -n "${RENDER_EXTERNAL_URL:-}" ]; then
    case "${APP_URL:-}" in
        ''|http://localhost|http://localhost:*|https://localhost|https://localhost:*)
            export APP_URL="$RENDER_EXTERNAL_URL"
            echo "Using Render external URL for APP_URL: $APP_URL" >&2
            ;;
    esac
fi

if [ "${MAIL_MAILER:-}" = "smtp" ] && [ "${MAIL_HOST:-}" = "smtp.gmail.com" ]; then
    echo "Gmail SMTP is configured. Render Free web services block outbound SMTP ports 25, 465, and 587; use a paid Render instance or an HTTPS/API mail provider if reset emails do not send." >&2
fi

# Render's dashboard sometimes shows a short internal Postgres host such as
# dpg-...-a. That only resolves on Render's private network. If the service is
# not on that network, expand it to the public hostname before Laravel caches
# configuration.
if [ -n "${DATABASE_URL:-}" ]; then
    database_url_host="$(printf '%s' "$DATABASE_URL" | sed -n 's#^[a-zA-Z][a-zA-Z0-9+.-]*://[^@]*@\([^:/?]*\).*#\1#p')"

    case "$database_url_host" in
        dpg-*.*|'')
            ;;
        dpg-*)
            export RENDER_POSTGRES_REGION="${RENDER_POSTGRES_REGION:-singapore}"
            database_url_expanded_host="${database_url_host}.${RENDER_POSTGRES_REGION}-postgres.render.com"
            export DATABASE_URL="$(printf '%s' "$DATABASE_URL" | sed "s#@${database_url_host}#@${database_url_expanded_host}#")"
            export DB_SSLMODE="${DB_SSLMODE:-require}"
            echo "Expanded Render Postgres DATABASE_URL host for ${RENDER_POSTGRES_REGION} region." >&2
            ;;
    esac
else
    case "${DB_HOST:-}" in
        dpg-*.*)
            ;;
        dpg-*)
            export RENDER_POSTGRES_REGION="${RENDER_POSTGRES_REGION:-singapore}"
            export DB_HOST="${DB_HOST}.${RENDER_POSTGRES_REGION}-postgres.render.com"
            export DB_SSLMODE="${DB_SSLMODE:-require}"
            echo "Expanded Render Postgres DB_HOST for ${RENDER_POSTGRES_REGION} region." >&2
            ;;
    esac
fi

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

# Do not block Render's port scanner on slow database/cache maintenance.
# Remove stale cache files directly, then bind the web port as early as possible.
rm -f bootstrap/cache/config.php bootstrap/cache/routes-*.php bootstrap/cache/views.php

# Start PHP-FPM in the background
php-fpm -D

# Run Laravel maintenance after PHP-FPM starts. Nginx binds to Render's required
# port immediately below while these slower tasks continue in the background.
(
    echo "Running Render startup maintenance." >&2

    # Run skipped composer scripts
    php artisan package:discover --ansi || true

    # Clear stale framework state before schema work.
    php artisan config:clear || true
    php artisan cache:clear || true
    php artisan view:clear || true
    php artisan route:clear || true

    # Repair schemas that can be missing in partially migrated Render databases.
    php artisan app:ensure-ai-provider-schema --force --create-missing || true
    php artisan app:ensure-voice-schema --force --create-missing || true
    php artisan app:ensure-question-schema --force --create-missing || true

    # Create storage symlink for public uploads.
    php artisan storage:link --force || true

    php artisan migrate --force || true
    php artisan app:ensure-ai-provider-schema --force --create-missing || true
    php artisan app:ensure-voice-schema --force --create-missing || true
    php artisan app:ensure-question-schema --force --create-missing || true

    # Repair Learning Game session tables if a previous migration was marked as run
    # while production schema drifted or missed the table creation.
    php artisan app:ensure-game-schema --force || true

    # Optional backfill for older completed interviews. Keep it off during normal
    # Render starts so first user requests are not competing with maintenance work.
    if [ "${RENDER_REPAIR_FEEDBACK_ON_START:-false}" = "true" ]; then
        php artisan app:repair-feedback-coaching --limit="${RENDER_REPAIR_FEEDBACK_LIMIT:-250}" || true
    else
        echo "Skipping optional feedback coaching repair on startup." >&2
    fi

    # Seed the database automatically (uses firstOrCreate so it's safe to run multiple times)
    php artisan db:seed --force || true

    # Rebuild optimized caches after schema and environment repairs complete.
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true

    echo "Render startup maintenance complete." >&2
) &

# Start Nginx in the foreground (this keeps the container running)
nginx -g "daemon off;"
