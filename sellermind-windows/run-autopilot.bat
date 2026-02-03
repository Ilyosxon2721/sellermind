@echo off
REM ============================================
REM SellerMind Autopilot - Quick Start
REM ============================================
REM Двойной клик для запуска автопилота
REM ============================================

title SellerMind Autopilot

echo.
echo ========================================
echo    SELLERMIND AUTOPILOT
echo ========================================
echo.

cd /d D:\server\OSPanel\home\sellermind

powershell -ExecutionPolicy Bypass -File "scripts\autopilot.ps1" -MaxTasks 3

echo.
echo ========================================
echo    ГОТОВО! Проверь результаты:
echo    git log --oneline -5
echo ========================================
echo.
pause
