# QuickServe Localhost Development Setup

This project includes a complete localhost setup that runs without a database, using JSON file storage for development purposes.

## 🚀 Quick Start

### Option 1: Use the startup scripts (Recommended)

**Windows:**
```bash
# Double-click start-localhost.bat or run from command prompt:
start-localhost.bat
```

**PowerShell:**
```powershell
# Run from PowerShell:
.\start-localhost.ps1
```

### Option 2: Manual setup

1. **Set up environment:**
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Create storage directories
mkdir storage/app/data
```

2. **Install dependencies:**
```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

3. **Start the servers:**
```bash
# Terminal 1: Start Laravel server
php artisan serve

# Terminal 2: Start Vite dev server
npm run dev
```

## 📍 Access Points

- **Frontend Application:** `http://localhost:8000`
- **Admin Panel:** `http://localhost:8000/admin/dashboard`
- **API Endpoints:** `http://localhost:8000/api/`
- **Vite Dev Server:** Usually `http://localhost:5173` (check terminal output)

## 🗂️ Data Storage

The application uses JSON files for data persistence (no database required):

- **Location:** `storage/app/data/`
- **Files:** `menu.json`, `orders.json`, `staff.json`, `tables.json`, `inventory.json`
- **Auto-initialization:** Sample data is automatically created on first run

## 🛠️ API Endpoints

### Menu
- `GET /api/menu` - Get all menu items
- `POST /api/menu` - Create new menu item
- `GET /api/menu/{id}` - Get specific menu item
- `PUT /api/menu/{id}` - Update menu item
- `DELETE /api/menu/{id}` - Delete menu item
- `GET /api/menu/category/{category}` - Get items by category

### Orders
- `GET /api/orders` - Get all orders
- `POST /api/orders` - Create new order
- `GET /api/orders/{id}` - Get specific order
- `PUT /api/orders/{id}` - Update order
- `PUT /api/orders/{id}/status` - Update order status
- `DELETE /api/orders/{id}` - Delete order

### Staff
- `GET /api/staff` - Get all staff members
- `POST /api/staff` - Create new staff member
- `GET /api/staff/{id}` - Get specific staff member
- `PUT /api/staff/{id}` - Update staff member
- `DELETE /api/staff/{id}` - Delete staff member

### Tables
- `GET /api/tables` - Get all tables
- `POST /api/tables` - Create new table
- `GET /api/tables/{id}` - Get specific table
- `PUT /api/tables/{id}` - Update table
- `PUT /api/tables/{id}/status` - Update table status
- `DELETE /api/tables/{id}` - Delete table

### Inventory
- `GET /api/inventory` - Get all inventory items
- `POST /api/inventory` - Create new inventory item
- `GET /api/inventory/{id}` - Get specific inventory item
- `PUT /api/inventory/{id}` - Update inventory item
- `DELETE /api/inventory/{id}` - Delete inventory item

### Analytics
- `GET /api/analytics/dashboard` - Get dashboard analytics data

## 🔧 Features

✅ **No Database Required** - Uses JSON file storage  
✅ **Sample Data** - Automatically generated on first run  
✅ **Full CRUD Operations** - Create, Read, Update, Delete for all entities  
✅ **RESTful API** - Complete REST API with proper HTTP methods  
✅ **Frontend Integration** - Vue.js frontend connected to API  
✅ **Admin Panel** - Complete admin interface  
✅ **Real-time Updates** - Frontend updates reflect in JSON storage  
✅ **Easy Development** - One-click startup scripts  
✅ **Cross-platform** - Works on Windows, macOS, and Linux  

## 🗃️ Sample Data

The system automatically creates sample data for:

- **3 Menu Items** (Iced Brown Sugar Latte, Chicken Caesar Sandwich, Blueberry Muffin)
- **1 Sample Order** (John Smith's order)
- **2 Staff Members** (Alice Johnson - Manager, Bob Wilson - Barista)
- **15 Tables** (Mix of indoor/outdoor, various seating capacities)
- **2 Inventory Items** (Coffee Beans, Whole Milk)

## 🛡️ Error Handling

- Validation on all API endpoints
- Proper HTTP status codes
- User-friendly error messages
- Frontend error handling with alerts

## 🔄 Data Persistence

- Data persists between server restarts
- JSON files are human-readable and editable
- Automatic backup on every write operation
- Easy to reset by deleting JSON files

## 🚦 Troubleshooting

**Port already in use:**
```bash
# Change the port
php artisan serve --port=8001
```

**Cache issues:**
```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

**Storage permissions:**
```bash
# Fix storage permissions (Linux/macOS)
chmod -R 755 storage
chmod -R 775 storage/app/data
```

**Missing dependencies:**
```bash
# Reinstall dependencies
rm -rf vendor node_modules
composer install
npm install
```

## 📝 Development Notes

- All data is stored in `storage/app/data/*.json`
- CSRF tokens are handled automatically
- API responses include proper headers
- Frontend uses Inertia.js for seamless SPA experience
- TailwindCSS for styling
- Material Symbols for icons

## 🔒 Security

- CSRF protection enabled
- Input validation on all endpoints  
- File upload restrictions
- XSS protection
- Rate limiting ready (can be enabled in routes)

This setup is perfect for development, testing, and demonstration purposes. For production, consider migrating to a proper database system.
