# ============================================
# SellerMind Autopilot - Windows PowerShell
# ============================================

param(
    [int]$MaxTasks = 3,
    [switch]$Silent = $false
)

# Config
$ProjectPath = "D:\server\OSPanel\home\sellermind"
$LogFile = "$ProjectPath\.claude\autopilot.log"

# Go to project
Set-Location $ProjectPath

# Check requirements
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "   SELLERMIND AUTOPILOT" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check Claude
try {
    $version = claude --version 2>&1
    Write-Host "[OK] Claude Code: $version" -ForegroundColor Green
} catch {
    Write-Host "[ERROR] Claude Code not installed" -ForegroundColor Red
    exit 1
}

# Check API Key
if (-not $env:ANTHROPIC_API_KEY) {
    Write-Host "[ERROR] ANTHROPIC_API_KEY not set" -ForegroundColor Red
    exit 1
}
Write-Host "[OK] API Key: configured" -ForegroundColor Green

# Check project
if (-not (Test-Path "$ProjectPath\TASKS.md")) {
    Write-Host "[WARNING] TASKS.md not found" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Project: $ProjectPath" -ForegroundColor White
Write-Host "Max tasks: $MaxTasks" -ForegroundColor White
Write-Host ""

# Confirm
if (-not $Silent) {
    $confirm = Read-Host "Start autopilot? (y/n)"
    if ($confirm -ne 'y' -and $confirm -ne 'Y') {
        Write-Host "Cancelled" -ForegroundColor Yellow
        exit 0
    }
}

Write-Host ""
Write-Host "Starting Claude Code..." -ForegroundColor Green
Write-Host ""

# Log start
$timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
Add-Content -Path $LogFile -Value "========================================"
Add-Content -Path $LogFile -Value "$timestamp | SESSION START | MaxTasks=$MaxTasks"

# Prompt
$prompt = @"
You are running in autopilot mode. Work autonomously.

INSTRUCTIONS:
1. Read CLAUDE.md - project rules
2. Read TASKS.md - task list
3. Take first $MaxTasks tasks from queue by priority
4. For each task:
   - Move to "In Progress"
   - Analyze requirements
   - Write code
   - Write tests
   - Run: php artisan test
   - If tests pass - git commit
   - Move to "Done"
5. After all tasks - git push origin develop
6. Log everything to AUTOPILOT_LOG.md

IMPORTANT RULES:
- DO NOT git push origin main (only develop!)
- DO NOT git push --force
- Stop if tests fail 3 times
- One commit per task
- Commit messages in Russian
- Push only after successful tests

WHEN TO STOP:
- Completed $MaxTasks tasks
- Tests fail 3 times
- Task unclear (write to BLOCKERS.md)

START NOW!
"@

# Run Claude
try {
    claude -p $prompt
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    Add-Content -Path $LogFile -Value "$timestamp | SESSION END | Success"
    Write-Host ""
    Write-Host "[OK] Autopilot completed" -ForegroundColor Green
} catch {
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    Add-Content -Path $LogFile -Value "$timestamp | SESSION END | Error: $_"
    Write-Host "[ERROR] $_" -ForegroundColor Red
}

# Show results
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "   RESULTS" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "Recent commits:" -ForegroundColor Yellow
git log --oneline -5 2>$null

Write-Host ""
Write-Host "Log: $LogFile" -ForegroundColor Gray
Write-Host ""
