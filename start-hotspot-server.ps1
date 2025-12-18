# QuickServe Hotspot Server Startup Script
# This script configures and starts the QuickServe application for hotspot access

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  QuickServe Hotspot Server Setup" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Function to get the hotspot IP
function Get-HotspotIP {
    Write-Host "Detecting network configuration..." -ForegroundColor Yellow
    
    # Get all IPv4 addresses
    $adapters = Get-NetIPAddress -AddressFamily IPv4 | Where-Object { 
        $_.IPAddress -notlike "127.*" -and $_.PrefixOrigin -eq "Manual" 
    }
    
    Write-Host ""
    Write-Host "Available Network Adapters:" -ForegroundColor Green
    $index = 1
    $adapters | ForEach-Object {
        Write-Host "  [$index] $($_.IPAddress) - $($_.InterfaceAlias)" -ForegroundColor White
        $index++
    }
    
    # Check for common hotspot IP
    $hotspotIP = $adapters | Where-Object { $_.IPAddress -like "192.168.137.*" } | Select-Object -First 1
    
    if ($hotspotIP) {
        Write-Host ""
        Write-Host "Windows Mobile Hotspot detected: $($hotspotIP.IPAddress)" -ForegroundColor Green
        return $hotspotIP.IPAddress
    }
    
    # Otherwise, use first non-localhost IP
    $firstIP = $adapters | Select-Object -First 1
    if ($firstIP) {
        Write-Host ""
        Write-Host "Using primary IP: $($firstIP.IPAddress)" -ForegroundColor Green
        return $firstIP.IPAddress
    }
    
    # Fallback
    return "192.168.137.1"
}

# Get the IP address
$SERVER_IP = Get-HotspotIP
$SERVER_PORT = 8000
$VITE_PORT = 5173

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Configuration" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Server IP: $SERVER_IP" -ForegroundColor White
Write-Host "Laravel Port: $SERVER_PORT" -ForegroundColor White
Write-Host "Vite Port: $VITE_PORT" -ForegroundColor White
Write-Host ""

# Update .env file
Write-Host "Updating .env configuration..." -ForegroundColor Yellow
$envContent = Get-Content .env -Raw
$envContent = $envContent -replace 'APP_URL=.*', "APP_URL=http://${SERVER_IP}:${SERVER_PORT}"
$envContent | Set-Content .env -NoNewline

Write-Host "  APP_URL set to: http://${SERVER_IP}:${SERVER_PORT}" -ForegroundColor Green
Write-Host ""

# Update vite.config.js
Write-Host "Updating vite.config.js for network access..." -ForegroundColor Yellow
$viteConfig = @"
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: ${VITE_PORT},
        hmr: {
            host: '${SERVER_IP}',
            port: ${VITE_PORT}
        }
    }
});
"@
$viteConfig | Set-Content vite.config.js -NoNewline

Write-Host "  Vite configured for network access" -ForegroundColor Green
Write-Host ""

# Check and configure firewall
Write-Host "Checking firewall rules..." -ForegroundColor Yellow
$laravelRule = Get-NetFirewallRule -DisplayName "QuickServe Laravel" -ErrorAction SilentlyContinue
$viteRule = Get-NetFirewallRule -DisplayName "QuickServe Vite" -ErrorAction SilentlyContinue

if (-not $laravelRule) {
    Write-Host "  Creating firewall rule for Laravel (port $SERVER_PORT)..." -ForegroundColor Yellow
    try {
        New-NetFirewallRule -DisplayName "QuickServe Laravel" -Direction Inbound -Protocol TCP -LocalPort $SERVER_PORT -Action Allow -ErrorAction Stop | Out-Null
        Write-Host "    Firewall rule created successfully" -ForegroundColor Green
    } catch {
        Write-Host "    Could not create firewall rule (requires admin). Please run as Administrator or allow manually." -ForegroundColor Red
    }
} else {
    Write-Host "  Laravel firewall rule already exists" -ForegroundColor Green
}

if (-not $viteRule) {
    Write-Host "  Creating firewall rule for Vite (port $VITE_PORT)..." -ForegroundColor Yellow
    try {
        New-NetFirewallRule -DisplayName "QuickServe Vite" -Direction Inbound -Protocol TCP -LocalPort $VITE_PORT -Action Allow -ErrorAction Stop | Out-Null
        Write-Host "    Firewall rule created successfully" -ForegroundColor Green
    } catch {
        Write-Host "    Could not create firewall rule (requires admin). Please run as Administrator or allow manually." -ForegroundColor Red
    }
} else {
    Write-Host "  Vite firewall rule already exists" -ForegroundColor Green
}

Write-Host ""

# Clear cache
Write-Host "Clearing Laravel cache..." -ForegroundColor Yellow
php artisan config:clear | Out-Null
php artisan cache:clear | Out-Null
php artisan route:clear | Out-Null
php artisan view:clear | Out-Null
Write-Host "  Cache cleared" -ForegroundColor Green
Write-Host ""

# Display access information
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Server Information" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "CUSTOMER ACCESS:" -ForegroundColor Green
Write-Host "  1. Connect to your WiFi hotspot" -ForegroundColor White
Write-Host "  2. Open browser and go to:" -ForegroundColor White
Write-Host "     http://${SERVER_IP}:${SERVER_PORT}" -ForegroundColor Yellow
Write-Host ""
Write-Host "ADMIN ACCESS:" -ForegroundColor Green
Write-Host "     http://${SERVER_IP}:${SERVER_PORT}/admin/login" -ForegroundColor Yellow
Write-Host ""
Write-Host "QR CODE URLs will be:" -ForegroundColor Green
Write-Host "     http://${SERVER_IP}:${SERVER_PORT}/tables/{id}" -ForegroundColor Yellow
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Start servers
Write-Host "Starting servers..." -ForegroundColor Yellow
Write-Host ""
Write-Host "Opening Laravel server on http://${SERVER_IP}:${SERVER_PORT}" -ForegroundColor Green
Write-Host "Opening Vite dev server on http://${SERVER_IP}:${VITE_PORT}" -ForegroundColor Green
Write-Host ""
Write-Host "Press Ctrl+C to stop all servers" -ForegroundColor Red
Write-Host ""

# Start Laravel and Vite using composer script (concurrently)
# This uses the dev script from composer.json which runs both servers
try {
    # Update the composer dev script to use network host
    $composerDev = "npx concurrently -c `"#93c5fd,#c4b5fd,#fdba74`" `"php artisan serve --host=0.0.0.0 --port=${SERVER_PORT}`" `"php artisan queue:listen --tries=1`" `"npm run dev -- --host=0.0.0.0`" --names='server,queue,vite'"
    
    Write-Host "Starting QuickServe servers..." -ForegroundColor Cyan
    Write-Host ""
    
    Invoke-Expression $composerDev
} catch {
    Write-Host ""
    Write-Host "Error starting servers. Starting manually..." -ForegroundColor Red
    Write-Host ""
    
    # Fallback: Start servers in separate windows
    Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$PWD'; Write-Host 'Laravel Server' -ForegroundColor Cyan; php artisan serve --host=0.0.0.0 --port=${SERVER_PORT}"
    Start-Sleep -Seconds 2
    Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$PWD'; Write-Host 'Vite Dev Server' -ForegroundColor Cyan; npm run dev -- --host=0.0.0.0"
    
    Write-Host "Servers started in separate windows" -ForegroundColor Green
    Write-Host "Close those windows to stop the servers" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Press any key to exit this window..." -ForegroundColor White
    $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
}
