FROM php:8.1-apache

RUN apt-get update && apt-get install -y \
    unzip \
    libonig-dev \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libxml2-dev \
    libmagickwand-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    intl \
    zip \
    gd \
    opcache

RUN pecl install imagick \
    && docker-php-ext-enable imagick

RUN a2enmod rewrite
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set up application directory
WORKDIR /app

# Copy source code from host
COPY . .

# Install dependencies (including dev for testing)
RUN composer update --optimize-autoloader --no-interaction

# Copy Apache virtual host configuration
COPY container/yii2-vhost.conf /etc/apache2/sites-available/000-default.conf

# Set proper permissions
RUN chown -R www-data:www-data /app \
    && chmod -R 755 /app \
    && chmod -R 777 /app/runtime \
    && chmod -R 777 /app/web/assets

EXPOSE 80
CMD ["apache2-foreground"]