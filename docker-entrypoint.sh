#!/bin/sh
set -e

# Create log dirs
mkdir -p /var/log/nginx /var/log/php84 /run/php84 /run/nginx
chown -R nginx:nginx /var/log/nginx /run/php84 /var/www/html
chown nginx:nginx /var/db

# Initialize SQLite DB if not exists
php /var/www/html/db/init.php

# Start PHP-FPM
php-fpm84 -D

# Start nginx in foreground
exec nginx -g 'daemon off;'
