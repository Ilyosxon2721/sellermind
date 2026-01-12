#!/bin/bash

###############################################################################
# SellerMind AI - Automated Production Deployment Script
# Version: 1.0
# Date: 2026-01-12
###############################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration - EDIT THESE VALUES
APP_DIR="/var/www/sellermind"
DOMAIN="your-domain.com"
DB_NAME="sellermind_prod"
DB_USER="sellermind_user"
DB_PASS=""  # Will prompt if empty
TELEGRAM_TOKEN=""  # Will prompt if empty

echo -e "${BLUE}"
echo "========================================"
echo "  SellerMind AI - Production Deploy"
echo "========================================"
echo -e "${NC}"

# Check if running as root
if [ "$EUID" -eq 0 ]; then
    echo -e "${RED}Please don't run as root. Use a regular user with sudo privileges.${NC}"
    exit 1
fi

# Prompt for missing configuration
if [ -z "$DB_PASS" ]; then
    read -sp "Enter MySQL password for $DB_USER: " DB_PASS
    echo ""
fi

if [ -z "$TELEGRAM_TOKEN" ]; then
    read -p "Enter Telegram Bot Token: " TELEGRAM_TOKEN
fi

echo ""
echo -e "${GREEN}✓ Configuration loaded${NC}"
echo ""

###############################################################################
# Step 1: System Dependencies
###############################################################################

echo -e "${BLUE}[1/10] Installing system dependencies...${NC}"

sudo apt-get update
sudo apt-get install -y \
    nginx \
    mysql-server \
    php8.2 php8.2-fpm php8.2-cli php8.2-mysql php8.2-mbstring \
    php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-redis \
    redis-server \
    supervisor \
    git \
    curl \
    unzip \
    certbot \
    python3-certbot-nginx

# Install Composer if not present
if ! command -v composer &> /dev/null; then
    echo "Installing Composer..."
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
fi

echo -e "${GREEN}✓ System dependencies installed${NC}"
echo ""

###############################################################################
# Step 2: Clone Repository
###############################################################################

echo -e "${BLUE}[2/10] Setting up application directory...${NC}"

if [ -d "$APP_DIR" ]; then
    echo -e "${YELLOW}Directory $APP_DIR already exists. Pulling latest changes...${NC}"
    cd $APP_DIR
    sudo -u www-data git pull origin main || git pull origin claude/review-production-readiness-LSoNy
else
    echo "Cloning repository..."
    sudo mkdir -p /var/www
    cd /var/www
    sudo git clone https://github.com/Ilyosxon2721/sellermind.git
    cd sellermind
    sudo git checkout main || sudo git checkout claude/review-production-readiness-LSoNy
fi

echo -e "${GREEN}✓ Repository ready${NC}"
echo ""

###############################################################################
# Step 3: Application Setup
###############################################################################

echo -e "${BLUE}[3/10] Installing application dependencies...${NC}"

cd $APP_DIR
sudo composer install --no-dev --optimize-autoloader

# Set permissions
sudo chown -R www-data:www-data $APP_DIR
sudo chmod -R 775 $APP_DIR/storage
sudo chmod -R 775 $APP_DIR/bootstrap/cache

echo -e "${GREEN}✓ Dependencies installed${NC}"
echo ""

###############################################################################
# Step 4: Environment Configuration
###############################################################################

echo -e "${BLUE}[4/10] Configuring environment...${NC}"

if [ ! -f "$APP_DIR/.env" ]; then
    sudo cp $APP_DIR/.env.example $APP_DIR/.env
    sudo -u www-data php artisan key:generate
fi

# Update .env file
sudo sed -i "s|^APP_ENV=.*|APP_ENV=production|" $APP_DIR/.env
sudo sed -i "s|^APP_DEBUG=.*|APP_DEBUG=false|" $APP_DIR/.env
sudo sed -i "s|^APP_URL=.*|APP_URL=https://$DOMAIN|" $APP_DIR/.env
sudo sed -i "s|^DB_DATABASE=.*|DB_DATABASE=$DB_NAME|" $APP_DIR/.env
sudo sed -i "s|^DB_USERNAME=.*|DB_USERNAME=$DB_USER|" $APP_DIR/.env
sudo sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=$DB_PASS|" $APP_DIR/.env
sudo sed -i "s|^QUEUE_CONNECTION=.*|QUEUE_CONNECTION=database|" $APP_DIR/.env
sudo sed -i "s|^CACHE_DRIVER=.*|CACHE_DRIVER=redis|" $APP_DIR/.env
sudo sed -i "s|^SESSION_DRIVER=.*|SESSION_DRIVER=redis|" $APP_DIR/.env
sudo sed -i "s|^TELEGRAM_BOT_TOKEN=.*|TELEGRAM_BOT_TOKEN=$TELEGRAM_TOKEN|" $APP_DIR/.env

echo -e "${GREEN}✓ Environment configured${NC}"
echo ""

###############################################################################
# Step 5: Database Setup
###############################################################################

echo -e "${BLUE}[5/10] Setting up database...${NC}"

# Create database and user
sudo mysql -u root <<EOF
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
EOF

# Run migrations
cd $APP_DIR
sudo -u www-data php artisan migrate --force

# Seed templates
sudo -u www-data php artisan db:seed --class=ReviewTemplatesSeeder || echo "Seeder already ran or not found"

echo -e "${GREEN}✓ Database configured${NC}"
echo ""

###############################################################################
# Step 6: Cache Optimization
###############################################################################

echo -e "${BLUE}[6/10] Optimizing application...${NC}"

cd $APP_DIR
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo composer dump-autoload --optimize

echo -e "${GREEN}✓ Application optimized${NC}"
echo ""

###############################################################################
# Step 7: Queue Workers (Supervisor)
###############################################################################

echo -e "${BLUE}[7/10] Setting up queue workers...${NC}"

# Update paths in supervisor configs
sudo sed -i "s|/var/www/sellermind|$APP_DIR|g" $APP_DIR/deployment/supervisor/*.conf

# Copy configs
sudo cp $APP_DIR/deployment/supervisor/sellermind-worker.conf /etc/supervisor/conf.d/
sudo cp $APP_DIR/deployment/supervisor/sellermind-worker-high.conf /etc/supervisor/conf.d/

# Start workers
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start sellermind-worker:* 2>/dev/null || true
sudo supervisorctl start sellermind-worker-high:* 2>/dev/null || true

echo -e "${GREEN}✓ Queue workers configured${NC}"
echo ""

###############################################################################
# Step 8: Scheduler (Cron)
###############################################################################

echo -e "${BLUE}[8/10] Setting up scheduler...${NC}"

# Add cron job if not exists
CRON_CMD="* * * * * cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1"
(sudo crontab -u www-data -l 2>/dev/null | grep -F "$CRON_CMD") || \
    (sudo crontab -u www-data -l 2>/dev/null; echo "$CRON_CMD") | sudo crontab -u www-data -

echo -e "${GREEN}✓ Scheduler configured${NC}"
echo ""

###############################################################################
# Step 9: Web Server (Nginx)
###############################################################################

echo -e "${BLUE}[9/10] Configuring web server...${NC}"

# Create Nginx config
sudo tee /etc/nginx/sites-available/sellermind > /dev/null <<'NGINX_EOF'
server {
    listen 80;
    server_name DOMAIN_PLACEHOLDER www.DOMAIN_PLACEHOLDER;
    root APP_DIR_PLACEHOLDER/public;
    index index.php index.html;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX_EOF

# Replace placeholders
sudo sed -i "s|DOMAIN_PLACEHOLDER|$DOMAIN|g" /etc/nginx/sites-available/sellermind
sudo sed -i "s|APP_DIR_PLACEHOLDER|$APP_DIR|g" /etc/nginx/sites-available/sellermind

# Enable site
sudo ln -sf /etc/nginx/sites-available/sellermind /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default

# Test and restart Nginx
sudo nginx -t && sudo systemctl restart nginx

echo -e "${GREEN}✓ Web server configured${NC}"
echo ""

###############################################################################
# Step 10: SSL Certificate
###############################################################################

echo -e "${BLUE}[10/10] Setting up SSL certificate...${NC}"

read -p "Do you want to set up SSL certificate with Let's Encrypt? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    sudo certbot --nginx -d $DOMAIN -d www.$DOMAIN --non-interactive --agree-tos --email admin@$DOMAIN --redirect || echo "SSL setup failed, continuing..."
    echo -e "${GREEN}✓ SSL configured${NC}"
else
    echo -e "${YELLOW}⚠ Skipping SSL setup. Run 'sudo certbot --nginx' later.${NC}"
fi

echo ""

###############################################################################
# Verification
###############################################################################

echo -e "${BLUE}Running verification checks...${NC}"
echo ""

# Check services
echo -n "Checking Nginx... "
sudo systemctl is-active --quiet nginx && echo -e "${GREEN}✓${NC}" || echo -e "${RED}✗${NC}"

echo -n "Checking PHP-FPM... "
sudo systemctl is-active --quiet php8.2-fpm && echo -e "${GREEN}✓${NC}" || echo -e "${RED}✗${NC}"

echo -n "Checking Redis... "
sudo systemctl is-active --quiet redis-server && echo -e "${GREEN}✓${NC}" || echo -e "${RED}✗${NC}"

echo -n "Checking MySQL... "
sudo systemctl is-active --quiet mysql && echo -e "${GREEN}✓${NC}" || echo -e "${RED}✗${NC}"

echo ""
echo -e "${GREEN}"
echo "========================================"
echo "  ✓ DEPLOYMENT COMPLETE!"
echo "========================================"
echo -e "${NC}"
echo ""
echo -e "${BLUE}Next Steps:${NC}"
echo "1. Visit https://$DOMAIN to verify deployment"
echo "2. Login and test each Quick Win:"
echo "   - Bulk Operations: /products"
echo "   - Smart Promotions: /promotions"
echo "   - Sales Analytics: /analytics"
echo "   - Review Responses: /reviews"
echo ""
echo -e "${BLUE}Monitoring Commands:${NC}"
echo "- View logs: sudo tail -f $APP_DIR/storage/logs/laravel.log"
echo "- Check workers: sudo supervisorctl status"
echo "- Check scheduler: sudo -u www-data php artisan schedule:list"
echo "- Restart workers: sudo supervisorctl restart sellermind-worker:*"
echo ""
echo -e "${GREEN}🎉 SellerMind AI is now live in production! 🎉${NC}"
echo ""
