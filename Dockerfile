# syntax=docker/dockerfile:1.7

ARG PHP_VERSION=8.3
ARG NODE_VERSION=22

FROM php:${PHP_VERSION}-cli-bookworm AS composer-deps
WORKDIR /var/www/html
RUN apt-get update && apt-get install -y --no-install-recommends \
        $PHPIZE_DEPS git libfreetype6-dev libicu-dev libjpeg62-turbo-dev \
        libpng-dev libxml2-dev libzip-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath gd intl pdo_mysql zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts --optimize-autoloader

FROM node:${NODE_VERSION}-bookworm-slim AS frontend-build
WORKDIR /var/www/html
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM php:${PHP_VERSION}-fpm-bookworm AS app
ARG INSTALL_LIBREOFFICE=true
WORKDIR /var/www/html
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6 libicu72 libjpeg62-turbo libpng16-16 libzip4 curl default-mysql-client \
    && if [ "$INSTALL_LIBREOFFICE" = "true" ]; then \
        apt-get install -y --no-install-recommends libreoffice-core libreoffice-writer libreoffice-calc; \
       fi \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer-deps /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=composer-deps /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/
COPY --from=composer-deps /var/www/html/vendor ./vendor
COPY . .
COPY --from=frontend-build /var/www/html/public/build ./public/build
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-production.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/10-opcache-production.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-production.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod 0755 /usr/local/bin/entrypoint \
    && mkdir -p storage/app storage/framework/cache storage/framework/sessions \
       storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache
ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

FROM nginx:1.28-alpine AS web
COPY public /var/www/html/public
COPY --from=frontend-build /var/www/html/public/build /var/www/html/public/build
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/nginx/site-snippets /etc/nginx/site-snippets
RUN ln -s /var/www/html/storage/app/public /var/www/html/public/storage
