#!/bin/sh
set -e

if [ -n "$DB_HOST" ]; then
    echo "Waiting for the database at $DB_HOST:${DB_PORT:-3306}..."
    until nc -z "$DB_HOST" "${DB_PORT:-3306}" 2>/dev/null; do
        sleep 1
    done
    echo "Database is up."
fi

if [ -z "$APP_KEY" ]; then
    echo "APP_KEY is not set. Run 'php artisan key:generate' locally first (it writes APP_KEY into your .env), then start the containers again." >&2
    exit 1
fi

if [ "$RUN_MIGRATIONS" = "true" ]; then
    php artisan migrate --force
fi

exec "$@"
