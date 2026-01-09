FROM ghcr.io/eventpoints/php:main AS composer
ENV APP_ENV=prod APP_DEBUG=0 PHP_OPCACHE_PRELOAD="/app/config/preload.php" PHP_EXPOSE_PHP=off PHP_OPCACHE_VALIDATE_TIMESTAMPS=0
WORKDIR /app
RUN rm -f /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN mkdir -p var/cache var/log
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts

FROM node:21 AS js-builder
WORKDIR /build

COPY --from=composer /app .

FROM composer AS php
WORKDIR /app
COPY --from=js-builder /build .
COPY . .

RUN composer dump-autoload --classmap-authoritative
RUN composer symfony:dump-env prod

RUN php bin/console importmap:install --no-interaction
RUN php bin/console assets:install --no-interaction
RUN php bin/console asset-map:compile --no-interaction

RUN chmod -R 777 var

FROM ghcr.io/eventpoints/caddy:sha-fc43d4e AS caddy
COPY --from=php /app/public public/
