#!/bin/bash

# ============================================
# SellerMind - Night Development Mode
# ============================================
# Запускает Claude Code в автономном режиме
# для выполнения задач пока ты спишь
# ============================================

set -e

# Цвета
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Конфигурация
PROJECT_DIR="${PROJECT_DIR:-$(pwd)}"
MAX_TASKS="${MAX_TASKS:-5}"
LOG_FILE="$PROJECT_DIR/.claude/night-session.log"
TASKS_FILE="$PROJECT_DIR/TASKS.md"

# Проверки
check_requirements() {
    echo -e "${BLUE}🔍 Проверка требований...${NC}"
    
    # Claude Code установлен?
    if ! command -v claude &> /dev/null; then
        echo -e "${RED}❌ Claude Code не установлен${NC}"
        echo "Установи: npm install -g @anthropic-ai/claude-code"
        exit 1
    fi
    
    # ANTHROPIC_API_KEY?
    if [ -z "$ANTHROPIC_API_KEY" ]; then
        echo -e "${RED}❌ ANTHROPIC_API_KEY не установлен${NC}"
        echo "Добавь в ~/.bashrc или ~/.zshrc:"
        echo "export ANTHROPIC_API_KEY='your-key'"
        exit 1
    fi
    
    # TASKS.md существует?
    if [ ! -f "$TASKS_FILE" ]; then
        echo -e "${RED}❌ TASKS.md не найден${NC}"
        exit 1
    fi
    
    # Git чистый?
    if [ -n "$(git status --porcelain)" ]; then
        echo -e "${YELLOW}⚠️ Есть незакоммиченные изменения${NC}"
        read -p "Продолжить? (y/n) " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
    fi
    
    echo -e "${GREEN}✅ Все проверки пройдены${NC}"
}

# Подсчёт задач
count_tasks() {
    grep -c '^\- \[ \]' "$TASKS_FILE" 2>/dev/null || echo 0
}

# Показать статус
show_status() {
    TASKS_COUNT=$(count_tasks)
    echo ""
    echo -e "${BLUE}════════════════════════════════════════${NC}"
    echo -e "${BLUE}   🌙 NIGHT DEVELOPMENT MODE${NC}"
    echo -e "${BLUE}════════════════════════════════════════${NC}"
    echo ""
    echo -e "📁 Проект:     ${GREEN}$PROJECT_DIR${NC}"
    echo -e "📋 Задач:      ${YELLOW}$TASKS_COUNT${NC}"
    echo -e "🎯 Макс:       ${YELLOW}$MAX_TASKS${NC}"
    echo -e "📝 Лог:        ${GREEN}$LOG_FILE${NC}"
    echo ""
}

# Запуск Claude Code
run_claude() {
    echo -e "${BLUE}🚀 Запуск Claude Code...${NC}"
    echo ""
    
    # Создаём директорию для логов
    mkdir -p "$(dirname "$LOG_FILE")"
    
    # Промпт для автопилота
    PROMPT="
Ты запущен в ночном режиме автономной разработки.

ИНСТРУКЦИИ:
1. Прочитай CLAUDE.md — там правила проекта
2. Прочитай TASKS.md — там список задач
3. Выполни до $MAX_TASKS задач из очереди (🟡)
4. Для каждой задачи:
   - Перенеси в 'В работе'
   - Реализуй
   - Напиши тесты
   - Запусти тесты
   - Закоммить если тесты прошли
   - Перенеси в 'Выполнено'
5. Логируй всё в AUTOPILOT_LOG.md

ПРАВИЛА:
- Остановись если тесты падают 3 раза подряд
- Не делай миграции без подтверждения
- Коммить после каждой задачи
- Пиши осмысленные commit messages

НАЧИНАЙ!
"
    
    # Запуск
    echo "$(date '+%Y-%m-%d %H:%M:%S') | NIGHT SESSION STARTED" >> "$LOG_FILE"
    echo "Tasks in queue: $(count_tasks)" >> "$LOG_FILE"
    echo "" >> "$LOG_FILE"
    
    cd "$PROJECT_DIR"
    
    claude -p "$PROMPT" \
        --allowedTools "Read,Write,Edit,Bash" \
        --output-format stream-json \
        2>&1 | tee -a "$LOG_FILE"
    
    echo "" >> "$LOG_FILE"
    echo "$(date '+%Y-%m-%d %H:%M:%S') | NIGHT SESSION ENDED" >> "$LOG_FILE"
}

# Показать результаты
show_results() {
    echo ""
    echo -e "${BLUE}════════════════════════════════════════${NC}"
    echo -e "${BLUE}   📊 РЕЗУЛЬТАТЫ${NC}"
    echo -e "${BLUE}════════════════════════════════════════${NC}"
    echo ""
    
    # Подсчёт коммитов за сессию
    COMMITS=$(git log --since="8 hours ago" --oneline | wc -l)
    echo -e "📝 Коммитов:   ${GREEN}$COMMITS${NC}"
    
    # Оставшиеся задачи
    REMAINING=$(count_tasks)
    echo -e "📋 Осталось:   ${YELLOW}$REMAINING${NC}"
    
    # Последние коммиты
    echo ""
    echo -e "${BLUE}Последние коммиты:${NC}"
    git log --since="8 hours ago" --oneline | head -10
    
    echo ""
    echo -e "📄 Полный лог: ${GREEN}$LOG_FILE${NC}"
}

# Main
main() {
    echo ""
    check_requirements
    show_status
    
    read -p "Запустить ночной режим? (y/n) " -n 1 -r
    echo
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        run_claude
        show_results
    else
        echo -e "${YELLOW}Отменено${NC}"
    fi
}

# Запуск
main "$@"
