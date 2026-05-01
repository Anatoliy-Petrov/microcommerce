FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
        linux-headers \
        $PHPIZE_DEPS \
        oniguruma-dev \
        libxml2-dev \
        libzip-dev \
        icu-dev \
        curl \
        unzip \
        git

RUN docker-php-ext-install \
        pdo_mysql \
        mbstring \
        xml \
        bcmath \
        zip \
        intl \
        pcntl \
        opcache \
        sockets \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data /var/www/html

USER www-data

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]