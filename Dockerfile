# syntax=docker/dockerfile:1.7

ARG WORDPRESS_IMAGE_TAG=php8.3-apache

FROM wordpress:${WORDPRESS_IMAGE_TAG}

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libicu-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install -j"$(nproc)" intl zip \
    && a2enmod expires headers rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --chown=www-data:www-data . /var/www/html/

RUN find /var/www/html -type d -exec chmod 0755 {} + \
    && find /var/www/html -type f -exec chmod 0644 {} + \
    && mkdir -p /var/www/html/wp-content/uploads \
    && chown -R www-data:www-data /var/www/html/wp-content

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD php -r '$$s=@fsockopen("127.0.0.1",80); if(!$$s){exit(1);} fclose($$s);'

CMD ["apache2-foreground"]
