#!/bin/bash

# Laravel 12 Cache Clear Script for Production
# Run after deployment via Laravel Forge

cd /home/forge/sellermind.uz/current || exit

echo "🧹 Clearing all caches..."

# Clear configuration cache
php artisan config:clear

# Clear route cache
php artisan route:clear

# Clear view cache
php artisan view:clear

# Clear event cache
php artisan event:clear

# Clear application cache
php artisan cache:clear

echo "⚡ Optimizing for production..."

# Cache configuration (includes rate limiters)
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Cache events
php artisan event:cache

echo "🔄 Restarting services..."

# Restart queue workers
php artisan queue:restart

# Reload PHP-FPM
sudo -S service php8.3-fpm reload

echo "✅ Deployment cache refresh complete!"
