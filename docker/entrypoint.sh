#!/bin/sh
set -e

# Bring the database schema up to date, then cache config, routes and views.
php artisan migrate --force
php artisan optimize

# Hand over to the image's own entrypoint, which starts FrankenPHP.
exec docker-php-entrypoint "$@"
