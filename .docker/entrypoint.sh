#!/bin/bash
if [ ! -f .env ]; then
    cp .env.example .env
fi
php artisan key:generate --force
php artisan migrate --force
php artisan optimize:clear
apache2-foreground