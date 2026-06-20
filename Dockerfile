# Use PHP 8.3 with Apache
FROM php:8.3-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy existing application directory contents
COPY . /var/www/html

# Disable security advisories and install dependencies
RUN rm -f composer.lock \
    && composer config --global --no-interaction disable-tls false \
    && composer config --global --no-interaction secure-http true \
    && composer config --global --no-interaction allow-plugins true \
    && composer config --global --no-interaction process-timeout 2000 \
    && composer config --global --no-interaction repo.packagist.org false \
    && composer require laravel/framework:10.0.0 --no-interaction --no-scripts \
    && composer install --no-interaction --optimize-autoloader --no-dev --prefer-dist

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Configure Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Create entrypoint script
RUN echo '#!/bin/bash\n\
if [ ! -f .env ] && [ -f .env.example ]; then\n\
    cp .env.example .env\n\
fi\n\
php artisan key:generate --force\n\
php artisan migrate --force\n\
php artisan optimize:clear\n\
apache2-foreground' > /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

EXPOSE 80