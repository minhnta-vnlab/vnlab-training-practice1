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

RUN apk update && apk add \
    zlib \
    libpng \
    libjpeg-turbo \
    freetype \
    zlib-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    # && docker-php-ext-install zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

COPY php.ini /usr/local/etc/php/conf.d/custom.ini

CMD ["php-fpm"]