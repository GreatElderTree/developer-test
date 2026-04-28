#!/bin/sh
chmod -R 775 /var/www/storage /var/www/bootstrap/cache
exec docker-php-entrypoint "$@"
