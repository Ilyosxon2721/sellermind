# 🚀 PRODUCTION DEPLOYMENT - FINAL CHECKLIST

**Project:** SellerMind AI
**Branch:** `claude/review-production-readiness-LSoNy`
**Status:** ✅ READY FOR PRODUCTION
**Date:** 2026-01-12

---

## 📋 Pre-Deployment Summary

### What's Included:
✅ **5 Quick Wins** - All implemented and tested
✅ **Automation** - Laravel Scheduler + Queue Jobs
✅ **Documentation** - 3 comprehensive guides (2000+ lines)
✅ **Testing** - Smoke tests for critical paths
✅ **Infrastructure** - Supervisor configs for production

### Git Status:
- **Branch:** `claude/review-production-readiness-LSoNy`
- **Commits:** 6 commits (all pushed to remote)
- **Latest Commit:** `2932013` - Automation & Deployment Infrastructure

---

## 🔗 Step 1: Create Pull Request

Go to your GitHub repository and create a PR:

**URL:** `https://github.com/Ilyosxon2721/sellermind/compare/main...claude/review-production-readiness-LSoNy`

**PR Title:**
```
🚀 Production Ready: All 5 Quick Wins + Automation Infrastructure
```

**PR Description:** (Copy from PULL_REQUEST_TEMPLATE.md in this directory)

Key highlights for PR:
- All 5 Quick Wins implemented (Bulk Ops, Notifications, Promotions, Analytics, Reviews)
- Complete automation with Laravel Scheduler
- Queue jobs for background processing
- Supervisor configurations
- Comprehensive documentation (2000+ lines)
- Smoke tests included

---

## 🎯 Step 2: Merge to Main

After code review and approval:

```bash
# Option 1: Merge via GitHub UI (Recommended)
# Click "Merge pull request" on GitHub

# Option 2: Merge locally
git checkout main
git merge claude/review-production-readiness-LSoNy
git push origin main
```

---

## 🖥️ Step 3: Server Preparation

### 3.1 Connect to Server

```bash
ssh user@your-server-ip
```

### 3.2 Install Dependencies

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

# Verify installations
php -v
composer -V
mysql --version
redis-cli ping
```

### 3.3 Secure MySQL

```bash
sudo mysql_secure_installation
# Answer: Y to all questions
# Set strong root password
```

---

## 📦 Step 4: Deploy Application

### 4.1 Clone Repository

```bash
cd /var/www
sudo git clone https://github.com/Ilyosxon2721/sellermind.git
cd sellermind
sudo git checkout main  # Use main branch after merge
```

### 4.2 Install PHP Dependencies

```bash
# Install dependencies (without dev packages)
sudo composer install --no-dev --optimize-autoloader

# Set permissions
sudo chown -R www-data:www-data /var/www/sellermind
sudo chmod -R 775 /var/www/sellermind/storage
sudo chmod -R 775 /var/www/sellermind/bootstrap/cache
```

### 4.3 Configure Environment

```bash
# Copy environment file
sudo cp .env.example .env

# Generate application key
sudo -u www-data php artisan key:generate

# Edit environment variables
sudo nano .env
```

**Required .env Settings:**

```env
APP_NAME=SellerMind
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sellermind_prod
DB_USERNAME=sellermind_user
DB_PASSWORD=YOUR_STRONG_PASSWORD_HERE

QUEUE_CONNECTION=database
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Telegram Bot
TELEGRAM_BOT_TOKEN=your_bot_token_here

# AI Service (if using)
AI_SERVICE_API_KEY=your_api_key_here

# Marketplace APIs
WB_API_KEY=your_wildberries_api_key
OZON_CLIENT_ID=your_ozon_client_id
OZON_API_KEY=your_ozon_api_key
YANDEX_MARKET_TOKEN=your_yandex_token
```

---

## 🗄️ Step 5: Database Setup

### 5.1 Create Database & User

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE sellermind_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sellermind_user'@'localhost' IDENTIFIED BY 'YOUR_STRONG_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON sellermind_prod.* TO 'sellermind_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 5.2 Run Migrations

```bash
cd /var/www/sellermind

# Run migrations
sudo -u www-data php artisan migrate --force

# Seed review templates (IMPORTANT!)
sudo -u www-data php artisan db:seed --class=ReviewTemplatesSeeder
```

### 5.3 Optimize Application

```bash
# Cache configuration
sudo -u www-data php artisan config:cache

# Cache routes
sudo -u www-data php artisan route:cache

# Cache views
sudo -u www-data php artisan view:cache

# Optimize Composer autoloader
sudo composer dump-autoload --optimize
```

---

## 🔄 Step 6: Queue Workers Setup (Supervisor)

### 6.1 Copy Supervisor Configs

```bash
# Copy worker configuration files
sudo cp /var/www/sellermind/deployment/supervisor/sellermind-worker.conf /etc/supervisor/conf.d/
sudo cp /var/www/sellermind/deployment/supervisor/sellermind-worker-high.conf /etc/supervisor/conf.d/
```

### 6.2 Verify & Update Paths

```bash
# Check paths in config files
sudo nano /etc/supervisor/conf.d/sellermind-worker.conf

# Make sure paths are correct:
# command=php /var/www/sellermind/artisan queue:work ...
# stdout_logfile=/var/www/sellermind/storage/logs/worker.log
```

### 6.3 Start Workers

```bash
# Reload supervisor configuration
sudo supervisorctl reread
sudo supervisorctl update

# Start workers
sudo supervisorctl start sellermind-worker:*
sudo supervisorctl start sellermind-worker-high:*

# Check status (should show RUNNING)
sudo supervisorctl status

# Expected output:
# sellermind-worker:sellermind-worker_00   RUNNING   pid 12345
# sellermind-worker:sellermind-worker_01   RUNNING   pid 12346
# ...
```

---

## ⏰ Step 7: Scheduler Setup (Cron)

### 7.1 Add Crontab Entry

```bash
# Edit crontab as www-data user
sudo crontab -u www-data -e

# Add this line at the end:
* * * * * cd /var/www/sellermind && php artisan schedule:run >> /dev/null 2>&1
```

### 7.2 Verify Scheduler

```bash
# List scheduled tasks
cd /var/www/sellermind
sudo -u www-data php artisan schedule:list

# Expected output:
# 0 9 * * 1 ........ create-auto-promotions (Next: 2026-01-13 09:00:00)
# 0 10 * * * ....... notify-expiring-promotions (Next: 2026-01-12 10:00:00)
# 0 * * * * ........ cache-sales-analytics (Next: 2026-01-12 15:00:00)
```

---

## 🌐 Step 8: Web Server Configuration (Nginx)

### 8.1 Create Nginx Config

```bash
sudo nano /etc/nginx/sites-available/sellermind
```

**Paste this configuration:**

```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    root /var/www/sellermind/public;
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
```

### 8.2 Enable Site

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/sellermind /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx
```

---

## 🔒 Step 9: SSL Certificate (Let's Encrypt)

```bash
# Install Certbot
sudo apt-get install certbot python3-certbot-nginx

# Get SSL certificate
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Follow prompts:
# - Enter email address
# - Agree to terms
# - Redirect HTTP to HTTPS: Yes

# Test auto-renewal
sudo certbot renew --dry-run
```

---

## ✅ Step 10: Verification & Testing

### 10.1 Run Smoke Tests

```bash
cd /var/www/sellermind

# Run smoke tests
./tests/smoke-tests.sh

# Should see mostly green ✓ marks
```

### 10.2 Check Services

```bash
# Check Nginx
sudo systemctl status nginx

# Check PHP-FPM
sudo systemctl status php8.2-fpm

# Check Redis
sudo systemctl status redis-server

# Check MySQL
sudo systemctl status mysql

# Check Queue Workers
sudo supervisorctl status

# All should show "active (running)" or "RUNNING"
```

### 10.3 Check Logs

```bash
# Laravel application logs
sudo tail -f /var/www/sellermind/storage/logs/laravel.log

# Queue worker logs
sudo tail -f /var/www/sellermind/storage/logs/worker.log

# Nginx error logs
sudo tail -f /var/log/nginx/error.log

# Should see no critical errors
```

### 10.4 Test in Browser

Open browser and test:

1. **Homepage:** `https://your-domain.com` - Should load
2. **Login:** `https://your-domain.com/login` - Should work
3. **Dashboard:** `https://your-domain.com/dashboard` - Should load (after login)
4. **Promotions:** `https://your-domain.com/promotions` - Should load
5. **Analytics:** `https://your-domain.com/analytics` - Should load
6. **Reviews:** `https://your-domain.com/reviews` - Should load

### 10.5 Test Quick Wins

1. **Bulk Operations:** Go to Products page, test bulk actions
2. **Smart Promotions:** Create a promotion, verify it works
3. **Analytics:** Check dashboard loads with charts
4. **Reviews:** Test AI response generation
5. **Notifications:** Trigger an event, check Telegram

---

## 🔥 Step 11: Firewall Configuration

```bash
# Enable UFW firewall
sudo ufw allow 22/tcp   # SSH
sudo ufw allow 80/tcp   # HTTP
sudo ufw allow 443/tcp  # HTTPS
sudo ufw enable

# Check status
sudo ufw status
```

---

## 💾 Step 12: Backup Configuration

### 12.1 Database Backup Script

```bash
sudo nano /usr/local/bin/backup-sellermind-db.sh
```

**Paste:**

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/sellermind"
mkdir -p $BACKUP_DIR

mysqldump -u sellermind_user -p'YOUR_PASSWORD' sellermind_prod | gzip > $BACKUP_DIR/sellermind_$DATE.sql.gz

# Keep only last 7 days
find $BACKUP_DIR -name "sellermind_*.sql.gz" -mtime +7 -delete
```

```bash
# Make executable
sudo chmod +x /usr/local/bin/backup-sellermind-db.sh

# Add to crontab (daily at 2am)
sudo crontab -e
# Add: 0 2 * * * /usr/local/bin/backup-sellermind-db.sh
```

---

## 📊 Step 13: Monitoring Setup

### 13.1 Check Scheduled Tasks Are Running

```bash
# Wait a few minutes after deployment, then check logs
sudo grep "Smart Promotions" /var/www/sellermind/storage/logs/laravel.log
sudo grep "cache-sales-analytics" /var/www/sellermind/storage/logs/laravel.log
```

### 13.2 Monitor Queue

```bash
# Check queue size
cd /var/www/sellermind
sudo -u www-data php artisan queue:work --once

# Check for failed jobs
sudo -u www-data php artisan queue:failed
```

---

## 🎉 DEPLOYMENT COMPLETE!

### ✅ Final Checklist

- [ ] Application accessible via HTTPS
- [ ] All pages load correctly
- [ ] Database connected and migrated
- [ ] Queue workers running (6 processes total)
- [ ] Cron scheduler configured
- [ ] SSL certificate active
- [ ] Logs clean (no critical errors)
- [ ] Smoke tests passing
- [ ] Backups configured
- [ ] Firewall enabled

---

## 📞 Post-Deployment

### Important Commands

```bash
# Restart queue workers (after code updates)
sudo supervisorctl restart sellermind-worker:*

# Clear cache
sudo -u www-data php artisan cache:clear

# View logs
sudo tail -f /var/www/sellermind/storage/logs/laravel.log

# Check scheduler
sudo -u www-data php artisan schedule:list

# Retry failed jobs
sudo -u www-data php artisan queue:retry all
```

### Monitoring URLs

- **Application:** https://your-domain.com
- **Health Check:** https://your-domain.com/api/health (if implemented)
- **Login:** https://your-domain.com/login

### Support

- **Documentation:** `/var/www/sellermind/docs/`
- **AUTOMATION_AND_DEPLOYMENT.md** - Full deployment guide
- **PRODUCTION_DEPLOYMENT.md** - Quick start
- **Quick Wins Guides** - Feature-specific docs

---

## 🚨 Troubleshooting

**Problem:** Workers not processing jobs
```bash
sudo supervisorctl restart sellermind-worker:*
sudo tail -f /var/www/sellermind/storage/logs/worker.log
```

**Problem:** Scheduler not running
```bash
sudo crontab -u www-data -l
sudo -u www-data php artisan schedule:run
```

**Problem:** 500 Error
```bash
sudo tail -f /var/www/sellermind/storage/logs/laravel.log
sudo tail -f /var/log/nginx/error.log
```

---

**Deployment Date:** _____________
**Deployed By:** _____________
**Version:** 1.0 - All Quick Wins + Automation
**Status:** ✅ PRODUCTION

🎊 **SellerMind AI is now LIVE!** 🎊
