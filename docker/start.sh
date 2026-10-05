#!/bin/sh
# runs on every deploy: cache the config, routes, views and events for speed, bring the database
# schema up to date, then hand over to frankenphp. a failed migration stops the deploy here.
set -e
cd /app
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan migrate --force
exec frankenphp run --config /etc/caddy/Caddyfile
