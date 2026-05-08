FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
        linux-headers \
        $PHPIZE_DEPS \
        libxml2-dev \
        curl \
        unzip \
        git

RUN docker-php-ext-install opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

RUN mkdir -p var/cache var/log config/jwt \
    && chown -R www-data:www-data /app

USER www-data

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]