# syntax=docker/dockerfile:1

# -----------------------------------------------------------------------------
# Composer dependencies
# -----------------------------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist \
    --ignore-platform-reqs

COPY . .
RUN composer dump-autoload \
    --optimize \
    --classmap-authoritative \
    --no-dev \
    --no-interaction

# -----------------------------------------------------------------------------
# Frontend (Vite) production build
# -----------------------------------------------------------------------------
FROM node:22-bookworm-slim AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

# -----------------------------------------------------------------------------
# Runtime: PHP-FPM + Nginx (Coolify / production)
# -----------------------------------------------------------------------------
FROM serversideup/php:8.3-fpm-nginx

USER root

WORKDIR /var/www/html

# Ensure common Laravel PHP extensions are present (image usually includes these)
RUN install-php-extensions gd intl exif bcmath

COPY --chown=www-data:www-data . /var/www/html
COPY --from=vendor --chown=www-data:www-data /app/vendor /var/www/html/vendor
COPY --from=frontend --chown=www-data:www-data /app/public/build /var/www/html/public/build

RUN mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
        public/uploads/genel \
        public/uploads/portal-sayfalar \
        public/uploads/sertifika \
    && chown -R www-data:www-data storage bootstrap/cache public/uploads \
    && chmod -R ug+rwx storage bootstrap/cache public/uploads

COPY --chmod=755 docker/entrypoint.d/ /etc/entrypoint.d/
RUN docker-php-serversideup-s6-init

# Coolify terminates TLS; container serves HTTP on 8080
ENV SSL_MODE=off \
    AUTORUN_ENABLED=false \
    PHP_OPCACHE_ENABLE=1

EXPOSE 8080 8443

USER www-data
