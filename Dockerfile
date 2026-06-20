FROM php:8.3-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Enable mod_rewrite
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Disable security advisories and install dependencies
RUN composer config --global allow-plugins true \
    && composer config --global --no-interaction disable-tls false \
    && composer config --global --no-interaction secure-http true \
    && composer config --global --no-interaction policy.advisories.block false \
    && composer install --no-interaction --optimize-autoloader --no-dev

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 755 storage bootstrap/cache

# Configure Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Create startup script
RUN echo '#!/bin/bash\n\
if [ ! -f .env ] && [ -f .env.example ]; then\n\
    cp .env.example .env\n\
fi\n\
php artisan key:generate --force\n\
php artisan migrate --force\n\
php artisan optimize:clear\n\
apache2-foreground' > /usr/local/bin/start.sh \
    && chmod +x /usr/local/bin/start.sh

ENTRYPOINT ["/usr/local/bin/start.sh"]

EXPOSE 80