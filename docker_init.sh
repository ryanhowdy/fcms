#!/bin/sh

# entrypoint file for starting fcms

set -e
# generate the key if it does not already exist
case `grep APP_KEY .env | sed -e 's/APP_KEY=\s*//'` in
    'base64:'*)
        echo "artisan key already exists...";;
    *)
        echo "generating artisan key..."
        php artisan key:generate;;
esac
# create or update database
echo "create or update database..."
php artisan migrate --force

echo "starting server"
exec "$@"
