#!/bin/bash

set -eux

# create or update database
php artisan migrate

exec "php-fpm"
