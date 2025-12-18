@echo off
REM QuickServe Hotspot Server Startup Script (Batch Version)
REM This will launch the PowerShell script

echo ========================================
echo   QuickServe Hotspot Server
echo ========================================
echo.

REM Check if running as administrator
net session >nul 2>&1
if %errorLevel% == 0 (
    echo Running as Administrator - Full firewall access
    echo.
) else (
    echo WARNING: Not running as Administrator
    echo Some firewall rules may not be created automatically
    echo Right-click this file and select "Run as Administrator" for full setup
    echo.
    timeout /t 3 >nul
)

REM Run the PowerShell script
powershell -ExecutionPolicy Bypass -File "%~dp0start-hotspot-server.ps1"

pause
