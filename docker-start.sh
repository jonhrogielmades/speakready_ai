#!/bin/sh

set -eu

cd /var/www

export PORT="${PORT:-10000}"
export LOG_CHANNEL="${LOG_CHANNEL:-stderr}"
export LOG_EMERGENCY_PATH="${LOG_EMERGENCY_PATH:-php://stderr}"

case "$PORT" in
    ''|*[!0-9]*)
        echo "Invalid PORT value: $PORT" >&2
        exit 1
        ;;
esac

if [ -z "${APP_URL:-}" ] && [ -n "${RENDER_EXTERNAL_URL:-}" ]; then
    export APP_URL="$RENDER_EXTERNAL_URL"
    echo "APP_URL was not set; using RENDER_EXTERNAL_URL." >&2
fi

if [ -z "${DB_CONNECTION:-}" ] && [ -n "${DATABASE_URL:-}" ]; then
    case "$DATABASE_URL" in
        postgres://*|postgresql://*|pgsql://*)
            export DB_CONNECTION=pgsql
            ;;
        mysql://*|mysql2://*|mariadb://*)
            export DB_CONNECTION=mysql
            ;;
        sqlite://*|sqlite3://*)
            export DB_CONNECTION=sqlite
            ;;
    esac

    if [ -n "${DB_CONNECTION:-}" ]; then
        echo "DB_CONNECTION was not set; inferred ${DB_CONNECTION} from DATABASE_URL." >&2
    fi
fi

if [ "${APP_ENV:-}" = "production" ]; then
    if [ -z "${APP_KEY:-}" ]; then
        echo "APP_KEY is missing. Set a stable APP_KEY in Render using: php artisan key:generate --show" >&2
        exit 1
    fi

    if [ -z "${APP_URL:-}" ]; then
        echo "APP_URL is missing. Set it to your Render URL or custom domain to avoid broken redirects." >&2
    fi

    if [ "${DB_CONNECTION:-}" != "sqlite" ] && [ -z "${DATABASE_URL:-}" ] && [ -z "${DB_HOST:-}" ]; then
        echo "Database configuration is missing. Set DATABASE_URL or DB_HOST/DB_DATABASE/DB_USERNAME/DB_PASSWORD." >&2
        exit 1
    fi
fi

if [ -z "${OPENAI_API_KEY:-}" ] && [ -z "${GEMINI_API_KEY:-}" ] && [ -z "${GROQ_API_KEY:-}" ] && [ -z "${COHERE_API_KEY:-}" ]; then
    echo "No hosted AI provider key is configured; interview feedback will use the local evidence fallback." >&2
fi

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

run_required() {
    echo "Running: $*" >&2
    "$@" || {
        status=$?
        echo "Required startup command failed with status ${status}: $*" >&2
        exit "$status"
    }
}

run_optional() {
    echo "Running optional: $*" >&2
    if ! "$@"; then
        echo "Optional startup command failed: $*" >&2
    fi
}

run_schema_repairs() {
    run_required php artisan app:ensure-ai-provider-schema --force --create-missing
    run_required php artisan app:ensure-voice-schema --force --create-missing
    run_required php artisan app:ensure-question-schema --force --create-missing
    run_required php artisan app:ensure-interview-answer-schema --force --create-missing
    run_required php artisan app:ensure-score-schema --force --create-missing
    run_required php artisan app:ensure-feedback-schema --force --create-missing
    run_required php artisan app:ensure-game-schema --force
}

run_migrations() {
    echo "Running: php artisan migrate --force" >&2
    php artisan migrate --force
}

echo "Running container startup maintenance." >&2

# Remove stale cache files before Laravel reads production environment values.
rm -f bootstrap/cache/config.php bootstrap/cache/routes-*.php bootstrap/cache/views.php

# Run skipped composer scripts and clear stale framework state before schema work.
run_required php artisan package:discover --ansi
run_required php artisan config:clear
run_optional php artisan cache:clear
run_required php artisan view:clear
run_required php artisan route:clear

if ! run_migrations; then
    echo "Initial migration failed; running schema repair fallback before retrying migrations." >&2
    run_schema_repairs

    if ! run_migrations; then
        echo "Migration retry failed after schema repair. Continuing only after required runtime schemas are repaired." >&2
    fi
fi

run_schema_repairs

# Create storage symlink for public uploads.
run_optional php artisan storage:link --force

if [ "${REPAIR_FEEDBACK_ON_START:-false}" = "true" ]; then
    run_optional php artisan app:repair-feedback-coaching --limit="${REPAIR_FEEDBACK_LIMIT:-250}"
else
    echo "Skipping optional feedback coaching repair on startup." >&2
fi

# Seed the database automatically. Seeders use idempotent writes where needed.
run_optional php artisan db:seed --force

# Rebuild optimized caches after schema and environment repairs complete.
run_required php artisan config:cache
run_optional php artisan route:cache
run_optional php artisan view:cache

echo "Container startup maintenance complete." >&2

# Start PHP-FPM in the background, then Nginx in the foreground to keep the container running.
php-fpm -D
nginx -g "daemon off;"
