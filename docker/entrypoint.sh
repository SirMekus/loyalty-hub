#!/bin/sh
set -e

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/testing \
    storage/framework/views storage/logs

if [ -n "$DB_HOST" ]; then
    echo "Waiting for database at ${DB_HOST}:${DB_PORT:-3306}..."
    until php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT:-3306}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
        sleep 2
    done
    echo "Database is up."
fi

case "$2 $3" in
    "artisan serve")
        php artisan migrate --force
        php artisan config:cache
        php artisan route:cache
        touch /tmp/.migrated
        ;;
esac

exec "$@"
