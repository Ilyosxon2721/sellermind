# Queue Worker Configuration for Production

Руководство по настройке и управлению очередями задач в SellerMind AI для production окружения.

---

## 📋 Содержание

1. [Обзор системы очередей](#обзор-системы-очередей)
2. [Выбор драйвера](#выбор-драйвера)
3. [Настройка для разных окружений](#настройка-для-разных-окружений)
4. [Production Setup - Supervisor](#production-setup---supervisor)
5. [Production Setup - Systemd](#production-setup---systemd)
6. [Масштабирование](#масштабирование)
7. [Мониторинг](#мониторинг)
8. [Troubleshooting](#troubleshooting)
9. [Best Practices](#best-practices)

---

## 🔍 Обзор системы очередей

SellerMind AI использует очереди для асинхронной обработки задач:

### Активные Jobs (7 штук)

| Job | Описание | Среднее время | Приоритет |
|-----|----------|---------------|-----------|
| `SyncNewWildberriesOrdersJob` | Синхронизация новых заказов WB | 5-30 сек | Высокий |
| `UpdateWildberriesOrdersStatusJob` | Обновление статусов заказов WB | 3-15 сек | Высокий |
| `SyncWildberriesSupplies` | Синхронизация поставок WB | 10-60 сек | Средний |
| `SyncUzumOrders` | Синхронизация заказов Uzum | 5-20 сек | Высокий |
| `ProcessGenerationTaskJob` | AI генерация контента | 10-120 сек | Низкий |
| `RunAgentTaskRunJob` | Запуск AI агентов | 5-300 сек | Низкий |
| `ContinueAgentRunJob` | Продолжение работы агентов | 5-60 сек | Низкий |

### Конфигурация

**Путь:** `config/queue.php`

**Default драйвер:** `database` (fallback: `redis` для VPS)

---

## 🎯 Выбор драйвера

### Database Driver

**Когда использовать:**
- ✅ Shared hosting (cPanel)
- ✅ Простая установка
- ✅ Низкая/средняя нагрузка (< 100 jobs/минуту)
- ✅ Один worker

**Преимущества:**
- Не требует дополнительных сервисов
- Все в одной базе данных
- Простой backup и восстановление

**Недостатки:**
- Медленнее Redis
- Дополнительная нагрузка на MySQL
- Не подходит для высоких нагрузок

### Redis Driver

**Когда использовать:**
- ✅ VPS/Dedicated сервер
- ✅ Высокая нагрузка (> 100 jobs/минуту)
- ✅ Несколько workers
- ✅ Требуется высокая скорость

**Преимущества:**
- Очень быстрый
- Отлично масштабируется
- Меньше нагрузка на MySQL
- Поддержка приоритетов

**Недостатки:**
- Требует Redis сервер
- Дополнительная настройка

---

## ⚙️ Настройка для разных окружений

### 1. Database Queue (cPanel, Shared Hosting)

#### Шаг 1: Настройка .env

```env
# Queue Configuration
QUEUE_CONNECTION=database

# Optional: Retry timeout (seconds)
DB_QUEUE_RETRY_AFTER=600
```

#### Шаг 2: Проверка миграций

```bash
php artisan migrate:status | grep jobs
```

Должны быть выполнены:
- `create_jobs_table`
- `create_job_batches_table`
- `create_failed_jobs_table`

#### Шаг 3: Настройка Cron Job

Добавьте в cPanel Cron Jobs:

**Частота:** Каждую минуту (`* * * * *`)

**Команда:**
```bash
cd /home/username/sellermind && /usr/bin/php artisan queue:work database --stop-when-empty --max-time=3600 >> /home/username/sellermind/storage/logs/queue.log 2>&1
```

**Объяснение параметров:**
- `--stop-when-empty` - остановится когда очередь пуста
- `--max-time=3600` - максимум 1 час работы (для перезагрузки)
- `>> storage/logs/queue.log` - лог выполнения

### 2. Redis Queue (VPS, Production)

#### Шаг 1: Установка Redis

**Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

**CentOS/RHEL:**
```bash
sudo yum install redis
sudo systemctl enable redis
sudo systemctl start redis
```

#### Шаг 2: Настройка Redis

Отредактируйте `/etc/redis/redis.conf`:

```conf
# Bind to localhost (безопасность)
bind 127.0.0.1

# Требовать пароль
requirepass your_secure_password_here

# Настройка памяти
maxmemory 256mb
maxmemory-policy allkeys-lru

# Persistence
save 900 1
save 300 10
save 60 10000
```

Перезапустите Redis:
```bash
sudo systemctl restart redis
```

#### Шаг 3: Настройка .env

```env
# Queue Configuration
QUEUE_CONNECTION=redis

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=your_secure_password_here
REDIS_PORT=6379
REDIS_QUEUE_CONNECTION=default
REDIS_QUEUE=default
REDIS_QUEUE_RETRY_AFTER=90
```

#### Шаг 4: Проверка подключения

```bash
php artisan tinker
>>> Redis::connection()->ping();
# Должно вернуть: "+PONG"
```

---

## 🚀 Production Setup - Supervisor

**Рекомендуется для VPS/Dedicated серверов**

Supervisor автоматически перезапускает worker при сбоях.

### Установка Supervisor

**Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install supervisor
sudo systemctl enable supervisor
sudo systemctl start supervisor
```

**CentOS/RHEL:**
```bash
sudo yum install supervisor
sudo systemctl enable supervisord
sudo systemctl start supervisord
```

### Конфигурация

Создайте файл `/etc/supervisor/conf.d/sellermind-queue.conf`:

```ini
[program:sellermind-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/sellermind/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=300
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/sellermind/storage/logs/worker.log
stopwaitsecs=3600
```

### Параметры команды

| Параметр | Значение | Описание |
|----------|----------|----------|
| `--sleep=3` | 3 секунды | Пауза между проверкой очереди |
| `--tries=3` | 3 попытки | Количество попыток выполнения |
| `--max-time=3600` | 1 час | Максимальное время работы worker |
| `--timeout=300` | 5 минут | Таймаут для одной задачи |

### Управление Supervisor

```bash
# Перечитать конфигурацию
sudo supervisorctl reread

# Обновить программы
sudo supervisorctl update

# Запустить worker
sudo supervisorctl start sellermind-queue-worker:*

# Остановить worker
sudo supervisorctl stop sellermind-queue-worker:*

# Перезапустить worker
sudo supervisorctl restart sellermind-queue-worker:*

# Статус
sudo supervisorctl status sellermind-queue-worker:*

# Посмотреть логи
sudo supervisorctl tail -f sellermind-queue-worker:sellermind-queue-worker_00 stdout
```

### После обновления кода

**ВАЖНО:** После деплоя нового кода необходимо перезапустить workers:

```bash
sudo supervisorctl restart sellermind-queue-worker:*
```

Или добавьте в скрипт деплоя:
```bash
# deploy.sh
php artisan down
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo supervisorctl restart sellermind-queue-worker:*
php artisan up
```

---

## 🔧 Production Setup - Systemd

**Альтернатива Supervisor для современных Linux систем**

### Создание systemd service

Создайте файл `/etc/systemd/system/sellermind-queue@.service`:

```ini
[Unit]
Description=SellerMind Queue Worker %i
After=network.target mysql.service redis.service

[Service]
Type=simple
User=www-data
Group=www-data
Restart=always
RestartSec=5s
ExecStart=/usr/bin/php /var/www/sellermind/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=300

# Логи
StandardOutput=append:/var/www/sellermind/storage/logs/queue-%i.log
StandardError=append:/var/www/sellermind/storage/logs/queue-%i-error.log

# Безопасность
PrivateTmp=true
NoNewPrivileges=true

[Install]
WantedBy=multi-user.target
```

### Управление

```bash
# Включить автозапуск
sudo systemctl enable sellermind-queue@{1..2}

# Запустить 2 worker'а
sudo systemctl start sellermind-queue@1
sudo systemctl start sellermind-queue@2

# Статус
sudo systemctl status sellermind-queue@1

# Перезапуск всех workers
sudo systemctl restart 'sellermind-queue@*'

# Остановка
sudo systemctl stop 'sellermind-queue@*'

# Логи
sudo journalctl -u sellermind-queue@1 -f
```

---

## 📊 Масштабирование

### Определение количества workers

**Формула:**
```
Количество workers = (Средний поток задач × Среднее время выполнения) / 60
```

**Пример:**
- 300 задач в час = 5 задач в минуту
- Среднее время: 15 секунд
- Нужно: (5 × 15) / 60 = 1.25 ≈ **2 workers**

### Приоритеты очередей

Для обработки важных задач быстрее используйте приоритеты:

```bash
# High priority: orders sync
php artisan queue:work redis --queue=high,default --tries=3

# Low priority: AI tasks
php artisan queue:work redis --queue=low --tries=3
```

**В коде:**
```php
// Высокий приоритет
SyncNewWildberriesOrdersJob::dispatch($account)->onQueue('high');

// Обычный приоритет
ProcessGenerationTaskJob::dispatch($task);

// Низкий приоритет
RunAgentTaskRunJob::dispatch($run)->onQueue('low');
```

### Supervisor конфигурация для приоритетов

```ini
# High priority workers (2 штуки)
[program:sellermind-queue-high]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/sellermind/artisan queue:work redis --queue=high,default --sleep=1 --tries=3 --max-time=3600
numprocs=2
priority=999

# Default workers (2 штуки)
[program:sellermind-queue-default]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/sellermind/artisan queue:work redis --queue=default --sleep=3 --tries=3 --max-time=3600
numprocs=2
priority=500

# Low priority workers (1 штука)
[program:sellermind-queue-low]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/sellermind/artisan queue:work redis --queue=low --sleep=5 --tries=3 --max-time=3600
numprocs=1
priority=100
```

---

## 📈 Мониторинг

### 1. Проверка состояния очереди

```bash
# Количество задач в очереди (database)
php artisan tinker
>>> DB::table('jobs')->count();

# Количество задач в очереди (redis)
redis-cli
> LLEN queues:default

# Проваленные задачи
php artisan queue:failed
```

### 2. Логирование

**Storage logs:**
```bash
# Worker логи
tail -f storage/logs/worker.log

# Laravel логи
tail -f storage/logs/laravel.log

# Queue логи (systemd)
sudo journalctl -u sellermind-queue@1 -f
```

### 3. Мониторинг производительности

Создайте команду `app/Console/Commands/QueueStats.php`:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class QueueStats extends Command
{
    protected $signature = 'queue:stats';
    protected $description = 'Show queue statistics';

    public function handle(): int
    {
        $driver = config('queue.default');

        $this->info("Queue Driver: {$driver}");
        $this->newLine();

        if ($driver === 'database') {
            $pending = DB::table('jobs')->count();
            $failed = DB::table('failed_jobs')->count();

            $this->table(
                ['Status', 'Count'],
                [
                    ['Pending', $pending],
                    ['Failed', $failed],
                ]
            );

            // Старые застрявшие задачи
            $stuck = DB::table('jobs')
                ->where('reserved_at', '<', now()->subHour()->timestamp)
                ->count();

            if ($stuck > 0) {
                $this->warn("⚠️  Found {$stuck} stuck jobs (reserved > 1 hour)");
            }
        }

        return self::SUCCESS;
    }
}
```

**Использование:**
```bash
php artisan queue:stats
```

### 4. Health Check Endpoint

Добавьте в `app/Http/Controllers/Api/HealthCheckController.php`:

```php
public function queue(): JsonResponse
{
    $driver = config('queue.default');
    $health = ['driver' => $driver];

    try {
        if ($driver === 'database') {
            $pending = DB::table('jobs')->count();
            $failed = DB::table('failed_jobs')->count();
            $stuck = DB::table('jobs')
                ->where('reserved_at', '<', now()->subHour()->timestamp)
                ->count();

            $health['status'] = $stuck > 10 ? 'warning' : 'healthy';
            $health['pending'] = $pending;
            $health['failed'] = $failed;
            $health['stuck'] = $stuck;
        } elseif ($driver === 'redis') {
            $size = Redis::connection()->llen('queues:default');
            $health['status'] = 'healthy';
            $health['pending'] = $size;
        }
    } catch (\Exception $e) {
        $health['status'] = 'unhealthy';
        $health['error'] = $e->getMessage();
    }

    return response()->json($health);
}
```

**Маршрут:**
```php
Route::get('health/queue', [HealthCheckController::class, 'queue']);
```

**Использование:**
```bash
curl https://yourdomain.com/api/health/queue
```

---

## 🔧 Troubleshooting

### Проблема 1: Worker не обрабатывает задачи

**Проверка:**
```bash
# Supervisor запущен?
sudo supervisorctl status

# Процессы работают?
ps aux | grep "queue:work"

# Логи
tail -50 storage/logs/worker.log
```

**Решение:**
```bash
# Перезапустить supervisor
sudo supervisorctl restart sellermind-queue-worker:*

# Очистить кеш
php artisan config:clear
php artisan cache:clear
```

### Проблема 2: Задачи падают с ошибками

**Проверка:**
```bash
# Проваленные задачи
php artisan queue:failed

# Детали задачи
php artisan queue:failed --id=1
```

**Решение:**
```bash
# Повторить одну задачу
php artisan queue:retry 1

# Повторить все
php artisan queue:retry all

# Удалить проваленную
php artisan queue:forget 1

# Очистить все проваленные
php artisan queue:flush
```

### Проблема 3: Задачи застревают (stuck)

**Признаки:**
- Задачи в таблице `jobs` с `reserved_at` более часа назад
- Worker не обрабатывает новые задачи

**Решение:**
```bash
# Очистить застрявшие (database)
php artisan queue:clear database

# Или вручную через SQL
mysql -u username -p database_name
DELETE FROM jobs WHERE reserved_at < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 HOUR));

# Перезапустить workers
sudo supervisorctl restart sellermind-queue-worker:*
```

### Проблема 4: Высокая нагрузка на БД (database driver)

**Признаки:**
- Медленные запросы к таблице `jobs`
- High CPU на MySQL

**Решение:**

1. **Добавить индексы:**
```sql
CREATE INDEX jobs_queue_index ON jobs (queue, reserved_at);
```

2. **Переключиться на Redis:**
```env
QUEUE_CONNECTION=redis
```

3. **Уменьшить sleep:**
```bash
--sleep=5  # Вместо --sleep=1
```

### Проблема 5: Memory leaks

**Признаки:**
- Worker процессы растут в памяти
- OOM (Out of Memory) ошибки

**Решение:**

Используйте `--max-time` и `--max-jobs`:
```bash
php artisan queue:work redis --max-time=3600 --max-jobs=1000
```

Supervisor автоматически перезапустит worker после лимита.

---

## ✅ Best Practices

### 1. Всегда используйте Supervisor/Systemd в production

❌ **Плохо:**
```bash
nohup php artisan queue:work &
```

✅ **Хорошо:**
```bash
sudo supervisorctl start sellermind-queue-worker:*
```

### 2. Ограничивайте время жизни worker

```bash
--max-time=3600  # Перезапуск каждый час
```

### 3. Настройте мониторинг

- Health check endpoints
- Логирование в внешний сервис (Papertrail, Logtail)
- Alerting при ошибках (Sentry)

### 4. Тестируйте jobs локально

```bash
# Запустить одну задачу
php artisan queue:work --once

# Проверить без обработки
php artisan queue:work --stop-when-empty
```

### 5. Используйте приоритеты

```php
// Критичные задачи
SyncNewWildberriesOrdersJob::dispatch()->onQueue('high');

// Некритичные
ProcessGenerationTaskJob::dispatch()->onQueue('low');
```

### 6. Graceful shutdown

При деплое:
```bash
# 1. Остановить принятие новых задач
php artisan down

# 2. Дождаться завершения текущих (до 1 часа)
sudo supervisorctl stop sellermind-queue-worker:*

# 3. Деплой
git pull
composer install
php artisan migrate

# 4. Запустить workers
sudo supervisorctl start sellermind-queue-worker:*

# 5. Включить сайт
php artisan up
```

### 7. Backup очередей

Для database driver - включено в backup БД.

Для Redis:
```bash
# Добавить в cron
0 */6 * * * redis-cli BGSAVE
```

---

## 📚 Дополнительные ресурсы

- [Laravel Queue Documentation](https://laravel.com/docs/queues)
- [Laravel Horizon](https://laravel.com/docs/horizon) - продвинутый dashboard для очередей
- [Supervisor Documentation](http://supervisord.org/)
- [Redis Best Practices](https://redis.io/topics/admin)

---

## 🆘 Поддержка

**Логи для диагностики:**
```bash
# Laravel logs
tail -100 storage/logs/laravel.log

# Worker logs
tail -100 storage/logs/worker.log

# Supervisor logs
sudo tail -100 /var/log/supervisor/supervisord.log

# System logs
sudo journalctl -u sellermind-queue@1 --since "1 hour ago"
```

**Статистика:**
```bash
php artisan queue:stats
curl https://yourdomain.com/api/health/queue
```

---

**Последнее обновление:** Январь 2026
**Версия:** 1.0.0
