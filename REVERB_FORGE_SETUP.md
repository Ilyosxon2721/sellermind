# Laravel Reverb Setup for Laravel Forge

Руководство по настройке Laravel Reverb WebSocket сервера на Laravel Forge.

---

## 📋 Содержание

1. [Предварительные требования](#предварительные-требования)
2. [Настройка в Laravel Forge](#настройка-в-laravel-forge)
3. [Конфигурация .env](#конфигурация-env)
4. [Настройка Nginx](#настройка-nginx)
5. [SSL сертификат](#ssl-сертификат)
6. [Проверка работы](#проверка-работы)
7. [Troubleshooting](#troubleshooting)
8. [Альтернативная настройка (без Forge)](#альтернативная-настройка-без-forge)

---

## ✅ Предварительные требования

- ✅ VPS сервер (не shared hosting!)
- ✅ Laravel Forge аккаунт
- ✅ Домен с настроенным DNS
- ✅ SSL сертификат (Let's Encrypt)

**Важно:** Reverb НЕ работает на shared hosting (cPanel). Требуется VPS!

---

## 🚀 Настройка в Laravel Forge

### Шаг 1: Создание Daemon для Reverb

1. **Откройте ваш сайт в Forge**
2. **Перейдите в раздел "Daemons"**
3. **Нажмите "New Daemon"**

**Конфигурация Daemon:**

| Поле | Значение |
|------|----------|
| **Command** | `php /home/forge/your-site.com/artisan reverb:start` |
| **User** | `forge` |
| **Directory** | `/home/forge/your-site.com` |
| **Processes** | `1` |
| **Startsecs** | `1` |

**Скриншот конфигурации:**
```
Command: php /home/forge/your-site.com/artisan reverb:start
User: forge
Directory: /home/forge/your-site.com
Processes: 1
Startsecs: 1
```

4. **Нажмите "Create Daemon"**

Forge автоматически создаст supervisor конфигурацию:
```ini
[program:daemon-123456]
command=php /home/forge/your-site.com/artisan reverb:start
directory=/home/forge/your-site.com
redirect_stderr=true
stdout_logfile=/home/forge/.forge/daemon-123456.log
autostart=true
autorestart=true
user=forge
startsecs=1
```

### Шаг 2: Создание Daemon для Queue Worker

Также создайте daemon для queue worker:

| Поле | Значение |
|------|----------|
| **Command** | `php /home/forge/your-site.com/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600` |
| **User** | `forge` |
| **Directory** | `/home/forge/your-site.com` |
| **Processes** | `2` |

---

## ⚙️ Конфигурация .env

### Production .env настройки

Обновите `.env` файл на сервере:

```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Broadcasting
BROADCAST_CONNECTION=reverb
BROADCAST_DRIVER=pusher

# Reverb Configuration
REVERB_APP_ID=sellermind
REVERB_APP_KEY=your-production-app-key-here
REVERB_APP_SECRET=your-production-app-secret-here
REVERB_HOST=your-domain.com
REVERB_PORT=443
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
REVERB_SCHEME=https

# Pusher (используется для Reverb client)
PUSHER_APP_ID="${REVERB_APP_ID}"
PUSHER_APP_KEY="${REVERB_APP_KEY}"
PUSHER_APP_SECRET="${REVERB_APP_SECRET}"
PUSHER_HOST="${REVERB_HOST}"
PUSHER_PORT="${REVERB_PORT}"
PUSHER_SCHEME="${REVERB_SCHEME}"
PUSHER_APP_CLUSTER=mt1

# Vite (для frontend)
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

# Queue
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Генерация ключей

**Для безопасности используйте уникальные ключи:**

```bash
# SSH в сервер
ssh forge@your-server-ip

cd /home/forge/your-site.com

# Сгенерировать случайные ключи
php -r "echo base64_encode(random_bytes(32)) . PHP_EOL;"  # REVERB_APP_KEY
php -r "echo base64_encode(random_bytes(32)) . PHP_EOL;"  # REVERB_APP_SECRET
```

Обновите эти значения в `.env` через Forge:
1. Site → Environment → Edit
2. Обновите `REVERB_APP_KEY` и `REVERB_APP_SECRET`
3. Save

---

## 🔧 Настройка Nginx

### Добавление WebSocket proxy

Reverb работает на порту 8080 внутри сервера. Nginx должен проксировать WebSocket запросы.

**В Laravel Forge:**

1. **Перейдите в "Sites" → Ваш сайт**
2. **Нажмите "Files" → "Edit Nginx Configuration"**
3. **Добавьте WebSocket location ПЕРЕД блоком `location /`:**

```nginx
# WebSocket для Reverb
location /app {
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_set_header X-Forwarded-Host $host;
    proxy_set_header X-Forwarded-Port $server_port;

    proxy_pass http://127.0.0.1:8080;

    proxy_connect_timeout 7d;
    proxy_send_timeout 7d;
    proxy_read_timeout 7d;
}
```

**Полная конфигурация должна выглядеть так:**

```nginx
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com;

    # SSL certificates
    ssl_certificate /etc/nginx/ssl/your-domain.com/123456/server.crt;
    ssl_certificate_key /etc/nginx/ssl/your-domain.com/123456/server.key;

    # ... остальные SSL настройки ...

    root /home/forge/your-site.com/public;

    # WebSocket для Reverb - ДОЛЖЕН БЫТЬ ПЕРЕД location /
    location /app {
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        proxy_pass http://127.0.0.1:8080;

        proxy_connect_timeout 7d;
        proxy_send_timeout 7d;
        proxy_read_timeout 7d;
    }

    # Остальные location блоки
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # ... остальная конфигурация ...
}
```

4. **Сохраните изменения**
5. **Перезапустите Nginx:**

```bash
sudo service nginx restart
```

---

## 🔒 SSL сертификат

### Let's Encrypt через Forge

1. **Убедитесь, что SSL сертификат установлен:**
   - Sites → Ваш сайт → SSL
   - Если нет - нажмите "LetsEncrypt" и создайте сертификат

2. **Проверьте, что HTTPS работает:**
   ```bash
   curl -I https://your-domain.com
   # Должен вернуть 200 OK
   ```

---

## ✅ Проверка работы

### 1. Проверка Daemon статуса

**В Forge:**
- Перейдите в "Daemons"
- Статус должен быть **"Running"** (зеленый)

**Или через SSH:**
```bash
ssh forge@your-server-ip
sudo supervisorctl status
```

Должно показать:
```
daemon-123456    RUNNING   pid 12345, uptime 0:05:23
```

### 2. Проверка логов Reverb

```bash
# SSH в сервер
ssh forge@your-server-ip

# Посмотреть логи Reverb daemon
tail -f /home/forge/.forge/daemon-123456.log
```

**Ожидаемый вывод:**
```
  INFO Server running...
  Local: http://0.0.0.0:8080
  Application key: sellermind

  INFO Listening for connections...
```

### 3. Проверка WebSocket подключения

**Через браузер:**

1. Откройте https://your-domain.com
2. Откройте DevTools → Console
3. Не должно быть ошибок WebSocket

**Ожидаемое поведение:**
```
WebSocket connection to 'wss://your-domain.com/app/your-key?...' established
```

**Или через curl:**
```bash
curl -I https://your-domain.com/app
```

Должен вернуть upgrade response.

### 4. Тестовое broadcasting событие

**SSH в сервер:**
```bash
php artisan tinker
```

```php
// Отправить тестовое событие
broadcast(new \App\Events\MarketplaceDataChanged(1, 'test', []));
```

Проверьте логи:
```bash
tail -f /home/forge/.forge/daemon-123456.log
```

Должны появиться сообщения о broadcasting.

---

## 🔧 Troubleshooting

### Проблема 1: Daemon не запускается

**Проверка:**
```bash
ssh forge@your-server-ip
sudo supervisorctl status daemon-123456
```

**Если показывает FATAL:**
```bash
# Посмотрите логи
cat /home/forge/.forge/daemon-123456.log

# Попробуйте запустить вручную
cd /home/forge/your-site.com
php artisan reverb:start --debug
```

**Решение:**
```bash
# Перезапустить daemon через Forge или:
sudo supervisorctl restart daemon-123456
```

### Проблема 2: WebSocket connection failed

**Симптомы:**
```
WebSocket connection to 'wss://...' failed: Error during WebSocket handshake
```

**Причины:**
1. Nginx конфигурация неправильная
2. SSL сертификат не настроен
3. Reverb daemon не запущен

**Решение:**

1. **Проверьте Nginx config:**
```bash
sudo nginx -t
```

2. **Убедитесь, что location /app ПЕРЕД location /**

3. **Перезапустите Nginx:**
```bash
sudo service nginx restart
```

4. **Проверьте Reverb daemon:**
```bash
sudo supervisorctl status | grep daemon
```

### Проблема 3: 502 Bad Gateway на /app

**Причина:** Reverb daemon не запущен или не слушает порт 8080

**Решение:**
```bash
# Проверить порт
sudo netstat -tlnp | grep 8080

# Если пусто - Reverb не запущен
sudo supervisorctl restart daemon-123456

# Проверить логи
tail -50 /home/forge/.forge/daemon-123456.log
```

### Проблема 4: REVERB_APP_KEY not set

**Симптомы:**
```
Application key is missing
```

**Решение:**

1. Проверьте `.env`:
```bash
cat /home/forge/your-site.com/.env | grep REVERB
```

2. Если пусто - добавьте ключи:
```bash
# Сгенерируйте
php -r "echo base64_encode(random_bytes(32)) . PHP_EOL;"
```

3. Обновите через Forge Environment или вручную

4. Перезапустите daemon:
```bash
sudo supervisorctl restart daemon-123456
```

### Проблема 5: Работает локально, но не на production

**Checklist:**

- [ ] `.env` настроен правильно (REVERB_SCHEME=https, REVERB_PORT=443)
- [ ] Nginx конфигурация обновлена
- [ ] SSL сертификат установлен
- [ ] Daemon запущен
- [ ] Порт 8080 открыт локально (но НЕ снаружи - только Nginx proxy)
- [ ] Assets пересобраны: `npm run build`

**Команды для проверки:**
```bash
# 1. Проверить .env
cat .env | grep -E 'REVERB|BROADCAST'

# 2. Проверить daemon
sudo supervisorctl status

# 3. Проверить Nginx
sudo nginx -t

# 4. Перезапустить все
sudo supervisorctl restart all
sudo service nginx restart

# 5. Очистить кеш
php artisan config:clear
php artisan cache:clear
```

---

## 🛠️ Альтернативная настройка (без Forge)

Если вы НЕ используете Forge, настройте вручную:

### Supervisor конфигурация

Создайте файл `/etc/supervisor/conf.d/reverb.conf`:

```ini
[program:reverb]
process_name=%(program_name)s
command=php /var/www/your-site.com/artisan reverb:start
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/your-site.com/storage/logs/reverb.log
stopwaitsecs=3600
```

**Запуск:**
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start reverb
```

### Systemd альтернатива

Создайте `/etc/systemd/system/reverb.service`:

```ini
[Unit]
Description=Laravel Reverb WebSocket Server
After=network.target mysql.service redis.service

[Service]
Type=simple
User=www-data
Group=www-data
Restart=always
RestartSec=5s
ExecStart=/usr/bin/php /var/www/your-site.com/artisan reverb:start

StandardOutput=append:/var/www/your-site.com/storage/logs/reverb.log
StandardError=append:/var/www/your-site.com/storage/logs/reverb-error.log

PrivateTmp=true
NoNewPrivileges=true

[Install]
WantedBy=multi-user.target
```

**Запуск:**
```bash
sudo systemctl enable reverb
sudo systemctl start reverb
sudo systemctl status reverb
```

---

## 📊 Мониторинг

### Forge Dashboard

В Forge вы можете видеть:
- Статус daemon (зеленый = работает)
- Автоматические перезапуски
- Быстрый рестарт через UI

### Логи

```bash
# Reverb logs
tail -f /home/forge/.forge/daemon-XXXXX.log

# Laravel logs
tail -f /home/forge/your-site.com/storage/logs/laravel.log

# Nginx logs
tail -f /var/log/nginx/your-site.com-error.log
```

### Health Check

Добавьте в `routes/api.php`:

```php
Route::get('health/reverb', function () {
    try {
        $key = config('reverb.apps.apps.0.key');
        $port = config('reverb.servers.reverb.port');

        return response()->json([
            'status' => 'configured',
            'app_key' => substr($key, 0, 8) . '***',
            'internal_port' => $port,
            'public_url' => config('app.url') . '/app',
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});
```

**Проверка:**
```bash
curl https://your-domain.com/api/health/reverb
```

---

## 🚀 После деплоя

**ВАЖНО:** После каждого деплоя перезапускайте Reverb daemon!

**Через Forge:**
1. Перейдите в Daemons
2. Нажмите "Restart" на Reverb daemon

**Или через SSH:**
```bash
sudo supervisorctl restart daemon-XXXXX
```

**Добавьте в deploy script:**
```bash
cd /home/forge/your-site.com

# Pull code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# Migrations
php artisan migrate --force

# Clear cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Restart workers and Reverb
sudo supervisorctl restart all
```

---

## ✅ Checklist готовности

- [ ] VPS сервер (не shared hosting)
- [ ] SSL сертификат установлен
- [ ] `.env` настроен (REVERB_* переменные)
- [ ] Daemon создан в Forge
- [ ] Nginx конфигурация обновлена (location /app)
- [ ] Nginx перезапущен
- [ ] Daemon статус = Running
- [ ] WebSocket подключение работает (проверка в браузере)
- [ ] Queue worker тоже настроен
- [ ] Логи не показывают ошибок

---

## 📚 Дополнительные ресурсы

- [Laravel Reverb Documentation](https://laravel.com/docs/reverb)
- [Laravel Broadcasting](https://laravel.com/docs/broadcasting)
- [Laravel Forge Daemons](https://forge.laravel.com/docs/servers/daemons.html)
- [Nginx WebSocket Proxy](https://nginx.org/en/docs/http/websocket.html)

---

**Последнее обновление:** Январь 2026
**Версия:** 1.0.0
