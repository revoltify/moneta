#!/bin/sh
set -e

cd /app

export APP_ENV="${APP_ENV:-production}"
export APP_DEBUG="${APP_DEBUG:-false}"
export APP_URL="${APP_URL:-http://localhost:8080}"
export LOG_CHANNEL="${LOG_CHANNEL:-stderr}"
export DB_CONNECTION="${DB_CONNECTION:-sqlite}"
export QUEUE_CONNECTION="${QUEUE_CONNECTION:-sync}"

INIT_FLAG="/app/storage/.moneta-init"

mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

# first run, indicated by the missing flag file
if [ ! -f "$INIT_FLAG" ]; then
    echo "Initializing Moneta..."

    if [ -z "$APP_KEY" ] && [ ! -f storage/app/.app_key ]; then
        php artisan key:generate --show > storage/app/.app_key
        echo "Generated APP_KEY (persisted in the storage volume). Set APP_KEY explicitly to override."
    fi

    if [ "$DB_CONNECTION" = "sqlite" ]; then
        SQLITE_PATH="${DB_DATABASE:-/app/storage/database.sqlite}"
        [ -f "$SQLITE_PATH" ] || touch "$SQLITE_PATH"
    fi

    if [ ! -f storage/oauth-private.key ]; then
        php artisan passport:keys
    fi

    touch "$INIT_FLAG"
fi

if [ -z "$APP_KEY" ]; then
    export APP_KEY="$(cat storage/app/.app_key)"
fi

php artisan migrate --force

php artisan moneta:install \
    ${MONETA_ADMIN_NAME:+--name="$MONETA_ADMIN_NAME"} \
    ${MONETA_ADMIN_EMAIL:+--email="$MONETA_ADMIN_EMAIL"} \
    ${MONETA_ADMIN_PASSWORD:+--password="$MONETA_ADMIN_PASSWORD"} \
    ${MONETA_COMPANY:+--company="$MONETA_COMPANY"} \
    || true

[ -f storage/installed ] || touch storage/installed

php artisan optimize:clear
php artisan optimize

cron

echo "Moneta is running."

exec frankenphp run --config /etc/caddy/Caddyfile
