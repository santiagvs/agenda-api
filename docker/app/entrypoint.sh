#!/usr/bin/env bash
set -e
cd /var/www/html

rm -f bootstrap/cache/*.php

php artisan package:discover --ansi || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

TRIES=0
until php -r "new PDO(sprintf('mysql:host=%s;dbname=%s;port=%s', getenv('DB_HOST')?:'db', getenv('DB_DATABASE')?:'agenda_telefonica', getenv('DB_PORT')?:'3306'), getenv('DB_USERNAME')?:'root', getenv('DB_PASSWORD')?:'root');" 2>/dev/null; do
  TRIES=$((TRIES+1))
  if [ "$TRIES" -gt 30 ]; then echo 'DB not ready'; break; fi
  echo "Aguardando DB..."
  sleep 2
done

php artisan migrate --force || true
php artisan storage:link || true

exec /usr/local/sbin/php-fpm -F
