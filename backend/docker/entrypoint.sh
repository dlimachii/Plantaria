#!/usr/bin/env sh
set -eu

cd /var/www/html

if [ -z "${APP_KEY:-}" ] && ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
  php artisan key:generate --force >/dev/null 2>&1 || true
fi

php artisan storage:link >/dev/null 2>&1 || true

if [ "${MIGRATE_ON_BOOT:-0}" = "1" ]; then
  php artisan migrate --force --no-interaction
  if [ "${SEED_ON_BOOT:-0}" = "1" ]; then
    php artisan db:seed --force --no-interaction
  fi
fi

exec "$@"
