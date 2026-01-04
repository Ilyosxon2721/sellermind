#!/bin/bash

# SellerMind AI - Production Deployment Script
# Usage: bash deploy.sh

set -e

echo "🚀 SellerMind AI - Production Deployment"
echo "========================================"

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Check if running as www-data or with sudo
if [ "$EUID" -ne 0 ] && [ "$(whoami)" != "www-data" ]; then
   echo -e "${YELLOW}Warning: Not running as root or www-data. Some commands may require sudo.${NC}"
fi

echo ""
echo "📦 Step 1: Installing Composer dependencies..."
composer install --optimize-autoloader --no-dev --no-interaction

echo ""
echo "📦 Step 2: Installing NPM dependencies..."
npm ci --production=false

echo ""
echo "🏗️  Step 3: Building frontend assets..."
npm run build

echo ""
echo "⚙️  Step 4: Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo ""
echo "🗄️  Step 5: Running database migrations..."
php artisan migrate --force

echo ""
echo "🌱 Step 6: Seeding database (if needed)..."
read -p "Do you want to run seeders? (y/N) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]
then
    php artisan db:seed --force
fi

echo ""
echo "📂 Step 7: Setting file permissions..."
if command -v chown &> /dev/null; then
    sudo chown -R www-data:www-data storage bootstrap/cache
    sudo chmod -R 755 storage bootstrap/cache
else
    chmod -R 755 storage bootstrap/cache
fi

echo ""
echo "🔄 Step 8: Restarting services..."

# Restart Queue Workers (Supervisor)
if command -v supervisorctl &> /dev/null; then
    echo "Restarting queue workers..."
    sudo supervisorctl restart sellermind-worker:*
else
    echo -e "${YELLOW}Supervisor not found. Skipping queue worker restart.${NC}"
fi

# Restart PHP-FPM
if command -v systemctl &> /dev/null; then
    if systemctl list-units --type=service | grep -q php.*fpm; then
        echo "Restarting PHP-FPM..."
        sudo systemctl restart php*-fpm
    fi
fi

# Restart Nginx
if command -v nginx &> /dev/null; then
    echo "Restarting Nginx..."
    sudo nginx -t && sudo systemctl reload nginx
fi

echo ""
echo -e "${GREEN}✅ Deployment completed successfully!${NC}"
echo ""
echo "📋 Post-deployment checklist:"
echo "  1. Check application: APP_URL"
echo "  2. Verify queue workers are running: sudo supervisorctl status"
echo "  3. Check logs: tail -f storage/logs/laravel.log"
echo "  4. Test marketplace integrations"
echo "  5. Verify WebSocket connection (Reverb)"
echo ""
echo "🎉 SellerMind AI is now deployed!"
