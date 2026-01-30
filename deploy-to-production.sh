#!/bin/bash

# ================================
# СКРИПТ ДЕПЛОЯ НА ПРОДАКШН
# ================================
# Использование: bash deploy-to-production.sh

set -e  # Остановка при ошибке

echo "🚀 Начинаем деплой на продакшн..."

# Цвета для вывода
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# ================================
# 1. ПРОВЕРКА ОКРУЖЕНИЯ
# ================================
echo -e "${YELLOW}[1/10] Проверка окружения...${NC}"

if [ ! -f ".env" ]; then
    echo -e "${RED}❌ Файл .env не найден!${NC}"
    echo "Скопируйте .env.production в .env и заполните все значения"
    exit 1
fi

if grep -q "APP_DEBUG=true" .env; then
    echo -e "${RED}❌ APP_DEBUG=true в продакшн недопустимо!${NC}"
    exit 1
fi

if grep -q "APP_ENV=local" .env; then
    echo -e "${RED}❌ APP_ENV должен быть production!${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Окружение проверено${NC}"

# ================================
# 2. ОБНОВЛЕНИЕ КОДА
# ================================
echo -e "${YELLOW}[2/10] Обновление кода из git...${NC}"
git pull origin main
echo -e "${GREEN}✓ Код обновлен${NC}"

# ================================
# 3. УСТАНОВКА ЗАВИСИМОСТЕЙ
# ================================
echo -e "${YELLOW}[3/10] Установка Composer зависимостей...${NC}"
composer install --optimize-autoloader --no-dev
echo -e "${GREEN}✓ Зависимости установлены${NC}"

# ================================
# 4. МИГРАЦИИ БД
# ================================
echo -e "${YELLOW}[4/10] Применение миграций БД...${NC}"
php artisan migrate --force
echo -e "${GREEN}✓ Миграции применены${NC}"

# ================================
# 5. ОЧИСТКА И КЭШИРОВАНИЕ
# ================================
echo -e "${YELLOW}[5/10] Очистка кэшей...${NC}"
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo -e "${GREEN}✓ Кэши очищены${NC}"

echo -e "${YELLOW}[6/10] Кэширование конфигурации...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
echo -e "${GREEN}✓ Кэширование завершено${NC}"

# ================================
# 7. ОПТИМИЗАЦИЯ AUTOLOAD
# ================================
echo -e "${YELLOW}[7/10] Оптимизация autoload...${NC}"
composer dump-autoload --optimize --classmap-authoritative
echo -e "${GREEN}✓ Autoload оптимизирован${NC}"

# ================================
# 8. ПРАВА ДОСТУПА
# ================================
echo -e "${YELLOW}[8/10] Установка прав доступа...${NC}"
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
echo -e "${GREEN}✓ Права установлены${NC}"

# ================================
# 9. ПЕРЕЗАПУСК СЕРВИСОВ
# ================================
echo -e "${YELLOW}[9/10] Перезапуск queue workers...${NC}"
php artisan queue:restart
echo -e "${GREEN}✓ Queue restart signal sent (workers will restart after current job)${NC}"

# Перезапуск PHP-FPM
echo -e "${YELLOW}[10/10] Перезапуск PHP-FPM...${NC}"
if command -v systemctl &> /dev/null; then
    sudo systemctl reload php8.2-fpm 2>/dev/null || sudo systemctl reload php8.3-fpm 2>/dev/null || echo -e "${YELLOW}⚠ PHP-FPM reload skipped${NC}"
    echo -e "${GREEN}✓ PHP-FPM перезагружен${NC}"
else
    echo -e "${YELLOW}⚠ systemctl не найден, пропускаем${NC}"
fi

echo ""
echo -e "${GREEN}✅ ДЕПЛОЙ ЗАВЕРШЕН УСПЕШНО!${NC}"
echo ""
echo "📋 Следующие шаги:"
echo "1. Проверьте сайт: https://yourdomain.com"
echo "2. Проверьте логи: tail -f storage/logs/laravel.log"
echo "3. Проверьте queue workers: supervisorctl status sellermind-worker:*"
echo ""
