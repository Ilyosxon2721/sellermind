# 🚀 Инструкция по развертыванию на продакшн

## 📋 Предварительные требования

### Сервер
- **OS**: Ubuntu 22.04 LTS или новее
- **PHP**: 8.2 или выше
- **MySQL**: 8.0 или выше
- **Redis**: 6.0 или выше
- **Nginx**: 1.18 или выше
- **Supervisor**: 4.2 или выше
- **RAM**: минимум 2GB, рекомендуется 4GB
- **Disk**: минимум 10GB свободного места

### PHP Расширения
```bash
php -m | grep -E "mysql|redis|mbstring|xml|curl|zip|gd|bcmath|json"
```

Должны быть установлены:
- pdo_mysql
- redis
- mbstring
- xml
- curl
- zip
- gd
- bcmath
- json
- opcache

---

## 🔧 Шаг 1: Установка зависимостей

### 1.1 Обновление системы
```bash
sudo apt update && sudo apt upgrade -y
```

### 1.2 Установка PHP 8.2
```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2-fpm php8.2-mysql php8.2-redis php8.2-mbstring \
    php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath php8.2-cli
```

### 1.3 Установка MySQL
```bash
sudo apt install -y mysql-server
sudo mysql_secure_installation
```

### 1.4 Установка Redis
```bash
sudo apt install -y redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

### 1.5 Установка Nginx
```bash
sudo apt install -y nginx
sudo systemctl enable nginx
```

### 1.6 Установка Supervisor
```bash
sudo apt install -y supervisor
sudo systemctl enable supervisor
sudo systemctl start supervisor
```

### 1.7 Установка Composer
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
```

---

## 📂 Шаг 2: Клонирование проекта

```bash
cd /var/www
sudo git clone https://your-repository.git sellermind-ai
cd sellermind-ai
sudo chown -R www-data:www-data /var/www/sellermind-ai
```

---

## ⚙️ Шаг 3: Настройка окружения

### 3.1 Копирование .env
```bash
cp .env.production .env
```

### 3.2 Редактирование .env
```bash
nano .env
```

**Обязательно заполните:**
```env
APP_KEY=  # Будет сгенерирован позже
APP_URL=https://yourdomain.com

DB_DATABASE=sellermind_ai_prod
DB_USERNAME=your_db_user
DB_PASSWORD=your_secure_password

WB_API_KEY=your_wildberries_key
OZON_CLIENT_ID=your_ozon_client_id
OZON_API_KEY=your_ozon_api_key
UZUM_API_KEY=your_uzum_key
YM_API_KEY=your_yandex_key

MAIL_HOST=smtp.yandex.ru
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your_mail_password
```

### 3.3 Генерация APP_KEY
```bash
php artisan key:generate
```

### 3.4 Создание БД
```bash
mysql -u root -p
```
```sql
CREATE DATABASE sellermind_ai_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sellermind_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON sellermind_ai_prod.* TO 'sellermind_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 🗄️ Шаг 4: Установка зависимостей и миграции

```bash
# Установка зависимостей
composer install --optimize-autoloader --no-dev

# Применение миграций
php artisan migrate --force

# Синхронизация товаров в warehouse
php artisan warehouse:sync-variants

# Кэширование конфигурации
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## 🔐 Шаг 5: Настройка прав доступа

```bash
sudo chown -R www-data:www-data /var/www/sellermind-ai
sudo chmod -R 755 /var/www/sellermind-ai/storage
sudo chmod -R 755 /var/www/sellermind-ai/bootstrap/cache
sudo chmod 644 /var/www/sellermind-ai/.env
```

---

## 🌐 Шаг 6: Настройка Nginx

### 6.1 Создание конфигурации
```bash
sudo nano /etc/nginx/sites-available/sellermind-ai
```

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;

    # Редирект на HTTPS (после установки SSL)
    # return 301 https://$server_name$request_uri;

    root /var/www/sellermind-ai/public;
    index index.php index.html;

    # Логи
    access_log /var/log/nginx/sellermind-access.log;
    error_log /var/log/nginx/sellermind-error.log;

    # Ограничение размера загружаемых файлов
    client_max_body_size 20M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Кэширование статики
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### 6.2 Активация сайта
```bash
sudo ln -s /etc/nginx/sites-available/sellermind-ai /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## 🔒 Шаг 7: Установка SSL (Let's Encrypt)

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

Certbot автоматически настроит HTTPS и добавит редирект.

---

## 👷 Шаг 8: Настройка Supervisor для Queue Workers

### 8.1 Копирование конфигурации
```bash
sudo cp supervisor-sellermind-worker.conf /etc/supervisor/conf.d/
```

### 8.2 Редактирование путей
```bash
sudo nano /etc/supervisor/conf.d/supervisor-sellermind-worker.conf
```

Замените `/path/to/sellermind-ai` на `/var/www/sellermind-ai`

### 8.3 Применение конфигурации
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start sellermind-worker:*
```

### 8.4 Проверка статуса
```bash
sudo supervisorctl status
```

Должно быть:
```
sellermind-worker:sellermind-worker_00   RUNNING
sellermind-worker:sellermind-worker_01   RUNNING
sellermind-worker:sellermind-worker_02   RUNNING
sellermind-worker:sellermind-worker_03   RUNNING
```

---

## ⏰ Шаг 9: Настройка Cron для планировщика

```bash
sudo crontab -e -u www-data
```

Добавьте:
```cron
* * * * * cd /var/www/sellermind-ai && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🔧 Шаг 10: Оптимизация PHP

### 10.1 Настройка OPcache
```bash
sudo nano /etc/php/8.2/fpm/conf.d/10-opcache.ini
```

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
opcache.enable_cli=1
```

### 10.2 Перезапуск PHP-FPM
```bash
sudo systemctl restart php8.2-fpm
```

---

## 🚀 АВТОМАТИЧЕСКИЙ ДЕПЛОЙ

После начальной настройки, для последующих обновлений используйте скрипт:

```bash
cd /var/www/sellermind-ai
sudo -u www-data bash deploy-to-production.sh
```

---

## 📊 Мониторинг

### Логи Laravel
```bash
tail -f /var/www/sellermind-ai/storage/logs/laravel.log
```

### Логи Nginx
```bash
tail -f /var/log/nginx/sellermind-error.log
```

### Логи Queue Workers
```bash
tail -f /var/www/sellermind-ai/storage/logs/worker.log
```

### Статус Redis
```bash
redis-cli ping  # Должно вернуть PONG
redis-cli info stats
```

### Статус MySQL
```bash
sudo systemctl status mysql
```

---

## 🔧 Полезные команды

### Очистка кэша
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Перезапуск workers
```bash
sudo supervisorctl restart sellermind-worker:*
```

### Проверка очередей
```bash
php artisan queue:work redis --once
```

### Бэкап БД
```bash
mysqldump -u sellermind_user -p sellermind_ai_prod > backup_$(date +%Y%m%d).sql
```

---

## ⚠️ Troubleshooting

### Проблема: 500 Internal Server Error
**Решение:**
1. Проверьте логи: `tail -f storage/logs/laravel.log`
2. Проверьте права: `sudo chmod -R 755 storage bootstrap/cache`
3. Очистите кэш: `php artisan cache:clear && php artisan config:clear`

### Проблема: Queue не обрабатывается
**Решение:**
```bash
sudo supervisorctl status
sudo supervisorctl restart sellermind-worker:*
tail -f storage/logs/worker.log
```

### Проблема: Redis connection refused
**Решение:**
```bash
sudo systemctl status redis-server
sudo systemctl restart redis-server
redis-cli ping
```

---

## 📞 Поддержка

При возникновении проблем:
1. Проверьте все логи
2. Убедитесь что все сервисы запущены
3. Проверьте .env конфигурацию
4. Обратитесь к документации Laravel: https://laravel.com/docs

---

✅ **После успешного развертывания проект будет доступен по адресу: https://yourdomain.com**
