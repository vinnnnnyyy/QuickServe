# QuickServe - Display Server Information
# Shows current network configuration and access URLs

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  QuickServe Server Information" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Get network adapters
$adapters = Get-NetIPAddress -AddressFamily IPv4 | Where-Object { 
    $_.IPAddress -notlike "127.*" -and $_.PrefixOrigin -eq "Manual" 
}

Write-Host "Network Adapters:" -ForegroundColor Yellow
$adapters | ForEach-Object {
    $isHotspot = $_.IPAddress -like "192.168.137.*"
    $marker = if ($isHotspot) { " [HOTSPOT]" } else { "" }
    Write-Host "  $($_.IPAddress) - $($_.InterfaceAlias)$marker" -ForegroundColor White
}

Write-Host ""

# Read current .env APP_URL
if (Test-Path .env) {
    $envContent = Get-Content .env
    $appUrl = ($envContent | Where-Object { $_ -match '^APP_URL=' }) -replace 'APP_URL=', ''
    
    Write-Host "Current Configuration:" -ForegroundColor Yellow
    Write-Host "  APP_URL: $appUrl" -ForegroundColor White
    
    # Extract IP from APP_URL
    if ($appUrl -match 'http://([0-9.]+):?(\d+)?') {
        $configuredIP = $matches[1]
        $configuredPort = if ($matches[2]) { $matches[2] } else { "8000" }
        
        Write-Host ""
        Write-Host "Access URLs:" -ForegroundColor Green
        Write-Host "  Customer: http://${configuredIP}:${configuredPort}" -ForegroundColor Cyan
        Write-Host "  Admin:    http://${configuredIP}:${configuredPort}/admin/login" -ForegroundColor Cyan
        Write-Host "  Menu:     http://${configuredIP}:${configuredPort}/menu" -ForegroundColor Cyan
        Write-Host ""
        Write-Host "QR Code Format:" -ForegroundColor Green
        Write-Host "  http://${configuredIP}:${configuredPort}/tables/{id}" -ForegroundColor Yellow
    }
} else {
    Write-Host "ERROR: .env file not found!" -ForegroundColor Red
    Write-Host "Please run setup first" -ForegroundColor Yellow
}

Write-Host ""

# Check if servers are running
Write-Host "Server Status:" -ForegroundColor Yellow
$phpProcess = Get-Process php -ErrorAction SilentlyContinue | Where-Object { $_.CommandLine -like "*artisan serve*" }
$nodeProcess = Get-Process node -ErrorAction SilentlyContinue

if ($phpProcess) {
    Write-Host "  Laravel Server: RUNNING" -ForegroundColor Green
} else {
    Write-Host "  Laravel Server: STOPPED" -ForegroundColor Red
}

if ($nodeProcess) {
    Write-Host "  Vite Server:    RUNNING" -ForegroundColor Green
} else {
    Write-Host "  Vite Server:    STOPPED" -ForegroundColor Red
}

Write-Host ""

# Check firewall rules
Write-Host "Firewall Rules:" -ForegroundColor Yellow
$laravelRule = Get-NetFirewallRule -DisplayName "QuickServe Laravel" -ErrorAction SilentlyContinue
$viteRule = Get-NetFirewallRule -DisplayName "QuickServe Vite" -ErrorAction SilentlyContinue

if ($laravelRule) {
    $laravelEnabled = $laravelRule.Enabled
    $status = if ($laravelEnabled) { "ENABLED" } else { "DISABLED" }
    $color = if ($laravelEnabled) { "Green" } else { "Red" }
    Write-Host "  Laravel (8000): $status" -ForegroundColor $color
} else {
    Write-Host "  Laravel (8000): NOT CONFIGURED" -ForegroundColor Yellow
}

if ($viteRule) {
    $viteEnabled = $viteRule.Enabled
    $status = if ($viteEnabled) { "ENABLED" } else { "DISABLED" }
    $color = if ($viteEnabled) { "Green" } else { "Red" }
    Write-Host "  Vite (5173):    $status" -ForegroundColor $color
} else {
    Write-Host "  Vite (5173):    NOT CONFIGURED" -ForegroundColor Yellow
}

Write-Host ""

# Check database configuration
if (Test-Path .env) {
    $dbConnection = ($envContent | Where-Object { $_ -match '^DB_CONNECTION=' }) -replace 'DB_CONNECTION=', ''
    Write-Host "Database:" -ForegroundColor Yellow
    Write-Host "  Type: $dbConnection" -ForegroundColor White
    
    if ($dbConnection -eq "sqlite") {
        $dbFile = "database\database.sqlite"
        if (Test-Path $dbFile) {
            $size = (Get-Item $dbFile).Length / 1KB
            Write-Host "  File: $dbFile ($([math]::Round($size, 2)) KB)" -ForegroundColor White
        } else {
            Write-Host "  File: NOT FOUND (run setup-database.ps1)" -ForegroundColor Red
        }
    } elseif ($dbConnection -eq "mysql") {
        $dbHost = ($envContent | Where-Object { $_ -match '^DB_HOST=' }) -replace 'DB_HOST=', ''
        $dbName = ($envContent | Where-Object { $_ -match '^DB_DATABASE=' }) -replace 'DB_DATABASE=', ''
        Write-Host "  Host: $dbHost" -ForegroundColor White
        Write-Host "  Name: $dbName" -ForegroundColor White
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Quick actions
Write-Host "Quick Actions:" -ForegroundColor Yellow
Write-Host "  [S] Start servers" -ForegroundColor White
Write-Host "  [T] Stop servers" -ForegroundColor White
Write-Host "  [D] Setup database" -ForegroundColor White
Write-Host "  [Q] Quit" -ForegroundColor White
Write-Host ""

$action = Read-Host "Choose an action (or press Enter to exit)"

switch ($action.ToUpper()) {
    "S" {
        Write-Host ""
        Write-Host "Starting servers..." -ForegroundColor Green
        & "$PSScriptRoot\start-hotspot-server.ps1"
    }
    "T" {
        Write-Host ""
        Write-Host "Stopping servers..." -ForegroundColor Yellow
        & "$PSScriptRoot\stop-server.bat"
    }
    "D" {
        Write-Host ""
        Write-Host "Starting database setup..." -ForegroundColor Green
        & "$PSScriptRoot\setup-database.ps1"
    }
    default {
        Write-Host "Goodbye!" -ForegroundColor Cyan
    }
}
