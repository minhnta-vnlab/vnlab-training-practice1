FROM php:fpm-alpine

# Install PostgreSQL extension
RUN apk update && apk add \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

RUN apk add --no-cache \
    build-base \
    libtool \
    autoconf \
    gcc \
    make \
    && pecl install apcu \
    && docker-php-ext-enable apcu

COPY php.ini /usr/local/etc/php/conf.d/custom.ini

CMD ["php-fpm"]