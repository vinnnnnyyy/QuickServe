# QuickServe Database Setup Script
# This script helps you set up the database for QuickServe

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  QuickServe Database Setup" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if .env exists
if (-not (Test-Path .env)) {
    Write-Host "ERROR: .env file not found!" -ForegroundColor Red
    Write-Host "Copying .env.example to .env..." -ForegroundColor Yellow
    Copy-Item .env.example .env
    Write-Host "Please update .env with your database credentials and run this script again" -ForegroundColor Yellow
    pause
    exit
}

# Ask user for database type
Write-Host "Choose your database:" -ForegroundColor Yellow
Write-Host "  [1] SQLite (Recommended for offline/portable setup)" -ForegroundColor White
Write-Host "  [2] MySQL (Current configuration)" -ForegroundColor White
Write-Host ""
$choice = Read-Host "Enter choice (1 or 2)"

if ($choice -eq "1") {
    # Configure SQLite
    Write-Host ""
    Write-Host "Configuring SQLite..." -ForegroundColor Yellow
    
    # Create database directory
    $dbPath = "database\database.sqlite"
    if (-not (Test-Path $dbPath)) {
        New-Item -Path $dbPath -ItemType File -Force | Out-Null
        Write-Host "  Created database file: $dbPath" -ForegroundColor Green
    }
    
    # Update .env
    $envContent = Get-Content .env -Raw
    $envContent = $envContent -replace 'DB_CONNECTION=.*', 'DB_CONNECTION=sqlite'
    $envContent = $envContent -replace 'DB_HOST=.*', '# DB_HOST=127.0.0.1'
    $envContent = $envContent -replace 'DB_PORT=.*', '# DB_PORT=3306'
    $envContent = $envContent -replace 'DB_DATABASE=.*', "# DB_DATABASE=$dbPath"
    $envContent = $envContent -replace 'DB_USERNAME=.*', '# DB_USERNAME=root'
    $envContent = $envContent -replace 'DB_PASSWORD=.*', '# DB_PASSWORD='
    
    # Also update session and cache to file-based
    $envContent = $envContent -replace 'SESSION_DRIVER=.*', 'SESSION_DRIVER=file'
    $envContent = $envContent -replace 'CACHE_STORE=.*', 'CACHE_STORE=file'
    $envContent = $envContent -replace 'QUEUE_CONNECTION=.*', 'QUEUE_CONNECTION=sync'
    
    $envContent | Set-Content .env -NoNewline
    
    Write-Host "  Database configured for SQLite" -ForegroundColor Green
    
} elseif ($choice -eq "2") {
    # Keep MySQL configuration
    Write-Host ""
    Write-Host "Using MySQL configuration..." -ForegroundColor Yellow
    
    # Read current MySQL settings
    $envContent = Get-Content .env
    $dbHost = ($envContent | Where-Object { $_ -match '^DB_HOST=' }) -replace 'DB_HOST=', ''
    $dbPort = ($envContent | Where-Object { $_ -match '^DB_PORT=' }) -replace 'DB_PORT=', ''
    $dbName = ($envContent | Where-Object { $_ -match '^DB_DATABASE=' }) -replace 'DB_DATABASE=', ''
    $dbUser = ($envContent | Where-Object { $_ -match '^DB_USERNAME=' }) -replace 'DB_USERNAME=', ''
    
    Write-Host "  Host: $dbHost" -ForegroundColor White
    Write-Host "  Port: $dbPort" -ForegroundColor White
    Write-Host "  Database: $dbName" -ForegroundColor White
    Write-Host "  Username: $dbUser" -ForegroundColor White
    Write-Host ""
    Write-Host "Make sure MySQL is running and the database '$dbName' exists" -ForegroundColor Yellow
    Write-Host ""
    
    $confirm = Read-Host "Continue with these settings? (y/n)"
    if ($confirm -ne "y") {
        Write-Host "Setup cancelled. Please update .env manually" -ForegroundColor Red
        pause
        exit
    }
} else {
    Write-Host "Invalid choice. Exiting..." -ForegroundColor Red
    pause
    exit
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Running Migrations" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Clear config cache
Write-Host "Clearing configuration cache..." -ForegroundColor Yellow
php artisan config:clear | Out-Null

# Run migrations
Write-Host "Running database migrations..." -ForegroundColor Yellow
Write-Host ""

try {
    php artisan migrate --force
    Write-Host ""
    Write-Host "Migrations completed successfully!" -ForegroundColor Green
} catch {
    Write-Host ""
    Write-Host "ERROR: Migration failed!" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    pause
    exit
}

# Ask about seeding
Write-Host ""
Write-Host "Would you like to seed sample data? (y/n)" -ForegroundColor Yellow
$seed = Read-Host
if ($seed -eq "y") {
    Write-Host ""
    Write-Host "Seeding database..." -ForegroundColor Yellow
    try {
        php artisan db:seed --force
        Write-Host ""
        Write-Host "Sample data seeded successfully!" -ForegroundColor Green
    } catch {
        Write-Host ""
        Write-Host "WARNING: Seeding had errors (this is often OK if seeders don't exist)" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Database Setup Complete!" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "You can now run: start-hotspot-server.bat" -ForegroundColor Green
Write-Host ""
pause
