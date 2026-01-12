# 🔥 Laravel Forge Deployment Guide

**SellerMind AI - Deploy with Laravel Forge (5 minutes setup)**

Laravel Forge автоматизирует весь процесс деплоя и управления сервером.

---

## 🎯 Преимущества Forge

✅ **Автоматическая настройка сервера** - Nginx, PHP, MySQL, Redis
✅ **One-click деплой** - Push to GitHub → Auto-deploy
✅ **SSL сертификаты** - Автоматически через Let's Encrypt
✅ **Queue Workers** - Автоматическая настройка Supervisor
✅ **Cron Jobs** - Простое управление через UI
✅ **Zero-downtime deploys** - Без остановки сервера
✅ **Monitoring** - Встроенный мониторинг

**Стоимость:** $12-19/месяц + стоимость сервера (~$5-10/месяц)

---

## 📋 Prerequisites

1. **Laravel Forge аккаунт** - [forge.laravel.com](https://forge.laravel.com)
2. **VPS сервер** (один из):
   - DigitalOcean (рекомендуется)
   - AWS
   - Linode
   - Vultr
   - Hetzner
3. **GitHub/GitLab/Bitbucket репозиторий** с кодом SellerMind
4. **Domain name** с доступом к DNS

---

## 🚀 Step-by-Step Deployment

### Step 1: Create Server in Forge (2 minutes)

1. Зайди на [forge.laravel.com](https://forge.laravel.com)
2. Нажми **"Create Server"**
3. Выбери параметры:

```
Server Provider: DigitalOcean (или другой)
Server Name: sellermind-production
Region: Choose closest to your users (Frankfurt, Amsterdam, etc.)
Server Size:
  - Basic Plan: $6/month (1GB RAM) - для старта
  - Business Plan: $12/month (2GB RAM) - рекомендуется
  - Hobby Plan: $18/month (4GB RAM) - для роста

Server Type: App Server

PHP Version: PHP 8.2

Database: MySQL 8.0 (✓ Enable)
Database Name: sellermind_prod
```

4. Нажми **"Create Server"**
5. Подожди 5-10 минут пока Forge настроит сервер

**Forge автоматически установит:**
- ✅ Nginx
- ✅ PHP 8.2 with all extensions
- ✅ MySQL 8.0
- ✅ Redis
- ✅ Supervisor
- ✅ Node.js
- ✅ Composer

---

### Step 2: Create Site (1 minute)

После создания сервера:

1. На странице сервера нажми **"New Site"**
2. Заполни:

```
Root Domain: your-domain.com
Aliases: www.your-domain.com (optional)
Project Type: General PHP / Laravel
Web Directory: /public
PHP Version: PHP 8.2
```

3. Нажми **"Add Site"**

---

### Step 3: Install Repository (1 minute)

1. На странице сайта найди секцию **"Apps"**
2. Нажми **"Install Repository"**
3. Выбери провайдера (GitHub)
4. Авторизуйся с GitHub
5. Заполни:

```
Repository: Ilyosxon2721/sellermind
Branch: claude/review-production-readiness-LSoNy (или main после merge)
Install Composer Dependencies: ✓ Yes
```

6. Нажми **"Install Repository"**

Forge склонирует репозиторий и запустит `composer install`

---

### Step 4: Environment Variables (2 minutes)

1. На странице сайта перейди в **"Environment"**
2. Forge уже создал базовый `.env` файл
3. Обнови следующие переменные:

```env
APP_NAME=SellerMind
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database (уже настроено Forge, проверь)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=sellermind_prod
DB_USERNAME=forge
DB_PASSWORD=<forge_generated_password>

# Cache & Queue
QUEUE_CONNECTION=database
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Telegram Bot
TELEGRAM_BOT_TOKEN=your_bot_token_here

# AI Service (если используется)
AI_SERVICE_API_KEY=your_api_key_here

# Marketplace APIs
WB_API_KEY=your_wildberries_key
OZON_CLIENT_ID=your_ozon_client_id
OZON_API_KEY=your_ozon_api_key
YANDEX_MARKET_TOKEN=your_yandex_token
```

4. Нажми **"Save"**

---

### Step 5: SSL Certificate (30 seconds)

1. На странице сайта перейди в **"SSL"**
2. Выбери **"LetsEncrypt"**
3. Заполни:

```
Domains: your-domain.com,www.your-domain.com
Email: your@email.com
```

4. Нажми **"Obtain Certificate"**

Forge автоматически получит SSL сертификат и настроит HTTPS!

---

### Step 6: Deploy Script (1 minute)

Forge уже создал базовый deploy script. Нужно его обновить для Quick Wins.

1. Перейди в **"Deployments"** → **"Deploy Script"**
2. Замени содержимое на:

```bash
cd /home/forge/your-domain.com

# Enable maintenance mode
php artisan down || true

# Pull latest changes
git pull origin $FORGE_SITE_BRANCH

# Install dependencies
$FORGE_COMPOSER install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Clear and rebuild cache
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate --force

# Seed review templates (only runs if not already seeded)
php artisan db:seed --class=ReviewTemplatesSeeder --force || true

# Restart queue workers
php artisan queue:restart

# Disable maintenance mode
php artisan up
```

3. Нажми **"Update"**

---

### Step 7: Queue Workers (1 minute)

1. Перейди в **"Queue Workers"**
2. Нажми **"New Worker"**

#### Worker 1: Default Queue

```
Connection: database
Queue: default
Processes: 4
Timeout: 300
Sleep: 3
Max Tries: 3
```

Нажми **"Create"**

#### Worker 2: High Priority Queue

```
Connection: database
Queue: high,default
Processes: 2
Timeout: 120
Sleep: 2
Max Tries: 3
```

Нажми **"Create"**

Forge автоматически настроит Supervisor!

---

### Step 8: Scheduler (30 seconds)

1. Перейди в **"Scheduler"**
2. **Проверь что включен** - должна быть галочка ✓

Forge автоматически добавил Laravel Scheduler в cron:
```
* * * * * php /home/forge/your-domain.com/artisan schedule:run
```

**Всё готово!** Теперь работают:
- ✅ Auto Promotions (Monday 9am)
- ✅ Expiring Notifications (Daily 10am)
- ✅ Analytics Caching (Hourly)
- ✅ Marketplace Sync (Every 10 min)

---

### Step 9: Initial Deploy

1. Перейди в **"Deployments"**
2. Нажми **"Deploy Now"**

Forge выполнит deploy script и запустит приложение!

---

### Step 10: Verify Deployment

1. Открой `https://your-domain.com` в браузере
2. Проверь:
   - ✅ Страница загружается с HTTPS
   - ✅ Логин работает
   - ✅ Dashboard отображается
   - ✅ Все Quick Wins доступны

3. Проверь Queue Workers:
   - В Forge перейди в **"Queue Workers"**
   - Оба worker'а должны показывать статус **"Running"**

4. Проверь Scheduler:
   - SSH на сервер: `ssh forge@your-server-ip`
   - Запусти: `php /home/forge/your-domain.com/artisan schedule:list`
   - Должны быть видны все задачи

---

## 🎉 Готово! Quick Wins активированы!

### Что теперь работает автоматически:

✅ **Каждый понедельник в 9:00:**
- Создание промо для неликвидного товара

✅ **Каждый день в 10:00:**
- Уведомления об истекающих акциях

✅ **Каждый час:**
- Обновление кэша аналитики

✅ **Каждые 10 минут:**
- Синхронизация заказов с маркетплейсов

---

## 🔄 Auto-Deployment

Настрой автоматический деплой при push в GitHub:

1. В Forge перейди в **"Deployments"**
2. Включи **"Quick Deploy"**
3. Выбери ветку: `claude/review-production-readiness-LSoNy` или `main`

Теперь при каждом push в GitHub:
1. Forge автоматически задеплоит изменения
2. Запустит миграции
3. Перезапустит queue workers
4. Zero-downtime!

---

## 📊 Monitoring через Forge

### 1. Server Monitoring

Forge автоматически мониторит:
- CPU usage
- Memory usage
- Disk space
- Load average

Алерты придут на email если что-то не так.

### 2. Logs

Смотри логи прямо в Forge UI:
- **Server Logs:** CPU, Memory, Disk
- **Application Logs:** Laravel logs
- **Queue Worker Logs:** Background jobs

---

## 🔧 Useful Forge Features

### Database Management

1. **Backups:**
   - Перейди в **"Backups"**
   - Настрой автоматические бэкапы на S3/DigitalOcean Spaces
   - Frequency: Daily

2. **phpMyAdmin:**
   - Установи в **"Server"** → **"Database"**
   - Доступ через: `your-server-ip:8080/phpmyadmin`

### SSH Access

```bash
# SSH ключ уже настроен Forge
ssh forge@your-server-ip

# Переход в директорию проекта
cd /home/forge/your-domain.com

# Артисан команды
php artisan queue:failed
php artisan schedule:list
php artisan cache:clear
```

### Deploy Keys

Forge автоматически настроил SSH ключ для доступа к GitHub.

---

## 🚨 Troubleshooting

### Queue Workers не запускаются

1. В Forge перейди в **"Queue Workers"**
2. Нажми **"Restart"** на каждом worker'е
3. Проверь логи: **"Logs"** → **"Queue Worker Logs"**

### Scheduler не работает

```bash
ssh forge@your-server-ip
crontab -l  # Проверь что есть Laravel Scheduler
php /home/forge/your-domain.com/artisan schedule:run  # Запусти вручную
```

### SSL не работает

1. Убедись что DNS настроен правильно (A record → server IP)
2. Подожди распространения DNS (до 24 часов)
3. В Forge **"SSL"** → **"Obtain Certificate"** снова

### Deploy падает с ошибкой

1. Проверь **"Deployment Log"** в Forge
2. Чаще всего проблемы:
   - Composer dependencies
   - Migrations error
   - Permissions

**Fix permissions:**
```bash
ssh forge@your-server-ip
cd /home/forge/your-domain.com
sudo chown -R forge:forge .
chmod -R 775 storage bootstrap/cache
```

---

## 💰 Costs

**Total Monthly Cost:**

| Item | Cost |
|------|------|
| Laravel Forge | $12-19/month |
| DigitalOcean Droplet (2GB) | $12/month |
| **Total** | **~$24-31/month** |

**Альтернатива ручному деплою:**
- ✅ Экономия времени: 2-3 часа каждый раз
- ✅ Меньше ошибок
- ✅ Автоматические обновления
- ✅ Встроенный мониторинг

---

## 🎯 Quick Commands via Forge

### Deploy

1. Перейди на страницу сайта
2. Нажми **"Deploy Now"**

### Restart Queue Workers

1. **"Queue Workers"** → **"Restart"**

### Run Artisan Command

1. **"Terminal"** → **"Commands"**
2. Введи команду: `php artisan migrate`
3. Нажми **"Run"**

### View Logs

1. **"Logs"** → Выбери тип лога
2. Real-time просмотр

---

## 🔐 Security

Forge автоматически настраивает:
- ✅ UFW Firewall (ports 80, 443, 22)
- ✅ SSH key-based authentication
- ✅ SSL/TLS certificates
- ✅ Proper file permissions
- ✅ Isolated environment per site

---

## 📚 Forge Documentation

- **Official Docs:** [forge.laravel.com/docs](https://forge.laravel.com/docs)
- **Server Management:** Backups, Monitoring, Security
- **Site Management:** Deployments, SSL, Queues
- **Laravel Scheduler:** Automatic cron setup

---

## ✅ Forge Setup Checklist

After setup, verify:

- [ ] Server created and provisioned
- [ ] Site created with correct domain
- [ ] Repository installed from GitHub
- [ ] Environment variables set
- [ ] SSL certificate obtained and active
- [ ] Queue workers running (2 workers, 6 processes total)
- [ ] Scheduler enabled
- [ ] Initial deploy successful
- [ ] HTTPS website loading
- [ ] All Quick Wins accessible
- [ ] Database migrated and seeded
- [ ] Auto-deploy enabled

---

## 🎉 Summary

**С Laravel Forge деплой занимает всего 5-10 минут!**

**Что получаешь:**
- ✅ Автоматическая настройка сервера
- ✅ One-click deploy
- ✅ SSL из коробки
- ✅ Queue workers автоматически
- ✅ Scheduler автоматически
- ✅ Monitoring встроенный
- ✅ Zero-downtime deploys
- ✅ Backups и безопасность

**VS ручной деплой:**
- ⏱ Экономия времени: 90%
- 🐛 Меньше ошибок: 100%
- 🔄 Автоматизация: Полная
- 💰 Стоимость: $24-31/месяц (окупается за 1 час работы)

---

## 🚀 Start Now

1. Зарегистрируйся на [forge.laravel.com](https://forge.laravel.com)
2. Подключи DigitalOcean или другой провайдер
3. Следуй этому гайду
4. Деплой за 10 минут!

**Need help?** Check [Forge Documentation](https://forge.laravel.com/docs) or Laravel community.

---

**Made with ❤️ by Laravel Forge**
