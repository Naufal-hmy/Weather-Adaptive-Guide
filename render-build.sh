#!/usr/bin/env bash
# exit on error
set -o errexit

echo "Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

echo "Installing Node dependencies..."
npm install

echo "Building frontend assets..."
npm run build

echo "Clearing caches..."
php artisan optimize:clear

echo "Running migrations and seeders..."
php artisan migrate:fresh --seed --force

echo "Build complete!"
