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

# Install PHP extensions (zip needed for composer, pdo_mysql for DB)
RUN docker-php-ext-configure gd \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip

# Verify the extension actually loaded (fails the build early & loudly if not)
RUN php -m | grep -i pdo_mysql

# Enable mod_rewrite
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first (better layer caching)
COPY composer.json composer.lock ./

# Install dependencies WITHOUT running scripts yet (artisan isn't safe to run
# until the full app + .env exist)
RUN composer install --no-interaction --optimize-autoloader --no-dev --no-scripts

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

# Render injects $PORT — Apache needs to listen on it
RUN sed -i 's/Listen 80/Listen ${PORT}/g' /etc/apache2/ports.conf \
    && sed -i 's/:80/:${PORT}/g' /etc/apache2/sites-available/000-default.conf

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

ENV PORT=80
EXPOSE 80

ENTRYPOINT ["/usr/local/bin/start.sh"]