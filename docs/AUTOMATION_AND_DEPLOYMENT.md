# SellerMind AI - Automation & Deployment Guide

**Version:** 1.0
**Date:** 2026-01-12
**Status:** Production Ready ✅

---

## Table of Contents

1. [Automation Overview](#automation-overview)
2. [Laravel Scheduler Setup](#laravel-scheduler-setup)
3. [Queue Workers Configuration](#queue-workers-configuration)
4. [Supervisor Setup](#supervisor-setup)
5. [Cron Jobs](#cron-jobs)
6. [Background Jobs](#background-jobs)
7. [Production Deployment](#production-deployment)
8. [Monitoring & Logging](#monitoring--logging)
9. [Troubleshooting](#troubleshooting)

---

## Automation Overview

SellerMind использует Laravel Scheduler и Queue System для автоматизации критичных бизнес-процессов:

### Scheduled Tasks (Cron)

| Task | Frequency | Purpose | Time |
|------|-----------|---------|------|
| **Auto Promotions** | Weekly (Monday) | Создание промо для неликвида | 09:00 |
| **Expiring Notifications** | Daily | Уведомления об истекающих акциях | 10:00 |
| **Analytics Caching** | Hourly | Предварительный расчет аналитики | Every hour |
| **Marketplace Sync Orders** | 10 min | Синхронизация заказов | Every 10 min |
| **Marketplace Sync Stocks** | Hourly | Синхронизация остатков | Every hour |

### Queue Jobs (Background Processing)

| Job | Queue | Purpose | Timeout |
|-----|-------|---------|---------|
| `ProcessAutoPromotionsJob` | default | Обработка автопромо | 300s |
| `SendPromotionExpiringNotificationsJob` | high | Отправка уведомлений о промо | 120s |
| `BulkGenerateReviewResponsesJob` | default | Массовая генерация ответов | 600s |
| `TelegramNotification` | high | Telegram уведомления | 60s |

---

## Laravel Scheduler Setup

### 1. Verify Scheduled Tasks

```bash
# List all scheduled tasks
php artisan schedule:list

# Expected output:
# 0 9 * * 1 ........ create-auto-promotions
# 0 10 * * * ....... notify-expiring-promotions
# 0 * * * * ........ cache-sales-analytics
# */10 * * * * ..... marketplace:sync-orders --days=7
```

### 2. Test Scheduler Locally

```bash
# Run scheduler in development (runs continuously)
php artisan schedule:work

# Run scheduler once (for testing)
php artisan schedule:run
```

### 3. View Specific Task

All scheduled tasks are defined in `routes/console.php`:

```php
// Smart Promotions: Auto creation
Schedule::call(function () {
    $companies = \App\Models\Company::where('is_active', true)->get();
    foreach ($companies as $company) {
        \App\Jobs\ProcessAutoPromotionsJob::dispatch($company->id);
    }
})->weekly()->mondays()->at('09:00');
```

---

## Queue Workers Configuration

### Queue System Architecture

```
┌─────────────┐       ┌──────────────┐       ┌─────────────┐
│  Scheduler  │──────▶│  Queue Jobs  │──────▶│   Workers   │
│  (Cron)     │       │  (Database)  │       │ (Supervisor)│
└─────────────┘       └──────────────┘       └─────────────┘
                             │
                             ▼
                      ┌──────────────┐
                      │   Execution  │
                      └──────────────┘
```

### Available Queues

1. **high** - Priority queue for time-sensitive tasks (notifications)
2. **default** - Standard queue for regular background jobs
3. **low** - Low priority queue for batch operations

### Queue Configuration

**File:** `config/queue.php`

```php
'connections' => [
    'database' => [
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 300,
    ],
],
```

### Running Queue Workers Manually

```bash
# Default queue
php artisan queue:work

# Specific queue
php artisan queue:work --queue=high,default

# With options
php artisan queue:work --sleep=3 --tries=3 --timeout=300

# Run once (for testing)
php artisan queue:work --once
```

### Monitor Queue

```bash
# Check failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry {id}

# Retry all failed
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

---

## Supervisor Setup

Supervisor ensures queue workers are always running and auto-restart on failure.

### 1. Install Supervisor

```bash
sudo apt-get update
sudo apt-get install supervisor
```

### 2. Copy Configuration Files

```bash
# Copy worker configs to supervisor directory
sudo cp deployment/supervisor/sellermind-worker.conf /etc/supervisor/conf.d/
sudo cp deployment/supervisor/sellermind-worker-high.conf /etc/supervisor/conf.d/
```

### 3. Update Paths in Config

Edit `/etc/supervisor/conf.d/sellermind-worker.conf`:

```ini
[program:sellermind-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/sellermind/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/sellermind/storage/logs/worker.log
stopwaitsecs=3600
```

**Important:** Change `/var/www/sellermind` to your actual path!

### 4. Start Supervisor

```bash
# Reload supervisor config
sudo supervisorctl reread
sudo supervisorctl update

# Start workers
sudo supervisorctl start sellermind-worker:*
sudo supervisorctl start sellermind-worker-high:*

# Check status
sudo supervisorctl status

# Expected output:
# sellermind-worker:sellermind-worker_00   RUNNING   pid 12345, uptime 0:01:00
# sellermind-worker:sellermind-worker_01   RUNNING   pid 12346, uptime 0:01:00
# ...
```

### 5. Manage Workers

```bash
# Stop all workers
sudo supervisorctl stop sellermind-worker:*

# Restart workers (after code deploy)
sudo supervisorctl restart sellermind-worker:*

# View logs
sudo tail -f /var/www/sellermind/storage/logs/worker.log
```

---

## Cron Jobs

### Setup System Cron

Add Laravel scheduler to crontab:

```bash
# Edit crontab as web server user
sudo crontab -u www-data -e

# Add this line:
* * * * * cd /var/www/sellermind && php artisan schedule:run >> /dev/null 2>&1
```

This runs Laravel scheduler every minute, which then executes tasks based on their defined schedule.

### Verify Cron is Running

```bash
# Check cron logs
sudo grep CRON /var/log/syslog

# Check Laravel scheduler logs
tail -f storage/logs/laravel.log | grep "Schedule"
```

### Alternative: Systemd Timer (Modern Approach)

**File:** `/etc/systemd/system/sellermind-scheduler.service`

```ini
[Unit]
Description=SellerMind Scheduler
After=network.target

[Service]
Type=oneshot
User=www-data
WorkingDirectory=/var/www/sellermind
ExecStart=/usr/bin/php artisan schedule:run

[Install]
WantedBy=multi-user.target
```

**File:** `/etc/systemd/system/sellermind-scheduler.timer`

```ini
[Unit]
Description=Run SellerMind Scheduler every minute
Requires=sellermind-scheduler.service

[Timer]
OnBootSec=1min
OnUnitActiveSec=1min
Unit=sellermind-scheduler.service

[Install]
WantedBy=timers.target
```

**Enable:**

```bash
sudo systemctl enable sellermind-scheduler.timer
sudo systemctl start sellermind-scheduler.timer
sudo systemctl status sellermind-scheduler.timer
```

---

## Background Jobs

### Job Lifecycle

```
1. Job Dispatched ──▶ 2. Added to Queue ──▶ 3. Worker Picks Up ──▶ 4. Execution
                                                                      │
                                                                      ├──▶ Success
                                                                      └──▶ Failure (Retry)
```

### Dispatch Jobs Manually

```php
use App\Jobs\ProcessAutoPromotionsJob;

// Dispatch immediately
ProcessAutoPromotionsJob::dispatch($companyId);

// Dispatch to specific queue
ProcessAutoPromotionsJob::dispatch($companyId)->onQueue('high');

// Dispatch with delay
ProcessAutoPromotionsJob::dispatch($companyId)->delay(now()->addMinutes(10));

// Dispatch after response (non-blocking)
ProcessAutoPromotionsJob::dispatchAfterResponse($companyId);
```

### Job Priority

Jobs are processed based on:

1. **Queue Priority:** `high` > `default` > `low`
2. **Order:** First-in, first-out within same queue
3. **Attempts:** Failed jobs are retried based on `tries` setting

### Example: Priority Notification

```php
// High priority (processed first)
SendPromotionExpiringNotificationsJob::dispatch($companyId)->onQueue('high');

// Standard priority
ProcessAutoPromotionsJob::dispatch($companyId); // Uses 'default' queue
```

---

## Production Deployment

### Pre-Deployment Checklist

- [ ] ✅ All Quick Wins implemented and tested
- [ ] ✅ Database migrations created
- [ ] ✅ Seeders for templates created
- [ ] ✅ Environment variables configured
- [ ] ✅ Queue tables migrated
- [ ] ✅ Supervisor configs prepared
- [ ] ✅ Cron job configured
- [ ] ✅ SSL certificates ready
- [ ] ✅ Database backups enabled

### Step-by-Step Deployment

#### 1. Server Setup

```bash
# Update system
sudo apt-get update && sudo apt-get upgrade -y

# Install PHP 8.2+
sudo apt-get install php8.2 php8.2-fpm php8.2-cli php8.2-mysql php8.2-mbstring \
  php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-redis

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Supervisor
sudo apt-get install supervisor

# Install Redis (for cache and queue)
sudo apt-get install redis-server
sudo systemctl enable redis-server
```

#### 2. Clone Repository

```bash
cd /var/www
sudo git clone https://github.com/your-org/sellermind.git
cd sellermind
```

#### 3. Install Dependencies

```bash
# PHP dependencies
composer install --no-dev --optimize-autoloader

# Set permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

#### 4. Configure Environment

```bash
# Copy environment file
cp .env.example .env

# Generate app key
php artisan key:generate

# Edit environment
nano .env
```

**Required Environment Variables:**

```env
APP_NAME=SellerMind
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sellermind.ai

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sellermind_prod
DB_USERNAME=sellermind_user
DB_PASSWORD=STRONG_PASSWORD_HERE

QUEUE_CONNECTION=database
CACHE_DRIVER=redis
SESSION_DRIVER=redis

# Telegram
TELEGRAM_BOT_TOKEN=your_bot_token_here

# AI Service (if using external AI)
AI_SERVICE_API_KEY=your_ai_api_key

# Marketplace APIs
WB_API_KEY=your_wildberries_key
OZON_CLIENT_ID=your_ozon_id
OZON_API_KEY=your_ozon_key
```

#### 5. Database Setup

```bash
# Run migrations
php artisan migrate --force

# Seed templates
php artisan db:seed --class=ReviewTemplatesSeeder

# Optional: Seed demo data
# php artisan db:seed
```

#### 6. Cache Optimization

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

#### 7. Setup Queue Workers

```bash
# Copy supervisor configs
sudo cp deployment/supervisor/sellermind-worker.conf /etc/supervisor/conf.d/
sudo cp deployment/supervisor/sellermind-worker-high.conf /etc/supervisor/conf.d/

# Update paths in configs
sudo nano /etc/supervisor/conf.d/sellermind-worker.conf
# Change /var/www/sellermind to your actual path

# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start sellermind-worker:*
sudo supervisorctl start sellermind-worker-high:*
```

#### 8. Setup Cron

```bash
# Add to crontab
sudo crontab -u www-data -e

# Add line:
* * * * * cd /var/www/sellermind && php artisan schedule:run >> /dev/null 2>&1
```

#### 9. Configure Web Server

**Nginx Example:**

```nginx
server {
    listen 80;
    server_name sellermind.ai;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name sellermind.ai;

    root /var/www/sellermind/public;
    index index.php index.html;

    ssl_certificate /etc/letsencrypt/live/sellermind.ai/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/sellermind.ai/privkey.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

#### 10. SSL Certificate (Let's Encrypt)

```bash
sudo apt-get install certbot python3-certbot-nginx
sudo certbot --nginx -d sellermind.ai -d www.sellermind.ai
```

#### 11. Verify Deployment

```bash
# Check queue workers
sudo supervisorctl status

# Check scheduled tasks
php artisan schedule:list

# Check logs
tail -f storage/logs/laravel.log

# Test API
curl https://sellermind.ai/api/health
```

---

## Monitoring & Logging

### Log Files

```bash
# Laravel application logs
tail -f storage/logs/laravel.log

# Queue worker logs
tail -f storage/logs/worker.log

# Scheduler logs
tail -f storage/logs/promotions.log
tail -f storage/logs/marketplace-sync.log

# Nginx access logs
sudo tail -f /var/log/nginx/access.log

# Nginx error logs
sudo tail -f /var/log/nginx/error.log
```

### Laravel Telescope (Optional)

For advanced monitoring in development/staging:

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

Access at: `https://sellermind.ai/telescope`

### Health Check Endpoint

Create a health check endpoint for monitoring:

**routes/api.php:**

```php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'queue_size' => DB::table('jobs')->count(),
        'failed_jobs' => DB::table('failed_jobs')->count(),
    ]);
});
```

### Automated Monitoring

Use external monitoring service (UptimeRobot, Pingdom, etc.):

- Monitor `/api/health` every 5 minutes
- Alert if response time > 2 seconds
- Alert if status !== 'ok'
- Monitor queue size (alert if > 1000)

---

## Troubleshooting

### Queue Workers Not Processing Jobs

**Problem:** Jobs stuck in queue

**Solutions:**

```bash
# Check worker status
sudo supervisorctl status

# Restart workers
sudo supervisorctl restart sellermind-worker:*

# Check for failed jobs
php artisan queue:failed

# Clear stuck jobs (careful!)
php artisan queue:flush
```

### Scheduler Not Running

**Problem:** Scheduled tasks not executing

**Solutions:**

```bash
# 1. Verify cron is configured
sudo crontab -u www-data -l

# 2. Check cron logs
sudo grep CRON /var/log/syslog

# 3. Run manually to test
php artisan schedule:run

# 4. Check task is defined
php artisan schedule:list
```

### High Memory Usage

**Problem:** Workers consuming too much memory

**Solutions:**

```bash
# 1. Add memory limit to supervisor config
command=php -d memory_limit=512M /var/www/sellermind/artisan queue:work

# 2. Restart workers more frequently
# Add to config:
# stopwaitsecs=1800  (30 minutes)

# 3. Reduce number of workers
# Change numprocs=4 to numprocs=2
```

### Jobs Timing Out

**Problem:** Jobs fail with timeout error

**Solutions:**

```php
// Increase timeout in job class
public int $timeout = 600; // 10 minutes

// Or in supervisor config
command=php artisan queue:work --timeout=600
```

### Database Connection Issues

**Problem:** "Too many connections" error

**Solutions:**

```bash
# 1. Increase MySQL max connections
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
# Add: max_connections = 200

# 2. Restart MySQL
sudo systemctl restart mysql

# 3. Optimize queue worker database connections
# Use persistent connections in .env
DB_PERSISTENT=true
```

---

## Quick Reference Commands

### Daily Operations

```bash
# Check system status
sudo supervisorctl status
php artisan queue:failed
php artisan schedule:list

# View logs
tail -f storage/logs/laravel.log
tail -f storage/logs/worker.log

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### After Code Deployment

```bash
# 1. Pull latest code
git pull origin main

# 2. Install dependencies
composer install --no-dev --optimize-autoloader

# 3. Run migrations
php artisan migrate --force

# 4. Clear and rebuild cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Restart workers
sudo supervisorctl restart sellermind-worker:*
```

### Emergency Procedures

```bash
# Stop all workers
sudo supervisorctl stop sellermind-worker:*

# Clear all queued jobs
php artisan queue:clear

# Restart everything
sudo supervisorctl restart all
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
```

---

## Performance Optimization

### Queue Optimization

```bash
# Use Redis instead of database for better performance
# .env:
QUEUE_CONNECTION=redis

# Install predis
composer require predis/predis
```

### Database Indexing

Ensure these indexes exist:

```sql
-- Jobs table
CREATE INDEX jobs_queue_index ON jobs(queue);
CREATE INDEX jobs_reserved_at_index ON jobs(reserved_at);

-- Failed jobs table
CREATE INDEX failed_jobs_failed_at_index ON failed_jobs(failed_at);

-- Promotions
CREATE INDEX promotions_company_active_end_idx ON promotions(company_id, is_active, end_date);

-- Reviews
CREATE INDEX reviews_company_status_idx ON reviews(company_id, status);
```

### Caching Strategy

```php
// Cache expensive queries
$analytics = Cache::remember("analytics_{$companyId}", 3600, function () use ($companyId) {
    return $analyticsService->getOverview($companyId);
});

// Use Redis for session
SESSION_DRIVER=redis
```

---

## Security Checklist

- [ ] Change default passwords
- [ ] Enable firewall (UFW)
- [ ] Restrict SSH access
- [ ] Use SSL/TLS
- [ ] Set APP_DEBUG=false
- [ ] Restrict file permissions (755 for directories, 644 for files)
- [ ] Enable rate limiting
- [ ] Use secure environment variables
- [ ] Regular backups
- [ ] Monitor logs for suspicious activity

---

## Support

- **Email:** [support@sellermind.ai](mailto:support@sellermind.ai)
- **Telegram:** [@sellermind_support](https://t.me/sellermind_support)
- **Docs:** [docs.sellermind.ai](https://docs.sellermind.ai)

---

**Last Updated:** 2026-01-12
**Version:** 1.0
**Status:** Production Ready ✅
