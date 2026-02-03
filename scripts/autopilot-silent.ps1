# ============================================
# SellerMind Autopilot - Silent Mode
# ============================================
# Для запуска через Task Scheduler
# Без подтверждений, с полным логированием
# ============================================

param(
    [int]$MaxTasks = 3
)

# Конфигурация
$ProjectPath = "D:\server\OSPanel\home\sellermind"
$LogFile = "$ProjectPath\.claude\autopilot.log"
$ErrorLogFile = "$ProjectPath\.claude\autopilot-error.log"

# Создаём папку для логов если нет
$logDir = Split-Path $LogFile -Parent
if (-not (Test-Path $logDir)) {
    New-Item -ItemType Directory -Path $logDir -Force | Out-Null
}

# Логирование
function Write-Log {
    param([string]$Message)
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    Add-Content -Path $LogFile -Value "$timestamp | $Message"
}

# Начало
Write-Log "========================================"
Write-Log "SILENT_SESSION_START | MaxTasks=$MaxTasks"

# Проверка API Key
if (-not $env:ANTHROPIC_API_KEY) {
    Write-Log "ERROR | ANTHROPIC_API_KEY не установлен"
    exit 1
}

# Проверка проекта
if (-not (Test-Path $ProjectPath)) {
    Write-Log "ERROR | Папка проекта не найдена"
    exit 1
}

# Переходим в проект
Set-Location $ProjectPath

# Промпт
$prompt = @"
Ты запущен в автоматическом режиме (ночная сессия). Работай полностью автономно.

ИНСТРУКЦИИ:
1. Прочитай CLAUDE.md — правила проекта
2. Прочитай TASKS.md — список задач
3. Выполни до $MaxTasks задач из очереди по приоритету
4. Для каждой задачи:
   - Перенеси в "В работе"
   - Напиши код и тесты
   - Запусти php artisan test
   - Если тесты прошли — git commit
   - Перенеси в "Выполнено"
5. После всех задач — git push origin develop
6. Логируй всё в AUTOPILOT_LOG.md

ПРАВИЛА:
- НЕ делай git push origin main!
- НЕ делай git push --force!
- Останавись если тесты падают 3 раза
- Коммиты на русском
- Push только после успешных тестов

НАЧИНАЙ!
"@

# Запуск Claude
Write-Log "CLAUDE_START | Launching claude..."

try {
    $output = claude -p $prompt --allowedTools "Read,Write,Edit,Bash" 2>&1
    Write-Log "CLAUDE_END | Success"
    
    # Записываем вывод в лог
    Add-Content -Path $LogFile -Value "OUTPUT:"
    Add-Content -Path $LogFile -Value $output
} catch {
    Write-Log "CLAUDE_END | Error: $_"
    Add-Content -Path $ErrorLogFile -Value "$(Get-Date) | $_"
}

# Статистика
$commitCount = (git log --since="1 hour ago" --oneline 2>$null | Measure-Object -Line).Lines
Write-Log "STATS | Commits this session: $commitCount"
Write-Log "SILENT_SESSION_END"
Write-Log "========================================"
