<?php

use App\Services\JsonStorageService;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Customer/Home/Index', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});


// Menu Route
Route::get('/menu', function () {
    return Inertia::render('Customer/Menu/Index');
})->name('menu');

// Payment Routes
Route::prefix('payment')->group(function () {
    Route::get('/gcash/{orderId}', function ($orderId) {
        return Inertia::render('Customer/Payment/GCash', [
            'orderId' => $orderId
        ]);
    })->name('payment.gcash');
});

// Order Routes
Route::prefix('order')->group(function () {
    Route::get('/confirmation/{orderId}', function ($orderId) {
        return Inertia::render('Customer/Order/Confirmation', [
            'orderId' => $orderId
        ]);
    })->name('order.confirmation');
    
    Route::get('/status', function () {
        return Inertia::render('Customer/Order/Status');
    })->name('order.status');
});


// Admin Auth Routes
Route::prefix('admin')->group(function () {
    Route::get('login', [App\Http\Controllers\Admin\Auth\AuthenticatedSessionController::class, 'create'])
        ->name('admin.login');
    
    Route::post('login', [App\Http\Controllers\Admin\Auth\AuthenticatedSessionController::class, 'store'])
        ->name('admin.login.store');
});

// Admin Routes (Protected)
Route::middleware('admin')->prefix('admin')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Admin/Dashboard/Index');
    })->name('admin.dashboard');
    
    Route::get('/orders', function (JsonStorageService $storage) {
        $orders = $storage->get('orders');
        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders
        ]);
    })->name('admin.orders');

            Route::get('/orders/add', function () {
                return Inertia::render('Admin/Orders/Create');
            })->name('admin.orders.add');

    Route::get('/orders/{id}/edit', function ($id, JsonStorageService $storage) {
        $order = $storage->find('orders', (int)$id);
        return Inertia::render('Admin/Orders/Edit', [
            'order' => $order,
            'id' => $id
        ]);
    })->name('admin.orders.edit');
    

    Route::resource('menu', App\Http\Controllers\MenuItemController::class)->except(['show']);

    
    
    Route::get('/analytics', function () {
        return Inertia::render('Admin/Analytics/Index');
    })->name('admin.analytics');
    
    Route::get('/inventory', function (JsonStorageService $storage) {
        $inventory = $storage->get('inventory');
        return Inertia::render('Admin/Inventory/Index', [
            'inventory' => $inventory
        ]);
    })->name('admin.inventory');

    Route::get('/inventory/add', function () {
        return Inertia::render('Admin/Inventory/Create');
    })->name('admin.inventory.add');
    
    Route::get('/staff', function (JsonStorageService $storage) {
        $staff = $storage->get('staff');
        return Inertia::render('Admin/Staff/Index', [
            'staff' => $staff->toArray()
        ]);
    })->name('admin.staff');

    Route::get('/staff/add', function () {
        return Inertia::render('Admin/Staff/Create');
    })->name('admin.staff.add');

    Route::get('/tables', function (JsonStorageService $storage) {
        $tables = $storage->get('tables');
        return Inertia::render('Admin/Tables/Index', [
            'tables' => $tables
        ]);
    })->name('admin.tables');

    Route::get('/tables/add', function () {
        return Inertia::render('Admin/Tables/Create');
    })->name('admin.tables.add');

    Route::get('/tables/{id}/edit', function ($id, JsonStorageService $storage) {
        $table = $storage->find('tables', (int)$id);
        return Inertia::render('Admin/Tables/Edit', [
            'table' => $table,
            'id' => $id
        ]);
    })->name('admin.tables.edit');
    
    Route::get('/barista', function () {
        return Inertia::render('Admin/Barista/Index');
    })->name('admin.barista');
    
    Route::get('/settings', function () {
        return Inertia::render('Admin/Settings/Index');
    })->name('admin.settings');
});

require __DIR__.'/auth.php';
