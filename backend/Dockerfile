FROM composer:2 AS composer

FROM node:20-bookworm-slim AS nodebuild
WORKDIR /app
COPY package.json package-lock.json* ./
RUN if [ -f package-lock.json ]; then npm ci; else npm install; fi
COPY . .
RUN npm run build

FROM php:8.3-fpm-bookworm

WORKDIR /var/www/html

RUN apt-get update \
  && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libicu-dev \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j"$(nproc)" pdo_pgsql pgsql zip intl gd opcache \
  && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY . .
COPY --from=nodebuild /app/public/build ./public/build

RUN composer install --no-dev --no-interaction --optimize-autoloader \
  && php -r "file_exists('.env') || copy('.env.example', '.env');" \
  && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

USER www-data
ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]

