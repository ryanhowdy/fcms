#!/bin/bash

set -eux
# generate the key
php artisan key:generate
# create or update database
php artisan migrate

exec "apache2-foreground"
