FROM php:8.2-cli-alpine

# 安裝必要擴展
# 1. 加上 icu-dev (修復 IntlChar 報錯必備)
# 2. 加上 libpng-dev 等 (預留給 Excel/圖片處理使用)
RUN apk add --no-cache \
    icu-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    zlib-dev \
    $PHPIZE_DEPS

# 安裝 PHP 核心擴展
RUN docker-php-ext-install \
    pdo_mysql \
    bcmath \
    zip \
    intl

# 如果你需要處理圖片(Excel需求)，建議也把 gd 裝起來
RUN docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install gd

WORKDIR /var/www/html