#!/bin/bash

# ================================
# СКРИПТ ОБНОВЛЕНИЯ ПРОЕКТА НА cPanel
# ================================
# Использование: bash update-production.sh
# Выполнять на сервере для обновления из Git

set -e  # Остановка при ошибке

echo "🔄 Обновление проекта из Git..."

# Цвета для вывода
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

PROJECT_DIR="$HOME/sellermind-ai"

if [ ! -d "$PROJECT_DIR" ]; then
    echo -e "${RED}❌ Директория $PROJECT_DIR не найдена!${NC}"
    exit 1
fi

cd "$PROJECT_DIR"

# ================================
# 1. ПОЛУЧЕНИЕ ИЗМЕНЕНИЙ ИЗ GIT
# ================================
echo -e "${YELLOW}[1/7] Получение изменений из Git...${NC}"

git pull origin main

echo -e "${GREEN}✓ Изменения получены${NC}"

# ================================
# 2. ОБНОВЛЕНИЕ COMPOSER ЗАВИСИМОСТЕЙ
# ================================
echo -e "${YELLOW}[2/7] Обновление Composer зависимостей...${NC}"

if [ -f "composer.lock" ]; then
    composer install --optimize-autoloader --no-dev
    echo -e "${GREEN}✓ Зависимости обновлены${NC}"
else
    echo -e "${YELLOW}⚠️  composer.lock не найден, пропускаем${NC}"
fi

# ================================
# 3. ПРИМЕНЕНИЕ МИГРАЦИЙ
# ================================
echo -e "${YELLOW}[3/7] Применение миграций БД...${NC}"

php artisan migrate --force

echo -e "${GREEN}✓ Миграции применены${NC}"

# ================================
# 4. СИНХРОНИЗАЦИЯ WAREHOUSE
# ================================
echo -e "${YELLOW}[4/7] Синхронизация ProductVariants...${NC}"

if php artisan list | grep -q "warehouse:sync-variants"; then
    php artisan warehouse:sync-variants
    echo -e "${GREEN}✓ Синхронизация завершена${NC}"
else
    echo -e "${YELLOW}⚠️  Команда warehouse:sync-variants не найдена, пропускаем${NC}"
fi

# ================================
# 5. ОЧИСТКА КЭША
# ================================
echo -e "${YELLOW}[5/7] Очистка кэша...${NC}"

php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo -e "${GREEN}✓ Кэш очищен${NC}"

# ================================
# 6. СОЗДАНИЕ НОВОГО КЭША
# ================================
echo -e "${YELLOW}[6/7] Создание нового кэша...${NC}"

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo -e "${GREEN}✓ Кэш создан${NC}"

# ================================
# 7. ОПТИМИЗАЦИЯ AUTOLOAD
# ================================
echo -e "${YELLOW}[7/7] Оптимизация autoload...${NC}"

composer dump-autoload --optimize --classmap-authoritative

echo -e "${GREEN}✓ Autoload оптимизирован${NC}"

# ================================
# ФИНАЛ
# ================================
echo ""
echo -e "${GREEN}✅ ОБНОВЛЕНИЕ ЗАВЕРШЕНО УСПЕШНО!${NC}"
echo ""
echo "📋 Проверьте:"
echo "1. Сайт: https://$(grep APP_URL .env | cut -d'=' -f2 | tr -d '"')"
echo "2. Логи: tail -f storage/logs/laravel.log"
echo ""
