<?php

use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\TableController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\CategoryController;
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

// Orders API routes
Route::prefix('orders')->name('api.orders.')->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('index');
    Route::post('/', [OrderController::class, 'store'])->name('store');
    Route::get('/{id}', [OrderController::class, 'show'])->name('show');
    Route::put('/{id}', [OrderController::class, 'update'])->name('update');
    Route::delete('/{id}', [OrderController::class, 'destroy'])->name('destroy');
    Route::put('/{id}/status', [OrderController::class, 'updateStatus'])->name('updateStatus');
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

// Inventory API routes
Route::prefix('inventory')->name('api.inventory.')->group(function () {
    Route::get('/', [InventoryController::class, 'index'])->name('index');
    Route::post('/', [InventoryController::class, 'store'])->name('store');
    Route::get('/{id}', [InventoryController::class, 'show'])->name('show');
    Route::put('/{id}', [InventoryController::class, 'update'])->name('update');
    Route::delete('/{id}', [InventoryController::class, 'destroy'])->name('destroy');
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
