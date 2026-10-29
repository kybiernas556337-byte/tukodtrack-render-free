FROM node:22-bookworm-slim AS assets
WORKDIR /assets
COPY package*.json ./
RUN npm install
COPY resources ./resources
COPY vite.config.js ./
RUN npm run build

FROM php:8.2-apache-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev libonig-dev libzip-dev unzip curl ca-certificates \
    && install -d /usr/share/postgresql-common/pgdg \
    && curl --fail --silent --show-error https://www.postgresql.org/media/keys/ACCC4CF8.asc -o /usr/share/postgresql-common/pgdg/apt.postgresql.org.asc \
    && echo "deb [signed-by=/usr/share/postgresql-common/pgdg/apt.postgresql.org.asc] https://apt.postgresql.org/pub/repos/apt bookworm-pgdg main" > /etc/apt/sources.list.d/pgdg.list \
    && apt-get update && apt-get install -y --no-install-recommends postgresql-client-18 \
    && docker-php-ext-install pdo_pgsql mbstring zip bcmath opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
COPY --from=assets /assets/public/build ./public/build
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache \
    && sed -i 's/\r$//' deploy/start.sh
COPY deploy/apache.conf /etc/apache2/sites-available/000-default.conf
COPY deploy/uploads.ini /usr/local/etc/php/conf.d/uploads.ini
EXPOSE 10000
CMD ["sh", "deploy/start.sh"]
