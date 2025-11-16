<?php
use App\Http\Controllers\Web\MenuItemController;
use App\Http\Controllers\Web\TableController;
use App\Http\Controllers\Web\InventoryController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Web\OrdersController;
use App\Services\JsonStorageService;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


// Home Page
Route::get('/', function () {
    return Inertia::render('Customer/Home/Index', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');

// Menu
Route::get('/menu', function () {
    return Inertia::render('Customer/Menu/Index');
})->name('menu');

// Payment Routes
Route::prefix('payment')->name('payment.')->group(function () {
    Route::get('/gcash/{orderId}', function ($orderId) {
        return Inertia::render('Customer/Payment/GCash', [
            'orderId' => $orderId,
        ]);
    })->name('gcash');
});

// Order Routes
Route::prefix('order')->name('order.')->group(function () {
    Route::get('/confirmation/{orderId}', function ($orderId) {
        return Inertia::render('Customer/Order/Confirmation', [
            'orderId' => $orderId,
        ]);
    })->name('confirmation');

    Route::get('/status', function () {
        return Inertia::render('Customer/Order/Status');
    })->name('status');
});

/*
|--------------------------------------------------------------------------
| Admin Authentication Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->name('login.store');
});

/*
|--------------------------------------------------------------------------
| Admin Protected Routes
|--------------------------------------------------------------------------
*/

Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', function () {
        return Inertia::render('Admin/Dashboard/Index');
    })->name('dashboard');

    // Orders Management
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrdersController::class, 'index'])->name('index');
        Route::get('/add', [OrdersController::class, 'create'])->name('add');
        Route::get('/{id}/edit', [OrdersController::class, 'edit'])->name('edit');
    });

    // Menu Management
    Route::resource('menu', MenuItemController::class)
        ->except(['show'])
        ->names([
            'index' => 'menu.index',
            'create' => 'menu.create',
            'store' => 'menu.store',
            'edit' => 'menu.edit',
            'update' => 'menu.update',
            'destroy' => 'menu.destroy',
        ]);

    // Analytics
    Route::get('/analytics', function () {
        return Inertia::render('Admin/Analytics/Index');
    })->name('analytics');

    // Inventory Management
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::get('/add', [InventoryController::class, 'create'])->name('add');
        Route::post('/', [InventoryController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [InventoryController::class, 'edit'])->name('edit');
        Route::put('/{id}', [InventoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [InventoryController::class, 'destroy'])->name('destroy');
    });

    // Staff Management
    Route::prefix('staff')->name('staff.')->group(function () {
        Route::get('/', function (JsonStorageService $storage) {
            return Inertia::render('Admin/Staff/Index', [
                'staff' => $storage->get('staff')->toArray(),
            ]);
        })->name('index');

        Route::get('/add', function () {
            return Inertia::render('Admin/Staff/Create');
        })->name('add');
    });

    // Tables Management
    Route::prefix('tables')->name('tables.')->group(function () {
        Route::get('/', [TableController::class, 'index'])->name('index');
        Route::get('/add', [TableController::class, 'create'])->name('add');
        Route::post('/', [TableController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [TableController::class, 'edit'])->name('edit');
        Route::put('/{id}', [TableController::class, 'update'])->name('update');
        Route::delete('/{id}', [TableController::class, 'destroy'])->name('destroy');
    });

    // Barista
    Route::get('/barista', function () {
        return Inertia::render('Admin/Barista/Index');
    })->name('barista');

    // Settings
    Route::get('/settings', function () {
        return Inertia::render('Admin/Settings/Index');
    })->name('settings');
});

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';
