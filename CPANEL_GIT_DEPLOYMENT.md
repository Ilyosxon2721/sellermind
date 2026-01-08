# 🚀 Развертывание на cPanel через Git

## Преимущества Git подхода

✅ Не нужно создавать и загружать ZIP архив  
✅ Быстрое обновление (`git pull`)  
✅ Версионный контроль  
✅ Меньше трафика  
✅ Проще откатить изменения

---

## Требования

- SSH доступ к cPanel серверу
- Git установлен на сервере (обычно уже есть)
- Ваш проект на GitHub/GitLab

---

## Шаг 1: Подготовка GitHub репозитория

### 1.1 Убедитесь, что проект в Git

```bash
cd /Applications/MAMP/htdocs/sellermind-ai
git status
```

### 1.2 Закоммитьте все изменения

```bash
git add .
git commit -m "Подготовка к развертыванию на cPanel"
git push origin main
```

> [!IMPORTANT]
> Убедитесь, что `.env` файл НЕ в репозитории (должен быть в `.gitignore`)!

### 1.3 Проверьте .gitignore

Должны быть исключены:
```
/vendor/
/node_modules/
.env
.env.backup
```

---

## Шаг 2: Настройка cPanel

### 2.1 Создание базы данных

**cPanel** → **MySQL Database Wizard**

1. Имя БД: `sellermind`
2. Пользователь: `seller` + пароль
3. Права: **ALL PRIVILEGES**

Запомните:
```
DB_DATABASE=username_sellermind
DB_USERNAME=username_seller
DB_PASSWORD=your_password
```

### 2.2 Настройка PHP

**cPanel** → **MultiPHP Manager** → **PHP 8.2+**

**cPanel** → **MultiPHP INI Editor**:
```ini
memory_limit = 256M
max_execution_time = 300
upload_max_filesize = 20M
post_max_size = 20M
```

---

## Шаг 3: Клонирование проекта через SSH

### 3.1 Подключение по SSH

```bash
ssh username@your-server.com
```

### 3.2 Клонирование репозитория

```bash
cd ~
git clone https://github.com/your-username/sellermind-ai.git
```

**Если репозиторий приватный**, используйте один из способов:

#### Способ A: Personal Access Token (рекомендуется)

1. GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic)
2. Generate new token → выберите `repo` scope
3. Скопируйте токен

```bash
git clone https://YOUR_TOKEN@github.com/your-username/sellermind-ai.git
```

#### Способ B: SSH ключ

```bash
# На сервере сгенерируйте SSH ключ
ssh-keygen -t ed25519 -C "your-email@example.com"

# Скопируйте публичный ключ
cat ~/.ssh/id_ed25519.pub
```

Добавьте ключ в GitHub → Settings → SSH and GPG keys → New SSH key

```bash
git clone git@github.com:your-username/sellermind-ai.git
```

---

## Шаг 4: Установка зависимостей

### 4.1 Composer зависимости

```bash
cd ~/sellermind-ai
composer install --optimize-autoloader --no-dev
```

> [!NOTE]
> Если Composer не установлен на сервере:
> ```bash
> curl -sS https://getcomposer.org/installer | php
> alias composer='php ~/composer.phar'
> ```

### 4.2 Сборка Frontend (локально)

Frontend **НУЖНО** собрать локально перед коммитом:

```bash
# На вашем локальном компьютере
cd /Applications/MAMP/htdocs/sellermind-ai
npm install
npm run build
```

Затем закоммитьте собранные файлы:

```bash
git add public/build
git commit -m "Build frontend assets"
git push origin main
```

На сервере:
```bash
cd ~/sellermind-ai
git pull origin main
```

---

## Шаг 5: Настройка окружения

### 5.1 Создание .env файла

```bash
cd ~/sellermind-ai
cp .env.cpanel .env
nano .env
```

Заполните:
```env
APP_URL=https://your-domain.com
DB_DATABASE=username_sellermind
DB_USERNAME=username_seller
DB_PASSWORD=your_password

# API ключи маркетплейсов
WB_API_KEY=...
OZON_CLIENT_ID=...
OZON_API_KEY=...
```

Сохраните: **Ctrl+O**, **Enter**, **Ctrl+X**

### 5.2 Генерация APP_KEY

```bash
php artisan key:generate
```

---

## Шаг 6: Миграции и настройка

### 6.1 Применение миграций

```bash
php artisan migrate --force
```

### 6.2 Синхронизация Warehouse

```bash
php artisan warehouse:sync-variants
```

### 6.3 Оптимизация

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
composer dump-autoload --optimize --classmap-authoritative
```

### 6.4 Права доступа

```bash
chmod -R 755 storage bootstrap/cache
chmod 644 .env
```

### 6.5 Storage link

```bash
php artisan storage:link
```

---

## Шаг 7: Настройка public_html

### 7.1 Создание symlink

```bash
# Резервная копия существующего public_html
mv ~/public_html ~/public_html_backup_$(date +%Y%m%d)

# Создание symlink
ln -s ~/sellermind-ai/public ~/public_html
```

### 7.2 Проверка

```bash
ls -la ~/public_html
# Должен показать: public_html -> /home/username/sellermind-ai/public
```

---

## Шаг 8: Настройка Cron задач

**cPanel** → **Cron Jobs**

### Задача 1: Laravel Scheduler
```bash
* * * * * cd /home/username/sellermind-ai && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

### Задача 2: Queue Worker
```bash
* * * * * cd /home/username/sellermind-ai && /usr/bin/php artisan queue:work --stop-when-empty --max-time=3600 >> /home/username/sellermind-ai/storage/logs/queue.log 2>&1
```

⚠️ Замените `username` на ваше имя пользователя!

---

## Шаг 9: SSL сертификат

**cPanel** → **SSL/TLS Status** → **Run AutoSSL**

---

## Шаг 10: Проверка

### 10.1 Открыть сайт

```
https://your-domain.com
```

### 10.2 Проверка БД

```bash
cd ~/sellermind-ai
php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB OK';"
```

### 10.3 Проверка логов

```bash
tail -50 ~/sellermind-ai/storage/logs/laravel.log
```

---

## Обновление проекта (в будущем)

Когда нужно обновить проект на сервере:

```bash
cd ~/sellermind-ai

# Получить изменения из GitHub
git pull origin main

# Обновить зависимости (если composer.json изменился)
composer install --optimize-autoloader --no-dev

# Применить новые миграции
php artisan migrate --force

# Очистить и обновить кэши
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Автоматизированный скрипт обновления

Создайте файл `update.sh`:

```bash
#!/bin/bash
cd ~/sellermind-ai
git pull origin main
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "✅ Обновление завершено!"
```

Сделайте исполняемым:
```bash
chmod +x ~/sellermind-ai/update.sh
```

Использование:
```bash
bash ~/sellermind-ai/update.sh
```

---

## Работа с Git на продакшн сервере

### Просмотр текущего состояния

```bash
cd ~/sellermind-ai
git status
git log -5  # Последние 5 коммитов
```

### Откат к предыдущей версии

```bash
git log --oneline  # Посмотреть список коммитов
git checkout COMMIT_HASH  # Откатиться к конкретному коммиту
```

### Обновление с перезаписью локальных изменений

```bash
git fetch origin
git reset --hard origin/main
```

---

## .gitignore для проекта

Убедитесь, что ваш `.gitignore` содержит:

```gitignore
/node_modules
/vendor
.env
.env.backup
.env.production
.phpunit.result.cache
Homestead.json
Homestead.yaml
auth.json
npm-debug.log
yarn-error.log
/.fleet
/.idea
/.vscode
```

> [!CAUTION]
> **ВАЖНО:** Собранные frontend файлы (`public/build/`) нужно коммитить в Git!
> 
> Если у вас в `.gitignore` есть `/public/build`, удалите эту строку для production деплоя.

---

## Troubleshooting

### Git не установлен на сервере

Обратитесь в поддержку хостинга или используйте ZIP метод.

### "Permission denied" при git clone

```bash
cd ~
chmod 755 .
git clone ...
```

### Composer не установлен

```bash
cd ~
curl -sS https://getcomposer.org/installer | php
alias composer='php ~/composer.phar'
```

Добавьте алиас в `~/.bashrc`:
```bash
echo "alias composer='php ~/composer.phar'" >> ~/.bashrc
source ~/.bashrc
```

### Ошибка "Could not open input file: artisan"

```bash
cd ~/sellermind-ai
ls -la artisan  # Проверьте наличие файла
```

---

## Сравнение методов

| Метод | Преимущества | Недостатки |
|-------|-------------|-----------|
| **Git** | ✅ Быстрое обновление<br>✅ Версионный контроль<br>✅ Откат изменений | ❌ Требует SSH<br>❌ Composer на сервере |
| **ZIP** | ✅ Работает без SSH<br>✅ Не требует Git | ❌ Сложное обновление<br>❌ Большой размер |

---

## Структура проекта на сервере

```
/home/username/
├── public_html/              # Symlink → sellermind-ai/public
├── sellermind-ai/            # Git репозиторий
│   ├── .git/                 # Git директория
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/              # Корень веб-сервера
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/              # Composer зависимости
│   ├── .env                 # НЕ в Git!
│   └── artisan
└── .ssh/                    # SSH ключи (опционально)
```

---

## Итоговый чек-лист

- [ ] Проект закоммичен и запушен в GitHub
- [ ] БД создана в cPanel
- [ ] PHP 8.2+ настроен
- [ ] Проект склонирован на сервер
- [ ] Composer зависимости установлены
- [ ] Frontend собран и закоммичен
- [ ] .env файл настроен
- [ ] Миграции применены
- [ ] Symlink public_html создан
- [ ] Cron задачи настроены
- [ ] SSL сертификат установлен
- [ ] Сайт открывается и работает

---

**Готово! Теперь у вас полноценный Git workflow для cPanel! 🚀**

Для обновлений просто делайте `git push` локально, а на сервере `git pull` + кэширование.
