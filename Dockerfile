FROM php:8.4-cli-alpine
WORKDIR /var/www/html

RUN apk add --no-cache git curl unzip postgresql-dev libxml2-dev oniguruma-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring xml

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

COPY . .

RUN composer dump-autoload --optimize \
    && chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x docker-entrypoint.sh

EXPOSE 8081
CMD ["./docker-entrypoint.sh"]
