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
    storage/framework/operations \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

touch storage/logs/laravel.log || true

startup_state_directory="/tmp/speakready"
startup_maintenance_flag="${startup_state_directory}/sr-maintenance.flag"
startup_maintenance_failure="${startup_state_directory}/sr-maintenance.failed"

# Startup readiness is deliberately container-local. The storage directory is
# a shared Dokploy volume; placing this gate there lets a new replica put the
# still-healthy old replica into maintenance during a rolling deployment.
mkdir -p "$startup_state_directory"
chmod 0755 "$startup_state_directory"

touch "$startup_maintenance_flag"
rm -f "$startup_maintenance_failure"

if command -v chown >/dev/null 2>&1; then
    chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
fi

chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true

clear_laravel_cache_files() {
    echo "Removing stale Laravel cache files before serving traffic." >&2

    # These paths can live on persistent volumes in Dokploy. Clear them before
    # PHP-FPM starts so old compiled Blade/config files cannot be served.
    rm -f \
        bootstrap/cache/config.php \
        bootstrap/cache/events.php \
        bootstrap/cache/packages.php \
        bootstrap/cache/routes-*.php \
        bootstrap/cache/services.php \
        bootstrap/cache/views.php \
        storage/framework/views/*.php

    if command -v find >/dev/null 2>&1; then
        find storage/framework/views -type f -name '*.php' -delete 2>/dev/null || true
    fi
}

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

clear_startup_gate() {
    rm -f "$startup_maintenance_flag" || true
}

record_startup_failure() {
    status="$1"

    {
        echo "Container startup maintenance failed with status ${status}."
        echo "Time: $(date -u +"%Y-%m-%dT%H:%M:%SZ" 2>/dev/null || date)"
        echo "Set STARTUP_MAINTENANCE_FAIL_OPEN=true only for an emergency fail-open deployment."
    } > "$startup_maintenance_failure" 2>/dev/null || true
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

write_process_heartbeat() {
    heartbeat_file="$1"
    heartbeat_tmp="${heartbeat_file}.tmp.$$"

    mkdir -p "$(dirname "$heartbeat_file")"
    date -u +%s > "$heartbeat_tmp"
    mv -f "$heartbeat_tmp" "$heartbeat_file"
}

start_supervised_queue_worker() {
    connection="${QUEUE_CONNECTION:-database}"
    heartbeat_file="storage/${QUEUE_HEARTBEAT_FILE:-framework/operations/queue-worker.heartbeat}"
    max_time="${QUEUE_WORKER_MAX_TIME:-240}"
    timeout="${QUEUE_WORKER_TIMEOUT:-180}"
    restart_delay="${PROCESS_RESTART_DELAY_SECONDS:-5}"

    echo "Starting supervised queue worker loop for ${connection}." >&2
    (
        while true; do
            write_process_heartbeat "$heartbeat_file"

            if php artisan queue:work "$connection" \
                --sleep=3 \
                --tries=3 \
                --timeout="$timeout" \
                --backoff=5 \
                --max-time="$max_time" >> /proc/1/fd/2 2>&1; then
                status=0
            else
                status=$?
            fi

            write_process_heartbeat "$heartbeat_file"
            echo "Queue worker exited with status ${status}; restarting in ${restart_delay}s." >&2
            sleep "$restart_delay"
        done
    ) &
}

start_supervised_scheduler() {
    heartbeat_file="storage/${SCHEDULER_HEARTBEAT_FILE:-framework/operations/scheduler.heartbeat}"
    interval="${SCHEDULER_INTERVAL_SECONDS:-60}"

    echo "Starting supervised Laravel scheduler loop." >&2
    (
        while true; do
            write_process_heartbeat "$heartbeat_file"

            if ! php artisan schedule:run --no-interaction >> /proc/1/fd/2 2>&1; then
                echo "Laravel scheduler invocation failed; retrying after ${interval}s." >&2
            fi

            write_process_heartbeat "$heartbeat_file"
            sleep "$interval"
        done
    ) &
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

    clear_laravel_cache_files

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

                run_required php artisan migrate --force
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

    if env_bool RUN_DATABASE_SEEDERS true; then
        # DatabaseSeeder is intentionally limited to the idempotent admin seeder.
        # On a fresh production database it rejects a missing ADMIN_PASSWORD.
        run_required php artisan db:seed --force
    else
        echo "Skipping database seeders. Set RUN_DATABASE_SEEDERS=true to run them." >&2
    fi

    # Rebuild optimized caches after schema and environment repairs complete.
    run_required php artisan config:cache
    run_optional php artisan route:cache
    run_optional php artisan view:cache

    if [ "${QUEUE_CONNECTION:-database}" != "sync" ] && [ "${QUEUE_CONNECTION:-database}" != "null" ]; then
        start_supervised_queue_worker
    fi

    if env_bool RUN_SCHEDULER_ON_START true; then
        start_supervised_scheduler
    else
        echo "Skipping Laravel scheduler worker because RUN_SCHEDULER_ON_START is disabled." >&2
    fi

    clear_startup_gate
    echo "Container startup maintenance complete." >&2
}

handle_startup_maintenance_exit() {
    status="$1"

    if [ "$status" -eq 0 ]; then
        return 0
    fi

    record_startup_failure "$status"

    if env_bool STARTUP_MAINTENANCE_FAIL_OPEN false; then
        echo "Container startup maintenance failed with status ${status}; clearing startup gate so Laravel can serve the best available response." >&2
        clear_startup_gate
    else
        echo "Container startup maintenance failed with status ${status}; keeping maintenance gate enabled." >&2
        touch "$startup_maintenance_flag" || true
    fi
}

# Start PHP-FPM and Nginx quickly while Laravel startup maintenance runs behind
# a static maintenance gate.
clear_laravel_cache_files
php-fpm -D
(
    trap 'status=$?; handle_startup_maintenance_exit "$status"' EXIT
    run_startup_maintenance
) &

exec nginx -g "daemon off;"
