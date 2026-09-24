#!/bin/sh

set -eu

cd /var/www

export PORT=80
export LOG_CHANNEL="${LOG_CHANNEL:-stderr}"
export LOG_EMERGENCY_PATH="${LOG_EMERGENCY_PATH:-php://stderr}"

case "$PORT" in
    ''|*[!0-9]*)
        echo "Invalid PORT value: $PORT" >&2
        exit 1
        ;;
esac

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
        echo "APP_KEY is missing. Set a stable APP_KEY using: php artisan key:generate --show" >&2
        exit 1
    fi

    if [ -z "${APP_URL:-}" ]; then
        echo "APP_URL is missing. Set it to your production domain to avoid broken redirects." >&2
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

seed_storage_path() {
    source="/var/www/storage-seed/$1"
    target="storage/$1"

    if [ -e "$source" ] && [ ! -e "$target" ]; then
        echo "Seeding persistent storage path: $1" >&2
        mkdir -p "$(dirname "$target")"
        cp -a "$source" "$target"
    fi
}

seed_persistent_storage() {
    if [ ! -d /var/www/storage-seed ]; then
        return 0
    fi

    seed_storage_path app/private/datasets
    seed_storage_path app/private/models
}

seed_persistent_storage

mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

touch storage/logs/laravel.log || true
touch storage/framework/sr-maintenance.flag || true

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

env_bool() {
    name="$1"
    default="$2"
    value="$(eval "printf '%s' \"\${$name:-$default}\"" | tr '[:upper:]' '[:lower:]')"

    case "$value" in
        1|true|yes|on)
            return 0
            ;;
        *)
            return 1
            ;;
    esac
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

run_startup_maintenance() {
    echo "Running container startup maintenance." >&2

    # Remove stale cache files before Laravel reads production environment values.
    # packages.php/services.php can contain dev-only providers from a local build;
    # production installs use --no-dev, so those stale manifests can crash boot.
    rm -f \
        bootstrap/cache/config.php \
        bootstrap/cache/events.php \
        bootstrap/cache/packages.php \
        bootstrap/cache/routes-*.php \
        bootstrap/cache/services.php \
        bootstrap/cache/views.php

    # Run skipped composer scripts and clear stale framework state before schema work.
    run_required php artisan package:discover --ansi
    run_required php artisan config:clear
    run_optional php artisan cache:clear
    run_required php artisan view:clear
    run_required php artisan route:clear

    if env_bool RUN_MIGRATIONS_ON_START true; then
        if ! run_migrations; then
            if env_bool RUN_SCHEMA_REPAIRS_ON_START true; then
                echo "Initial migration failed; running schema repair fallback before retrying migrations." >&2
                run_schema_repairs

                if ! run_migrations; then
                    echo "Migration retry failed after schema repair. Continuing only after required runtime schemas are repaired." >&2
                fi
            else
                echo "Migration failed and RUN_SCHEMA_REPAIRS_ON_START is disabled." >&2
                exit 1
            fi
        fi
    else
        echo "Skipping migrations because RUN_MIGRATIONS_ON_START is disabled." >&2
    fi

    if env_bool RUN_SCHEMA_REPAIRS_ON_START true; then
        run_schema_repairs
    else
        echo "Skipping schema repair commands because RUN_SCHEMA_REPAIRS_ON_START is disabled." >&2
    fi

    # Create storage symlink for public uploads.
    run_optional php artisan storage:link --force

    if [ "${REPAIR_FEEDBACK_ON_START:-false}" = "true" ]; then
        run_optional php artisan app:repair-feedback-coaching --limit="${REPAIR_FEEDBACK_LIMIT:-250}"
    else
        echo "Skipping optional feedback coaching repair on startup." >&2
    fi

    if env_bool RUN_DATABASE_SEEDERS false; then
        if [ "${APP_ENV:-}" = "production" ] && [ -z "${ADMIN_PASSWORD:-}" ]; then
            echo "Skipping database seeders: ADMIN_PASSWORD must be set before seeding in production." >&2
        else
            run_optional php artisan db:seed --force
        fi
    else
        echo "Skipping database seeders. Set RUN_DATABASE_SEEDERS=true to run them." >&2
    fi

    # Rebuild optimized caches after schema and environment repairs complete.
    run_required php artisan config:cache
    run_optional php artisan route:cache
    run_optional php artisan view:cache

    rm -f storage/framework/sr-maintenance.flag
    echo "Container startup maintenance complete." >&2
}

# Start PHP-FPM and Nginx quickly while Laravel startup maintenance runs behind
# a static maintenance gate.
php-fpm -D
(
    trap 'status=$?; if [ "$status" -ne 0 ]; then echo "Container startup maintenance failed with status ${status}; keeping maintenance gate enabled." >&2; touch storage/framework/sr-maintenance.flag || true; fi' EXIT
    run_startup_maintenance
) &

exec nginx -g "daemon off;"
