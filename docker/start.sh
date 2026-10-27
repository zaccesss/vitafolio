#!/bin/sh
# runs on every deploy: cache the config, routes, views and events for speed, then check the
# database. when it answers, the schema is brought up to date and a failed migration stops the
# deploy here. when it cannot be reached the site still starts, showing its maintenance page, so
# the host's health check on /up passes instead of restarting the container in a loop.
set -e
cd /app
# maintenance state lives in a file inside the container. the cache driver would keep it in the
# database, which is exactly what is missing when the site needs to show the maintenance page
export APP_MAINTENANCE_DRIVER=file
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# /cron looks for this marker, so it only lifts a maintenance page this script put up
marker=storage/framework/database-unreachable
if php artisan vitafolio:db-check --timeout=5; then
    rm -f "$marker"
    php artisan up
    php artisan migrate --force
else
    echo "WARNING: the database cannot be reached, so the site starts in maintenance mode. /cron brings it back once the database answers." >&2
    php artisan down --render=errors::503 --retry=60
    touch "$marker"
fi
exec frankenphp run --config /etc/caddy/Caddyfile
