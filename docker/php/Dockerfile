FROM php:8.2-cli-alpine

RUN apk add --no-cache \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo_mysql bcmath zip

WORKDIR /var/www/html
