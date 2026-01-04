#!/bin/bash

# ================================
# СКРИПТ ПОДГОТОВКИ ПАКЕТА ДЛЯ cPanel
# ================================
# Использование: bash deploy-cpanel-package.sh
# Результат: sellermind-ai-cpanel.zip в текущей директории

set -e  # Остановка при ошибке

echo "📦 Подготовка пакета для развертывания на cPanel..."

# Цвета для вывода
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# ================================
# 1. ПРОВЕРКА ОКРУЖЕНИЯ
# ================================
echo -e "${YELLOW}[1/6] Проверка окружения...${NC}"

if [ ! -f "composer.json" ]; then
    echo -e "${RED}❌ Файл composer.json не найден! Запустите скрипт из корневой директории проекта.${NC}"
    exit 1
fi

if [ ! -f ".env.cpanel" ]; then
    echo -e "${RED}❌ Файл .env.cpanel не найден!${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Окружение проверено${NC}"

# ================================
# 2. УСТАНОВКА FRONTEND ЗАВИСИМОСТЕЙ
# ================================
echo -e "${YELLOW}[2/6] Установка npm зависимостей...${NC}"

if command -v npm &> /dev/null; then
    npm install
    echo -e "${GREEN}✓ npm зависимости установлены${NC}"
else
    echo -e "${RED}❌ npm не установлен!${NC}"
    exit 1
fi

# ================================
# 3. СБОРКА FRONTEND
# ================================
echo -e "${YELLOW}[3/6] Сборка frontend ассетов...${NC}"
npm run build
echo -e "${GREEN}✓ Frontend собран${NC}"

# ================================
# 4. УСТАНОВКА COMPOSER ЗАВИСИМОСТЕЙ
# ================================
echo -e "${YELLOW}[4/6] Установка Composer зависимостей...${NC}"

if command -v composer &> /dev/null; then
    composer install --optimize-autoloader --no-dev
    echo -e "${GREEN}✓ Composer зависимости установлены${NC}"
else
    echo -e "${RED}❌ Composer не установлен!${NC}"
    exit 1
fi

# ================================
# 5. СОЗДАНИЕ ВРЕМЕННОЙ ДИРЕКТОРИИ
# ================================
echo -e "${YELLOW}[5/6] Подготовка файлов для архива...${NC}"

TEMP_DIR="sellermind-ai-package"
rm -rf "$TEMP_DIR"
mkdir -p "$TEMP_DIR"

# Копируем необходимые файлы и директории
echo "Копирование файлов..."

# Основные директории
cp -r app "$TEMP_DIR/"
cp -r bootstrap "$TEMP_DIR/"
cp -r config "$TEMP_DIR/"
cp -r database "$TEMP_DIR/"
cp -r lang "$TEMP_DIR/"
cp -r public "$TEMP_DIR/"
cp -r resources "$TEMP_DIR/"
cp -r routes "$TEMP_DIR/"
cp -r vendor "$TEMP_DIR/"

# Storage (создаем структуру, но без логов и кэша)
mkdir -p "$TEMP_DIR/storage/app/public"
mkdir -p "$TEMP_DIR/storage/framework/cache/data"
mkdir -p "$TEMP_DIR/storage/framework/sessions"
mkdir -p "$TEMP_DIR/storage/framework/views"
mkdir -p "$TEMP_DIR/storage/logs"
echo "*" > "$TEMP_DIR/storage/logs/.gitignore"

# Корневые файлы
cp artisan "$TEMP_DIR/"
cp composer.json "$TEMP_DIR/"
cp composer.lock "$TEMP_DIR/"
cp package.json "$TEMP_DIR/"
cp package-lock.json "$TEMP_DIR/"
cp .env.cpanel "$TEMP_DIR/.env.example"

# Скрипты развертывания
if [ -f "cpanel-deploy-commands.sh" ]; then
    cp cpanel-deploy-commands.sh "$TEMP_DIR/"
fi

if [ -f "setup-symlink.sh" ]; then
    cp setup-symlink.sh "$TEMP_DIR/"
fi

# Документация
if [ -f "CPANEL_DEPLOYMENT_GUIDE.md" ]; then
    cp CPANEL_DEPLOYMENT_GUIDE.md "$TEMP_DIR/"
fi

if [ -f "README.md" ]; then
    cp README.md "$TEMP_DIR/"
fi

# .gitignore и другие конфигурационные файлы
cp .gitignore "$TEMP_DIR/" 2>/dev/null || true
cp .editorconfig "$TEMP_DIR/" 2>/dev/null || true

echo -e "${GREEN}✓ Файлы подготовлены${NC}"

# ================================
# 6. СОЗДАНИЕ АРХИВА
# ================================
echo -e "${YELLOW}[6/6] Создание ZIP архива...${NC}"

ARCHIVE_NAME="sellermind-ai-cpanel-$(date +%Y%m%d-%H%M%S).zip"

# Используем zip для создания архива
cd "$TEMP_DIR"
zip -r "../$ARCHIVE_NAME" . -q
cd ..

# Удаляем временную директорию
rm -rf "$TEMP_DIR"

echo -e "${GREEN}✓ Архив создан: $ARCHIVE_NAME${NC}"

# Показываем размер архива
ARCHIVE_SIZE=$(du -h "$ARCHIVE_NAME" | cut -f1)
echo ""
echo -e "${GREEN}✅ ПАКЕТ ГОТОВ К ЗАГРУЗКЕ!${NC}"
echo ""
echo "📦 Файл: $ARCHIVE_NAME"
echo "💾 Размер: $ARCHIVE_SIZE"
echo ""
echo "📋 Следующие шаги:"
echo "1. Загрузите $ARCHIVE_NAME на сервер через cPanel File Manager"
echo "2. Распакуйте архив в директорию /home/username/sellermind-ai"
echo "3. Следуйте инструкциям в CPANEL_DEPLOYMENT_GUIDE.md"
echo ""
