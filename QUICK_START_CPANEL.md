# 🚀 Быстрый старт: Развертывание через Git

## Метод 1: Через Git (рекомендуется)

### Шаг 1: Подготовка репозитория

```bash
cd /Applications/MAMP/htdocs/sellermind-ai

# Соберите frontend
npm install
npm run build

# Закоммитьте изменения
git add .
git commit -m "Подготовка к деплою"
git push origin main
```

### Шаг 2: Настройка cPanel

1. **MySQL Database Wizard** → создать БД `sellermind`
2. **MultiPHP Manager** → PHP 8.2+
3. **MultiPHP INI Editor**:
   - `memory_limit = 256M`
   - `max_execution_time = 300`

### Шаг 3: Клонирование на сервере

```bash
ssh username@server.com
cd ~
git clone https://github.com/your-username/sellermind-ai.git
```

### Шаг 4: Установка

```bash
cd ~/sellermind-ai
composer install --optimize-autoloader --no-dev
cp .env.cpanel .env
nano .env  # Заполнить параметры
php artisan key:generate
php artisan migrate --force
php artisan warehouse:sync-variants
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
chmod -R 755 storage bootstrap/cache
```

### Шаг 5: Symlink

```bash
mv ~/public_html ~/public_html_backup
ln -s ~/sellermind-ai/public ~/public_html
```

### Шаг 6: Cron задачи

**cPanel** → **Cron Jobs**:

```bash
# Laravel Scheduler
* * * * * cd /home/username/sellermind-ai && /usr/bin/php artisan schedule:run >> /dev/null 2>&1

# Queue Worker
* * * * * cd /home/username/sellermind-ai && /usr/bin/php artisan queue:work --stop-when-empty --max-time=3600 >> /home/username/sellermind-ai/storage/logs/queue.log 2>&1
```

### Шаг 7: SSL

**cPanel** → **SSL/TLS Status** → **Run AutoSSL**

---

## Метод 2: Через ZIP архив

Если Git недоступен:

```bash
# Локально
bash deploy-cpanel-package.sh

# Загрузите ZIP через cPanel File Manager
# Распакуйте в /home/username/sellermind-ai
# Следуйте Шагам 4-7 выше
```

---

## Обновление проекта

```bash
ssh username@server.com
cd ~/sellermind-ai
bash update-production.sh
```

Или вручную:
```bash
git pull origin main
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan cache:clear && php artisan config:cache
```

---

## Проверка

```
✅ Сайт: https://your-domain.com
✅ БД: php artisan tinker --execute="DB::connection()->getPdo(); echo 'OK';"
✅ Логи: tail -50 ~/sellermind-ai/storage/logs/laravel.log
```

---

## Подробная документация

- [CPANEL_GIT_DEPLOYMENT.md](./CPANEL_GIT_DEPLOYMENT.md) - развертывание через Git
- [CPANEL_DEPLOYMENT_GUIDE.md](./CPANEL_DEPLOYMENT_GUIDE.md) - развертывание через ZIP

**Удачи! 🚀**
