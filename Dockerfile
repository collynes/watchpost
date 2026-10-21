FROM php:8.5-apache
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev libicu-dev libonig-dev libzip-dev unzip git \
    && docker-php-ext-install pdo_pgsql intl mbstring zip pcntl opcache \
    && a2enmod rewrite \
    && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && chown -R www-data:www-data storage bootstrap/cache
EXPOSE 80
