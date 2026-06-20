#!/bin/bash

# Generate application key if not set
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Generate key if not set
php artisan key:generate --force

# Run migrations
php artisan migrate --force

# Clear cache
php artisan optimize:clear

# Start Apache
apache2-foreground