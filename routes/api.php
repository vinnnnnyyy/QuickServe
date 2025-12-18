<?php

use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\TableController;
use App\Http\Controllers\Api\TableSessionController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\AddonController;
use App\Http\Controllers\Api\SystemStatusController;
use App\Http\Controllers\Api\TableCartController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



// Category API routes
Route::prefix('categories')->name('api.categories.')->group(function () {
    Route::get('/', [CategoryController::class, 'index'])->name('index');
    Route::post('/', [CategoryController::class, 'store'])->name('store');
    Route::get('/{id}', [CategoryController::class, 'show'])->name('show');
    Route::put('/{id}', [CategoryController::class, 'update'])->name('update');
    Route::delete('/{id}', [CategoryController::class, 'destroy'])->name('destroy');
});

// Addon API routes
Route::prefix('addons')->name('api.addons.')->group(function () {
    Route::get('/', [AddonController::class, 'index'])->name('index');
    Route::post('/', [AddonController::class, 'store'])->name('store');
    Route::get('/categories', [AddonController::class, 'categories'])->name('categories');
    Route::get('/{id}', [AddonController::class, 'show'])->name('show');
    Route::put('/{id}', [AddonController::class, 'update'])->name('update');
    Route::delete('/{id}', [AddonController::class, 'destroy'])->name('destroy');
});


// Menu API routes
Route::prefix('menu')->name('api.menu.')->group(function () {
    Route::get('/', [MenuController::class, 'index'])->name('index');
    Route::post('/', [MenuController::class, 'store'])->name('store');
    Route::get('/category/{category}', [MenuController::class, 'byCategory'])->name('byCategory');
    Route::post('/reset-sample-data', [MenuController::class, 'resetSampleData'])->name('resetSampleData');
    Route::get('/{id}', [MenuController::class, 'show'])->name('show');
    Route::put('/{id}', [MenuController::class, 'update'])->name('update');
    Route::delete('/{id}', [MenuController::class, 'destroy'])->name('destroy');
});

// Orders API routes - Customer endpoints (device-isolated)
Route::middleware(['table.device', 'customer.visibility'])->prefix('orders')->name('api.orders.')->group(function () {
    Route::get('/my-orders', [OrderController::class, 'myOrders'])->name('myOrders');
    Route::post('/', [OrderController::class, 'store'])->name('store');
    Route::post('/{id}/cancel', [OrderController::class, 'cancel'])->name('cancel');
});

// Orders API routes - Admin endpoints (unrestricted)
Route::prefix('orders')->name('api.orders.')->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('index');
    Route::get('/{id}', [OrderController::class, 'show'])->name('show');
    Route::put('/{id}', [OrderController::class, 'update'])->name('update');
    Route::delete('/{id}', [OrderController::class, 'destroy'])->name('destroy');
    Route::put('/{id}/status', [OrderController::class, 'updateStatus'])->name('updateStatus');
    Route::put('/{id}/payment', [OrderController::class, 'markAsPaid'])->name('markAsPaid');
});

// Staff API routes
Route::prefix('staff')->name('api.staff.')->group(function () {
    Route::get('/', [StaffController::class, 'index'])->name('index');
    Route::post('/', [StaffController::class, 'store'])->name('store');
    Route::get('/{id}', [StaffController::class, 'show'])->name('show');
    Route::put('/{id}', [StaffController::class, 'update'])->name('update');
    Route::delete('/{id}', [StaffController::class, 'destroy'])->name('destroy');
});

// Tables API routes
Route::prefix('tables')->name('api.tables.')->group(function () {
    Route::get('/', [TableController::class, 'index'])->name('index');
    Route::post('/', [TableController::class, 'store'])->name('store');
    Route::get('/{id}', [TableController::class, 'show'])->name('show');
    Route::put('/{id}', [TableController::class, 'update'])->name('update');
    Route::delete('/{id}', [TableController::class, 'destroy'])->name('destroy');
    Route::put('/{id}/status', [TableController::class, 'updateStatus'])->name('updateStatus');
});

// Table Sessions API routes
Route::prefix('table-sessions')->name('api.sessions.')->group(function () {
    Route::get('/', [TableSessionController::class, 'index'])->name('index');
    Route::get('/active', [TableSessionController::class, 'active'])->name('active');
    Route::post('/', [TableSessionController::class, 'store'])->name('store');
    Route::post('/disconnect', [TableSessionController::class, 'disconnectCurrentDevice'])->name('disconnect');
    Route::post('/expire-stale', [TableSessionController::class, 'expireStaleSessions'])->name('expireStale');
    Route::get('/table/{tableId}', [TableSessionController::class, 'byTable'])->name('byTable');
    Route::get('/{sessionId}', [TableSessionController::class, 'show'])->name('show');
    Route::put('/{sessionId}/activity', [TableSessionController::class, 'updateActivity'])->name('updateActivity');
    Route::post('/{sessionId}/complete-payment', [TableSessionController::class, 'completePayment'])->name('completePayment');
    Route::post('/{sessionId}/end', [TableSessionController::class, 'end'])->name('end');
    Route::post('/{sessionId}/release', [TableSessionController::class, 'release'])->name('release');
    Route::post('/table/{tableId}/clear', [TableSessionController::class, 'clearTableSessions'])->name('clearTable');
    Route::delete('/{sessionId}', [TableSessionController::class, 'destroy'])->name('destroy');
});

// Inventory API routes
Route::prefix('inventory')->name('api.inventory.')->group(function () {
    Route::get('/', [InventoryController::class, 'index'])->name('index');
    Route::post('/', [InventoryController::class, 'store'])->name('store');
    Route::get('/{id}', [InventoryController::class, 'show'])->name('show');
    Route::put('/{id}', [InventoryController::class, 'update'])->name('update');
    Route::delete('/{id}', [InventoryController::class, 'destroy'])->name('destroy');
});

// System Status API routes
Route::get('/system-status', [SystemStatusController::class, 'index'])->name('api.system-status');

// Table Cart API routes (Shared Cart for One Bill)
Route::middleware(['web', 'table.device'])->prefix('table-cart')->name('api.table-cart.')->group(function () {
    Route::get('/', [TableCartController::class, 'index'])->name('index');
    Route::post('/add', [TableCartController::class, 'store'])->name('store');
    Route::delete('/{id}', [TableCartController::class, 'destroy'])->name('destroy');
    Route::post('/checkout', [TableCartController::class, 'checkout'])->name('checkout');
});

// Analytics routes
Route::get('/analytics/dashboard', function () {
    return response()->json([
        'sales_today' => 2580.50,
        'orders_today' => 145,
        'average_order' => 17.80,
        'popular_items' => [
            ['name' => 'Iced Brown Sugar Latte', 'count' => 23],
            ['name' => 'Chicken Caesar Sandwich', 'count' => 18],
            ['name' => 'Blueberry Muffin', 'count' => 15],
        ],
        'hourly_sales' => [
            ['hour' => 6, 'sales' => 120],
            ['hour' => 7, 'sales' => 280],
            ['hour' => 8, 'sales' => 450],
            ['hour' => 9, 'sales' => 380],
            ['hour' => 10, 'sales' => 290],
            ['hour' => 11, 'sales' => 340],
            ['hour' => 12, 'sales' => 520],
            ['hour' => 13, 'sales' => 390],
            ['hour' => 14, 'sales' => 250],
        ]
    ]);
});
