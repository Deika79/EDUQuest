#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

if [ "$#" -gt 0 ]; then
    exec "$@"
fi

if [ "${EDUQUEST_OPTIMIZE_ON_START:-true}" = "true" ]; then
    php artisan package:discover --ansi
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

exec apache2-foreground
