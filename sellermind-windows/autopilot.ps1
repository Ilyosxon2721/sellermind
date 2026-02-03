# ============================================
# SellerMind Autopilot - Windows PowerShell
# ============================================
# Полная автоматизация разработки с Claude Code
# ============================================

param(
    [int]$MaxTasks = 3,
    [string]$TaskNumber = "",
    [switch]$DryRun = $false
)

# Конфигурация
$ProjectPath = "D:\server\OSPanel\home\sellermind"
$LogFile = "$ProjectPath\.claude\autopilot.log"
$TasksFile = "$ProjectPath\TASKS.md"

# Цвета
function Write-ColorOutput($ForegroundColor) {
    $fc = $host.UI.RawUI.ForegroundColor
    $host.UI.RawUI.ForegroundColor = $ForegroundColor
    if ($args) {
        Write-Output $args
    }
    $host.UI.RawUI.ForegroundColor = $fc
}

# Логирование
function Write-Log {
    param([string]$Message)
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logMessage = "$timestamp | $Message"
    Add-Content -Path $LogFile -Value $logMessage
    Write-Host $logMessage
}

# Проверки
function Test-Requirements {
    Write-Host "`n🔍 Проверка требований..." -ForegroundColor Cyan
    
    # Claude Code
    try {
        $version = claude --version 2>&1
        Write-Host "✅ Claude Code: $version" -ForegroundColor Green
    } catch {
        Write-Host "❌ Claude Code не установлен" -ForegroundColor Red
        exit 1
    }
    
    # API Key
    if (-not $env:ANTHROPIC_API_KEY) {
        Write-Host "❌ ANTHROPIC_API_KEY не установлен" -ForegroundColor Red
        exit 1
    }
    Write-Host "✅ API Key: настроен" -ForegroundColor Green
    
    # Project folder
    if (-not (Test-Path $ProjectPath)) {
        Write-Host "❌ Папка проекта не найдена: $ProjectPath" -ForegroundColor Red
        exit 1
    }
    Write-Host "✅ Проект: $ProjectPath" -ForegroundColor Green
    
    # TASKS.md
    if (-not (Test-Path $TasksFile)) {
        Write-Host "❌ TASKS.md не найден" -ForegroundColor Red
        exit 1
    }
    Write-Host "✅ TASKS.md: найден" -ForegroundColor Green
    
    # CLAUDE.md
    if (-not (Test-Path "$ProjectPath\CLAUDE.md")) {
        Write-Host "⚠️ CLAUDE.md не найден (рекомендуется)" -ForegroundColor Yellow
    } else {
        Write-Host "✅ CLAUDE.md: найден" -ForegroundColor Green
    }
}

# Подсчёт задач
function Get-TaskCount {
    $content = Get-Content $TasksFile -Raw
    $matches = [regex]::Matches($content, '^\- \[ \]', 'Multiline')
    return $matches.Count
}

# Показать статус
function Show-Status {
    $taskCount = Get-TaskCount
    
    Write-Host "`n════════════════════════════════════════" -ForegroundColor Cyan
    Write-Host "   🤖 SELLERMIND AUTOPILOT" -ForegroundColor Cyan
    Write-Host "════════════════════════════════════════" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "📁 Проект:        $ProjectPath"
    Write-Host "📋 Задач в очереди: $taskCount"
    Write-Host "🎯 Макс. задач:   $MaxTasks"
    Write-Host "📝 Лог:           $LogFile"
    Write-Host ""
}

# Основной промпт для Claude
function Get-AutopilotPrompt {
    if ($TaskNumber) {
        return @"
Ты запущен в режиме автопилота. Выполни конкретную задачу.

ИНСТРУКЦИИ:
1. Прочитай CLAUDE.md — там правила проекта
2. Прочитай TASKS.md — найди задачу $TaskNumber
3. Выполни эту задачу:
   - Проанализируй что нужно сделать
   - Напиши код
   - Напиши тесты
   - Запусти тесты: php artisan test
   - Если тесты прошли — сделай git commit
4. Обнови TASKS.md — перенеси задачу в "Выполнено"
5. Запиши результат в AUTOPILOT_LOG.md

ВАЖНЫЕ ПРАВИЛА:
- НЕ делай git push (запрещено!)
- Останавись если тесты падают 3 раза
- Коммить только если тесты прошли
- Пиши коммиты на русском

НАЧИНАЙ!
"@
    } else {
        return @"
Ты запущен в режиме автопилота. Работай автономно.

ИНСТРУКЦИИ:
1. Прочитай CLAUDE.md — там правила проекта
2. Прочитай TASKS.md — там список задач
3. Возьми первые $MaxTasks задач из очереди (🟡) по приоритету
4. Для каждой задачи:
   - Перенеси в "В работе" (🔴)
   - Проанализируй что нужно
   - Напиши код
   - Напиши тесты  
   - Запусти: php artisan test
   - Если тесты прошли — git commit
   - Перенеси в "Выполнено" (✅)
5. Запиши всё в AUTOPILOT_LOG.md

ВАЖНЫЕ ПРАВИЛА:
- НЕ делай git push (запрещено!)
- Останавись если тесты падают 3 раза подряд
- Один коммит на одну задачу
- Коммиты на русском языке

КОГДА ОСТАНОВИТЬСЯ:
- Выполнено $MaxTasks задач
- Тесты падают 3 раза
- Задача непонятна (запиши в BLOCKERS.md)

НАЧИНАЙ!
"@
    }
}

# Запуск Claude
function Start-Autopilot {
    Write-Host "🚀 Запуск автопилота..." -ForegroundColor Green
    Write-Host ""
    
    Write-Log "SESSION_START | MaxTasks=$MaxTasks"
    
    # Переходим в папку проекта
    Set-Location $ProjectPath
    
    # Получаем промпт
    $prompt = Get-AutopilotPrompt
    
    if ($DryRun) {
        Write-Host "🔍 DRY RUN — команда которая будет выполнена:" -ForegroundColor Yellow
        Write-Host ""
        Write-Host "claude -p `"$prompt`"" -ForegroundColor Gray
        Write-Host ""
        return
    }
    
    # Запускаем Claude
    try {
        claude -p $prompt --allowedTools "Read,Write,Edit,Bash"
        Write-Log "SESSION_END | Success"
    } catch {
        Write-Log "SESSION_END | Error: $_"
        Write-Host "❌ Ошибка: $_" -ForegroundColor Red
    }
}

# Показать результаты
function Show-Results {
    Write-Host "`n════════════════════════════════════════" -ForegroundColor Cyan
    Write-Host "   📊 РЕЗУЛЬТАТЫ" -ForegroundColor Cyan
    Write-Host "════════════════════════════════════════" -ForegroundColor Cyan
    
    Set-Location $ProjectPath
    
    # Последние коммиты
    Write-Host "`n📝 Последние коммиты:" -ForegroundColor Yellow
    git log --oneline -5 2>$null
    
    # Оставшиеся задачи
    $remaining = Get-TaskCount
    Write-Host "`n📋 Задач осталось: $remaining" -ForegroundColor Yellow
    
    # Напоминание
    Write-Host "`n⚠️  Не забудь проверить и сделать push:" -ForegroundColor Magenta
    Write-Host "    cd $ProjectPath"
    Write-Host "    git log --oneline -5"
    Write-Host "    php artisan test"
    Write-Host "    git push origin develop"
}

# Main
function Main {
    Write-Host ""
    Write-Host "╔═══════════════════════════════════════╗" -ForegroundColor Cyan
    Write-Host "║     SELLERMIND AUTOPILOT v1.0        ║" -ForegroundColor Cyan  
    Write-Host "║     Полная автоматизация разработки  ║" -ForegroundColor Cyan
    Write-Host "╚═══════════════════════════════════════╝" -ForegroundColor Cyan
    
    Test-Requirements
    Show-Status
    
    $confirm = Read-Host "Запустить автопилот? (y/n)"
    if ($confirm -ne 'y' -and $confirm -ne 'Y') {
        Write-Host "Отменено" -ForegroundColor Yellow
        return
    }
    
    Start-Autopilot
    Show-Results
}

# Запуск
Main
