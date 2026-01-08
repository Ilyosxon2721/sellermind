#!/bin/bash

# ================================
# СКРИПТ КОМАНД ДЛЯ РАЗВЕРТЫВАНИЯ НА cPanel
# ================================
# Использование: bash cpanel-deploy-commands.sh
# Выполнять через SSH после загрузки файлов на сервер

set -e  # Остановка при ошибке

echo "🚀 Начинаем развертывание на cPanel..."

# Цвета для вывода
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Определяем директорию проекта
PROJECT_DIR="$HOME/sellermind-ai"

if [ ! -d "$PROJECT_DIR" ]; then
    echo -e "${RED}❌ Директория $PROJECT_DIR не найдена!${NC}"
    echo "Убедитесь, что вы распаковали архив в правильную директорию."
    exit 1
fi

cd "$PROJECT_DIR"
echo -e "${GREEN}✓ Перешли в директорию: $PROJECT_DIR${NC}"

# ================================
# 1. ПРОВЕРКА .ENV
# ================================
echo -e "${YELLOW}[1/10] Проверка конфигурации...${NC}"

if [ ! -f ".env" ]; then
    if [ -f ".env.example" ]; then
        echo "Копируем .env.example в .env..."
        cp .env.example .env
        echo -e "${YELLOW}⚠️  ВАЖНО: Отредактируйте .env файл и заполните все необходимые значения!${NC}"
        echo "После редактирования .env запустите этот скрипт снова."
        exit 0
    else
        echo -e "${RED}❌ Файл .env не найден!${NC}"
        exit 1
    fi
fi

# Проверяем критичные настройки
if grep -q "APP_KEY=$" .env; then
    echo -e "${YELLOW}⚠️  APP_KEY не сгенерирован${NC}"
fi

if grep -q "APP_DEBUG=true" .env; then
    echo -e "${RED}❌ APP_DEBUG=true в продакшн недопустимо!${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Конфигурация проверена${NC}"

# ================================
# 2. ПРОВЕРКА PHP ВЕРСИИ
# ================================
echo -e "${YELLOW}[2/10] Проверка версии PHP...${NC}"

PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo "Версия PHP: $PHP_VERSION"

if php -r "exit(version_compare(PHP_VERSION, '8.2.0', '<') ? 1 : 0);"; then
    echo -e "${RED}❌ Требуется PHP 8.2 или выше!${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Версия PHP подходит${NC}"

# ================================
# 3. ПРОВЕРКА COMPOSER
# ================================
echo -e "${YELLOW}[3/10] Проверка Composer...${NC}"

if ! command -v composer &> /dev/null; then
    echo -e "${RED}❌ Composer не установлен!${NC}"
    echo "Установите Composer или попросите хостинг-провайдера установить его."
    exit 1
fi

echo -e "${GREEN}✓ Composer найден${NC}"

# ================================
# 4. УСТАНОВКА PHP ЗАВИСИМОСТЕЙ
# ================================
echo -e "${YELLOW}[4/10] Установка PHP зависимостей...${NC}"

# Проверяем, установлены ли уже зависимости
if [ ! -d "vendor" ]; then
    composer install --optimize-autoloader --no-dev
else
    echo "Vendor директория уже существует, обновляем..."
    composer install --optimize-autoloader --no-dev
fi

echo -e "${GREEN}✓ Зависимости установлены${NC}"

# ================================
# 5. ГЕНЕРАЦИЯ APP_KEY
# ================================
echo -e "${YELLOW}[5/10] Генерация APP_KEY...${NC}"

if grep -q "APP_KEY=$" .env; then
    php artisan key:generate --force
    echo -e "${GREEN}✓ APP_KEY сгенерирован${NC}"
else
    echo -e "${GREEN}✓ APP_KEY уже существует${NC}"
fi

# ================================
# 6. МИГРАЦИИ БД
# ================================
echo -e "${YELLOW}[6/10] Применение миграций БД...${NC}"

# Проверяем подключение к БД
if php artisan tinker --execute="try { DB::connection()->getPdo(); echo 'OK'; } catch(Exception \$e) { echo 'FAIL'; exit(1); }" | grep -q "OK"; then
    echo -e "${GREEN}✓ Подключение к БД успешно${NC}"
    
    php artisan migrate --force
    echo -e "${GREEN}✓ Миграции применены${NC}"
else
    echo -e "${RED}❌ Не удалось подключиться к БД!${NC}"
    echo "Проверьте настройки DB_* в .env файле"
    exit 1
fi

# ================================
# 7. СИНХРОНИЗАЦИЯ WAREHOUSE
# ================================
echo -e "${YELLOW}[7/10] Синхронизация ProductVariants...${NC}"

if php artisan list | grep -q "warehouse:sync-variants"; then
    php artisan warehouse:sync-variants
    echo -e "${GREEN}✓ Синхронизация завершена${NC}"
else
    echo -e "${YELLOW}⚠️  Команда warehouse:sync-variants не найдена, пропускаем${NC}"
fi

# ================================
# 8. ОПТИМИЗАЦИЯ И КЭШИРОВАНИЕ
# ================================
echo -e "${YELLOW}[8/10] Оптимизация и кэширование...${NC}"

# Очистка старых кэшей
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Создание новых кэшей
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Оптимизация autoload
composer dump-autoload --optimize --classmap-authoritative

echo -e "${GREEN}✓ Оптимизация завершена${NC}"

# ================================
# 9. ПРАВА ДОСТУПА
# ================================
echo -e "${YELLOW}[9/10] Установка прав доступа...${NC}"

chmod -R 755 storage
chmod -R 755 bootstrap/cache
chmod 644 .env

echo -e "${GREEN}✓ Права установлены${NC}"

# ================================
# 10. СОЗДАНИЕ STORAGE LINK
# ================================
echo -e "${YELLOW}[10/10] Создание symlink для storage...${NC}"

if [ ! -L "public/storage" ]; then
    php artisan storage:link
    echo -e "${GREEN}✓ Storage link создан${NC}"
else
    echo -e "${GREEN}✓ Storage link уже существует${NC}"
fi

# ================================
# ФИНАЛЬНАЯ ПРОВЕРКА
# ================================
echo ""
echo -e "${GREEN}✅ РАЗВЕРТЫВАНИЕ ЗАВЕРШЕНО УСПЕШНО!${NC}"
echo ""
echo "📋 Следующие шаги:"
echo ""
echo "1. Настройте симлинк для public_html:"
echo "   bash setup-symlink.sh"
echo ""
echo "2. Настройте Cron задачи в cPanel:"
echo "   * * * * * cd $PROJECT_DIR && /usr/bin/php artisan schedule:run >> /dev/null 2>&1"
echo "   * * * * * cd $PROJECT_DIR && /usr/bin/php artisan queue:work --stop-when-empty --max-time=3600 >> $PROJECT_DIR/storage/logs/queue.log 2>&1"
echo ""
echo "3. Установите SSL сертификат через cPanel AutoSSL или Let's Encrypt"
echo ""
echo "4. Проверьте сайт: https://$(grep APP_URL .env | cut -d'=' -f2 | tr -d '"')"
echo ""
echo "5. Проверьте логи:"
echo "   tail -f storage/logs/laravel.log"
echo ""
