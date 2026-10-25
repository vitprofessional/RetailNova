#!/usr/bin/env bash
set -Eeuo pipefail
umask 077

DRY_RUN=0
if [[ "${1:-}" == "--dry-run" && $# -eq 1 ]]; then
    DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: $0 [--dry-run]" >&2
    exit 2
fi

PROJECT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
BACKUP_DIR="$(dirname -- "$PROJECT_DIR")/backups"
TIMESTAMP="$(date -u +%Y%m%dT%H%M%SZ)"
BACKUP_FILE="$BACKUP_DIR/retailnova-${TIMESTAMP}.sql"
MAINTENANCE=0

if (( DRY_RUN )); then
    cat <<EOF
DRY RUN only; no server state will be changed.
Project: $PROJECT_DIR
Database backup: $BACKUP_FILE
Planned order:
  1. mysqldump --defaults-extra-file=<protected temporary credentials> --single-transaction <configured database> > <backup>
  2. php artisan down
  3. git pull --ff-only origin main
  4. composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
  5. php artisan optimize:clear
  6. php artisan migrate --force
  7. php artisan optimize
  8. php artisan up
On a post-maintenance failure, the app remains in maintenance mode; restore/release rollback is an operator decision.
EOF
    exit 0
fi

cd "$PROJECT_DIR"
for command in php composer git mysqldump; do
    if ! command -v "$command" >/dev/null 2>&1; then
        echo "Missing required command: $command" >&2
        exit 1
    fi
done
if [[ ! -f .env ]]; then
    echo "Missing .env; deployment stopped before backup." >&2
    exit 1
fi
for variable in DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD; do
    if [[ -z "${!variable:-}" ]]; then
        echo "Required exported variable $variable is missing. Export DB settings securely; deploy.sh never sources .env." >&2
        exit 1
    fi
done

mkdir -p "$BACKUP_DIR"
chmod 700 "$BACKUP_DIR"
defaults_file="$(mktemp "${TMPDIR:-/tmp}/retailnova-mysqldump.XXXXXX")"
cleanup() { rm -f -- "$defaults_file"; }
trap cleanup EXIT
escape_option_value() {
    local value="$1"
    value="${value//\\/\\\\}"
    value="${value//\"/\\\"}"
    printf '%s' "$value"
}
printf '[client]\nhost="%s"\nport="%s"\nuser="%s"\npassword="%s"\n' \
    "$(escape_option_value "$DB_HOST")" "$(escape_option_value "$DB_PORT")" \
    "$(escape_option_value "$DB_USERNAME")" "$(escape_option_value "$DB_PASSWORD")" > "$defaults_file"
chmod 600 "$defaults_file"

echo "Creating database backup: $BACKUP_FILE"
if ! mysqldump --defaults-extra-file="$defaults_file" --single-transaction --quick --routines --triggers "$DB_DATABASE" > "$BACKUP_FILE"; then
    rm -f -- "$BACKUP_FILE"
    echo "Database backup failed; deployment stopped before maintenance mode. Check DB access and retry." >&2
    exit 1
fi
if [[ ! -s "$BACKUP_FILE" ]]; then
    rm -f -- "$BACKUP_FILE"
    echo "Database backup is empty; deployment stopped before maintenance mode." >&2
    exit 1
fi
chmod 600 "$BACKUP_FILE"

on_failure() {
    status=$?
    if (( status != 0 )); then
        if (( MAINTENANCE )); then
            echo "Deployment failed (exit $status). Application remains in maintenance mode." >&2
        fi
        echo "DB backup retained at: $BACKUP_FILE" >&2
        echo "Review the failed step and logs. Restore the matched pre-deploy DB/files backup or roll back code only after compatibility review; then run php artisan up when safe." >&2
    fi
    exit "$status"
}
trap on_failure ERR

php artisan down
MAINTENANCE=1
git pull --ff-only origin main
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
php artisan optimize:clear
php artisan migrate --force
php artisan optimize
php artisan up
MAINTENANCE=0
trap - ERR
echo "Deployment steps completed. Database backup retained at: $BACKUP_FILE"
