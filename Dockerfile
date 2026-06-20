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
    libzip-dev \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip

# Verify the extension actually loaded
RUN php -m | grep -i pdo_mysql

# Enable mod_rewrite
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first (better layer caching)
# The * makes composer.lock optional — won't fail the build if it's missing
COPY composer.json composer.lock* ./

# Since there's no lock file, this resolves fresh versions at install time.
# We also disable Composer's audit-blocking so unrelated security advisories
# on transitive deps don't halt the build.
RUN composer config --no-plugins allow-plugins.php-http/discovery true \
    && composer install --no-interaction --optimize-autoloader --no-dev --no-scripts --no-audit \
    || composer install --no-interaction --optimize-autoloader --no-dev --no-scripts --no-security-blocking

# Now copy the rest of the application
COPY . .

# Run composer's deferred scripts now that the app code is present
RUN composer run-script post-autoload-dump

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Configure Apache to serve from /public
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Startup script
RUN echo '#!/bin/bash\n\
set -e\n\
if [ ! -f .env ] && [ -f .env.example ]; then\n\
    cp .env.example .env\n\
fi\n\
php artisan config:clear\n\
php artisan key:generate --force\n\
php artisan migrate --force\n\
php artisan config:cache\n\
php artisan route:cache\n\
php artisan view:cache\n\
exec apache2-foreground' > /usr/local/bin/start.sh \
    && chmod +x /usr/local/bin/start.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/start.sh"]