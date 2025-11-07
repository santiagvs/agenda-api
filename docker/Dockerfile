FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts

FROM php:8.3-fpm AS app
WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    git unzip libpng-dev libjpeg62-turbo-dev libzip-dev \
 && docker-php-ext-configure gd --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" pdo_mysql gd zip bcmath opcache \
 && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY . /var/www/html
COPY --from=vendor /app/vendor /var/www/html/vendor

RUN rm -f bootstrap/cache/*.php \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R ug+rwx storage bootstrap/cache

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/app/entrypoint.sh /usr/local/bin/app-entrypoint.sh
RUN chmod +x /usr/local/bin/app-entrypoint.sh

FROM nginx:alpine AS nginx
WORKDIR /var/www/html
COPY --from=app /var/www/html/public /var/www/html/public
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
