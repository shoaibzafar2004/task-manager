# syntax=docker/dockerfile:1

# ---- 1. Build the front-end assets with Vite ----
FROM node:24-alpine AS assets
WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

# ---- 2. The app, served by FrankenPHP ----
FROM dunglas/frankenphp:1-php8.4

RUN install-php-extensions pdo_mysql zip opcache
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install PHP dependencies first so this layer is reused when only app code changes.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist --no-progress

COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev \
    && mkdir -p storage/logs storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/app-entrypoint
RUN chmod +x /usr/local/bin/app-entrypoint

# Plain HTTP on port 80; docker-compose.yml maps it to APP_PORT on the host.
ENV SERVER_NAME=:80
EXPOSE 80

ENTRYPOINT ["app-entrypoint"]
CMD ["--config", "/etc/frankenphp/Caddyfile", "--adapter", "caddyfile"]
