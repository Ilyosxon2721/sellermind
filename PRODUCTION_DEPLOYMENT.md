# Production Deployment - Quick Start Guide

**SellerMind AI** - Quick Wins Complete ✅

This guide will get you from zero to production in ~30 minutes.

---

## Prerequisites

- Ubuntu 20.04+ or Debian 11+
- Root or sudo access
- Domain name pointed to your server
- Minimum 2GB RAM, 2 CPU cores

---

## 1. Server Setup (5 minutes)

```bash
# Update system
sudo apt-get update && sudo apt-get upgrade -y

# Install required packages
sudo apt-get install -y \
    nginx \
    mysql-server \
    php8.2 php8.2-fpm php8.2-cli php8.2-mysql php8.2-mbstring \
    php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-redis \
    redis-server \
    supervisor \
    git \
    curl \
    unzip

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Secure MySQL
sudo mysql_secure_installation
```

---

## 2. Clone and Configure (5 minutes)

```bash
# Clone repository
cd /var/www
sudo git clone https://github.com/your-org/sellermind.git
cd sellermind

# Install dependencies
composer install --no-dev --optimize-autoloader

# Set permissions
sudo chown -R www-data:www-data .
sudo chmod -R 775 storage bootstrap/cache

# Setup environment
cp .env.example .env
php artisan key:generate

# Edit .env file
nano .env
```

**Required .env settings:**

```env
APP_NAME=SellerMind
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_DATABASE=sellermind_prod
DB_USERNAME=sellermind_user
DB_PASSWORD=STRONG_PASSWORD

QUEUE_CONNECTION=database
CACHE_DRIVER=redis
SESSION_DRIVER=redis

TELEGRAM_BOT_TOKEN=your_bot_token
```

---

## 3. Database Setup (3 minutes)

```bash
# Create database and user
sudo mysql -u root -p

CREATE DATABASE sellermind_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sellermind_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON sellermind_prod.* TO 'sellermind_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Run migrations
php artisan migrate --force

# Seed templates
php artisan db:seed --class=ReviewTemplatesSeeder

# Cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 4. Queue Workers Setup (5 minutes)

```bash
# Copy supervisor configs
sudo cp deployment/supervisor/sellermind-worker.conf /etc/supervisor/conf.d/
sudo cp deployment/supervisor/sellermind-worker-high.conf /etc/supervisor/conf.d/

# Update paths in config files (change /var/www/sellermind if needed)
sudo nano /etc/supervisor/conf.d/sellermind-worker.conf

# Start workers
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start sellermind-worker:*
sudo supervisorctl start sellermind-worker-high:*

# Verify workers are running
sudo supervisorctl status
```

---

## 5. Scheduler Setup (2 minutes)

```bash
# Add to crontab
sudo crontab -u www-data -e

# Add this line:
* * * * * cd /var/www/sellermind && php artisan schedule:run >> /dev/null 2>&1

# Verify scheduler
php artisan schedule:list
```

---

## 6. Web Server Configuration (5 minutes)

### Nginx Configuration

Create `/etc/nginx/sites-available/sellermind`:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/sellermind/public;
    index index.php index.html;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable site:

```bash
sudo ln -s /etc/nginx/sites-available/sellermind /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

---

## 7. SSL Certificate (3 minutes)

```bash
# Install certbot
sudo apt-get install certbot python3-certbot-nginx

# Get SSL certificate
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Auto-renewal test
sudo certbot renew --dry-run
```

---

## 8. Verification (5 minutes)

```bash
# Run smoke tests
./tests/smoke-tests.sh

# Check services
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status redis-server
sudo supervisorctl status

# Check logs
tail -f storage/logs/laravel.log
tail -f storage/logs/worker.log

# Test in browser
curl https://your-domain.com
```

---

## 9. Post-Deployment Checklist

- [ ] ✅ Homepage loads correctly
- [ ] ✅ Login/Register pages work
- [ ] ✅ Database connection successful
- [ ] ✅ Queue workers running (4 processes)
- [ ] ✅ Cron scheduler configured
- [ ] ✅ SSL certificate active
- [ ] ✅ Redis cache working
- [ ] ✅ Logs accessible and clean
- [ ] ✅ Smoke tests passing

---

## 10. Quick Wins Verification

Test each Quick Win:

### 1. Bulk Operations
```bash
# Check UI at /products
# Test bulk price update, stock update, status change
```

### 2. Telegram Notifications
```bash
# Trigger a notification
# Verify message in Telegram
```

### 3. Smart Promotions
```bash
# Check UI at /promotions
# Test "Find slow-moving products"
# Verify auto-creation works
```

### 4. Sales Analytics
```bash
# Check UI at /analytics
# Verify charts load
# Test period filters (7/30/90 days)
```

### 5. Review Response Generator
```bash
# Check UI at /reviews
# Test AI generation
# Verify templates work
```

---

## Scheduled Tasks Overview

| Task | Frequency | Next Run |
|------|-----------|----------|
| Auto Promotions | Weekly (Mon 9am) | Check: `php artisan schedule:list` |
| Expiring Notifications | Daily (10am) | Check: `php artisan schedule:list` |
| Analytics Caching | Hourly | Check: `php artisan schedule:list` |
| Marketplace Sync | Every 10 min | Check: `php artisan schedule:list` |

---

## Monitoring Commands

```bash
# Check queue status
php artisan queue:failed

# View worker logs
sudo tail -f /var/www/sellermind/storage/logs/worker.log

# View Laravel logs
sudo tail -f /var/www/sellermind/storage/logs/laravel.log

# Restart queue workers
sudo supervisorctl restart sellermind-worker:*

# Clear cache
php artisan cache:clear
```

---

## Updating Production

When deploying updates:

```bash
# 1. Pull latest code
cd /var/www/sellermind
sudo -u www-data git pull origin main

# 2. Install dependencies
sudo -u www-data composer install --no-dev --optimize-autoloader

# 3. Run migrations
sudo -u www-data php artisan migrate --force

# 4. Rebuild cache
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# 5. Restart workers
sudo supervisorctl restart sellermind-worker:*

# 6. Restart PHP-FPM
sudo systemctl restart php8.2-fpm

# 7. Verify
./tests/smoke-tests.sh
```

---

## Troubleshooting

### Workers not running?
```bash
sudo supervisorctl status
sudo supervisorctl restart sellermind-worker:*
sudo tail -f /var/www/sellermind/storage/logs/worker.log
```

### Scheduler not working?
```bash
sudo crontab -u www-data -l
sudo grep CRON /var/log/syslog
php artisan schedule:run
```

### 500 Error?
```bash
sudo tail -f /var/www/sellermind/storage/logs/laravel.log
sudo tail -f /var/log/nginx/error.log
```

### Database connection failed?
```bash
php artisan db:show
mysql -u sellermind_user -p sellermind_prod
```

---

## Security Best Practices

1. **Change default passwords** immediately
2. **Enable firewall:**
   ```bash
   sudo ufw allow 22/tcp
   sudo ufw allow 80/tcp
   sudo ufw allow 443/tcp
   sudo ufw enable
   ```
3. **Disable SSH password login** (use keys only)
4. **Set up automated backups:**
   ```bash
   # Add to crontab
   0 2 * * * mysqldump -u root sellermind_prod | gzip > /backups/sellermind_$(date +\%Y\%m\%d).sql.gz
   ```
5. **Monitor logs regularly**

---

## Support

- **Documentation:** [docs/AUTOMATION_AND_DEPLOYMENT.md](docs/AUTOMATION_AND_DEPLOYMENT.md)
- **Email:** support@sellermind.ai
- **Telegram:** @sellermind_support

---

## Success Metrics

After deployment, track:

- **Response Time:** < 500ms average
- **Uptime:** > 99.9%
- **Queue Processing:** < 1 minute average
- **Failed Jobs:** 0
- **Active Users:** Monitor growth
- **Quick Wins Impact:**
  - Smart Promotions: 25% reduction in slow inventory
  - Review Responses: 70% time saved
  - Analytics: Data-driven decision making
  - Bulk Operations: 80% efficiency gain
  - Telegram: Real-time alerts

---

**Deployed on:** _____________
**By:** _____________
**Version:** 1.0
**Status:** ✅ Production Ready

---

🎉 **Congratulations! SellerMind is now live in production!** 🎉
