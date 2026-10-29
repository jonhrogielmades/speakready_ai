#!/bin/sh

set -eu

backup_directory="${1:-}"

if [ -z "$backup_directory" ] || [ ! -d "$backup_directory" ]; then
    echo "Usage: RESTORE_CONFIRM=restore-speakready ./scripts/restore-backup.sh /backups/<timestamp>" >&2
    exit 1
fi

if [ "${RESTORE_CONFIRM:-}" != "restore-speakready" ]; then
    echo "Restore refused. Stop the app and set RESTORE_CONFIRM=restore-speakready after verifying the target." >&2
    exit 1
fi

for required in MANIFEST SHA256SUMS database.sql.gz storage.tar.gz; do
    if [ ! -f "${backup_directory}/${required}" ]; then
        echo "Backup is incomplete: missing ${required}." >&2
        exit 1
    fi
done

(
    cd "$backup_directory"
    sha256sum -c SHA256SUMS
)

if [ ! -d "${RESTORE_STORAGE_PATH:-}" ]; then
    echo "RESTORE_STORAGE_PATH must name an existing, dedicated Laravel storage directory." >&2
    exit 1
fi

restore_storage_path="$(cd "${RESTORE_STORAGE_PATH}" && pwd -P)"
case "$restore_storage_path" in
    /|/var|/var/www|/source|/backups)
        echo "Restore refused for broad or reserved path: ${restore_storage_path}" >&2
        exit 1
        ;;
esac

if find "$restore_storage_path" -mindepth 1 -maxdepth 1 -print -quit | grep -q . \
    && [ "${RESTORE_ALLOW_NONEMPTY_STORAGE:-false}" != "true" ]; then
    echo "Restore storage target must be empty so newer files are not mixed with the backup." >&2
    echo "Use a fresh staging directory or set RESTORE_ALLOW_NONEMPTY_STORAGE=true only after an explicit overlay review." >&2
    exit 1
fi

backup_database="$(sed -n 's/^database=//p' "${backup_directory}/MANIFEST" | head -n 1)"
target_database="${DB_DATABASE:-speakready_ai}"
if [ -n "$backup_database" ] \
    && [ "$backup_database" != "$target_database" ] \
    && [ "${RESTORE_ALLOW_DATABASE_MISMATCH:-false}" != "true" ]; then
    echo "Backup database '${backup_database}' does not match target '${target_database}'." >&2
    echo "Set RESTORE_ALLOW_DATABASE_MISMATCH=true only after verifying the target." >&2
    exit 1
fi

if tar -tzf "${backup_directory}/storage.tar.gz" | grep -Eq '(^/|(^|/)\.\.(/|$))'; then
    echo "Storage archive contains an unsafe path and cannot be restored." >&2
    exit 1
fi

export MYSQL_PWD="${DB_PASSWORD:?DB_PASSWORD is required}"

gzip -dc "${backup_directory}/database.sql.gz" | mariadb \
    --host="${DB_HOST:-db}" \
    --port="${DB_PORT:-3306}" \
    --user="${DB_USERNAME:-speakready}" \
    "$target_database"

tar -C "$restore_storage_path" -xzf "${backup_directory}/storage.tar.gz"

echo "Restore completed. Run migrations, clear Laravel caches, and verify /up before reopening traffic." >&2
