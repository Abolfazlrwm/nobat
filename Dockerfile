# ---------------------------------------------------------------------------
# Nobat — production image
#
# Stage 1 builds the frontend assets with node, stage 2 assembles a lean
# php-fpm image with the extensions the app needs and the compiled assets.
# ---------------------------------------------------------------------------

# ---------- Stage 1: frontend assets ----------
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY resources ./resources
COPY vite.config.ts tailwind.config.js tsconfig.json ./
RUN npm run build

# ---------- Stage 2: php-fpm runtime ----------
FROM php:8.4-fpm-alpine AS app

# System libraries + PHP extensions (gd backs the invoice PDF — mPDF
# needs it for the font metrics and image handling)
RUN apk add --no-cache \
        icu-dev \
        libzip-dev \
        oniguruma-dev \
        libpng-dev \
        freetype-dev \
        jpeg-dev \
        linux-headers \
        $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        zip \
        intl \
        gd \
        bcmath \
        pcntl \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

# Dependencies first for better layer caching
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction

# Application code
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize \
    && chown -R www-data:www-data storage bootstrap/cache \
    && php artisan storage:link || true

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
