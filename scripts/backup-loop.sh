#!/bin/sh

set -eu

backup_root=/backups
source_storage=/source/storage
interval="${BACKUP_INTERVAL_SECONDS:-86400}"
retry_interval="${BACKUP_RETRY_SECONDS:-300}"
retention_days="${BACKUP_RETENTION_DAYS:-14}"

case "$interval" in
    ''|*[!0-9]*)
        echo "BACKUP_INTERVAL_SECONDS must be a positive integer." >&2
        exit 1
        ;;
esac

case "$retention_days" in
    ''|*[!0-9]*)
        echo "BACKUP_RETENTION_DAYS must be a positive integer." >&2
        exit 1
        ;;
esac

case "$retry_interval" in
    ''|*[!0-9]*)
        echo "BACKUP_RETRY_SECONDS must be a positive integer." >&2
        exit 1
        ;;
esac

if [ "$interval" -lt 300 ] || [ "$retry_interval" -lt 60 ] || [ "$retention_days" -lt 1 ]; then
    echo "Backup interval must be at least 300 seconds, retry interval at least 60 seconds, and retention at least one day." >&2
    exit 1
fi

mkdir -p "$backup_root"

temporary=''

cleanup_temporary_backup() {
    candidate="${1:-}"

    if [ -z "$candidate" ]; then
        return 0
    fi

    case "$candidate" in
        /backups/.20??????T??????Z.tmp)
            rm -rf -- "$candidate"
            ;;
        *)
            echo "Refusing to clean unexpected temporary backup path: ${candidate}" >&2
            return 1
            ;;
    esac
}

trap 'cleanup_temporary_backup "${temporary:-}"' EXIT

# A prior container crash can leave a hidden in-progress directory behind.
# Completed backups never use this name pattern, so pruning these after one
# day cannot remove a valid backup set.
find "$backup_root" \
    -mindepth 1 \
    -maxdepth 1 \
    -type d \
    -name '.20??????T??????Z.tmp' \
    -mtime +1 \
    -exec rm -rf {} \;

run_backup() {
    timestamp="$(date -u +%Y%m%dT%H%M%SZ)"
    temporary="${backup_root}/.${timestamp}.tmp"
    destination="${backup_root}/${timestamp}"

    cleanup_temporary_backup "$temporary"
    mkdir -p "$temporary"

    export MYSQL_PWD="${DB_PASSWORD:?DB_PASSWORD is required}"

    if ! mariadb-dump \
        --host="${DB_HOST:-db}" \
        --port="${DB_PORT:-3306}" \
        --user="${DB_USERNAME:-speakready}" \
        --single-transaction \
        --quick \
        --skip-lock-tables \
        --routines \
        --events \
        --triggers \
        "${DB_DATABASE:-speakready_ai}" > "${temporary}/database.sql"; then
        echo "Database dump failed." >&2
        cleanup_temporary_backup "$temporary"
        temporary=''
        return 1
    fi

    if ! gzip -9 "${temporary}/database.sql"; then
        echo "Database compression failed." >&2
        cleanup_temporary_backup "$temporary"
        temporary=''
        return 1
    fi

    if ! tar -C "$source_storage" -czf "${temporary}/storage.tar.gz" .; then
        echo "Storage archive failed." >&2
        cleanup_temporary_backup "$temporary"
        temporary=''
        return 1
    fi

    if ! (
        cd "$temporary"
        sha256sum database.sql.gz storage.tar.gz > SHA256SUMS
    ); then
        echo "Backup checksum generation failed." >&2
        cleanup_temporary_backup "$temporary"
        temporary=''
        return 1
    fi

    {
        echo "created_at=${timestamp}"
        echo "database=${DB_DATABASE:-speakready_ai}"
        echo "format=speakready-backup-v1"
    } > "${temporary}/MANIFEST"

    if ! mv "$temporary" "$destination"; then
        echo "Backup could not be finalized." >&2
        cleanup_temporary_backup "$temporary"
        temporary=''
        return 1
    fi
    temporary=''
    date -u +%s > "${backup_root}/.last-success"
    echo "Created application backup ${destination}." >&2

    find "$backup_root" \
        -mindepth 1 \
        -maxdepth 1 \
        -type d \
        -name '20??????T??????Z' \
        -mtime "+${retention_days}" \
        -exec rm -rf {} \;
}

while true; do
    if run_backup; then
        sleep "$interval"
    else
        echo "Backup attempt failed; another attempt will run after ${retry_interval}s." >&2
        sleep "$retry_interval"
    fi
done
