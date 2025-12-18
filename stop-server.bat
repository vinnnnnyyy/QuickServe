@echo off
REM Stop all QuickServe servers

echo ========================================
echo   Stopping QuickServe Servers
echo ========================================
echo.

echo Stopping PHP processes (Laravel)...
taskkill /F /IM php.exe >nul 2>&1

echo Stopping Node processes (Vite)...
taskkill /F /IM node.exe >nul 2>&1

echo.
echo All servers stopped!
echo.
pause
