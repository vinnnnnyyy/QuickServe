<?php
use App\Http\Controllers\Web\MenuItemController;
use App\Http\Controllers\Web\TableController;
use App\Http\Controllers\Web\InventoryController;
use App\Http\Controllers\Web\AddonController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Web\OrdersController;
use App\Services\JsonStorageService;
use App\Services\DeviceIdentifierService;
use App\Http\Controllers\Web\HomeController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;


// Root always redirects to scan-qr
Route::get('/', function () {
    if (session()->has('table_id')) {
        $table = \App\Models\Table::find(session('table_id'));
        if ($table) {
            return redirect()->route('table.menu', ['token' => $table->qr_token]);
        }
    }
    return redirect()->route('scan.qr');
});

// Scan QR page
Route::get('/scan-qr', function () {
    return Inertia::render('Customer/ScanQR');
})->name('scan.qr');

// Join table via access code
Route::post('/join', function (\Illuminate\Http\Request $request) {
    $request->validate(['code' => 'required|string']);
    
    $table = \App\Models\Table::where('access_code', strtoupper($request->code))->first();
    
    if (!$table) {
        return back()->withErrors(['code' => 'Invalid access code']);
    }
    
    return redirect()->route('table.menu', ['token' => $table->qr_token]);
})->name('join');

// Table Access via Token (Secure)
Route::get('/table/{token}', function ($token, \Illuminate\Http\Request $request) {
    // SECURITY: Lookup by token instead of ID to prevent enumeration
    $table = \App\Models\Table::where('qr_token', $token)->first();
    
    if (!$table) {
        return redirect()->route('scan.qr')->withErrors(['token' => 'Invalid table token']);
    }
    
    $deviceId = DeviceIdentifierService::getOrCreate($request);
    
    // 0. Pre-calculate/Detect Device Info
     $userAgent = $request->userAgent();
     $deviceType = 'Desktop';
     if (preg_match('/mobile|android.*mobile|iphone|ipod|blackberry|iemobile|opera mini|opera mobi/i', strtolower($userAgent))) {
         $deviceType = 'Mobile';
     } elseif (preg_match('/tablet|ipad|playbook|silk|android(?!.*mobile)/i', strtolower($userAgent))) {
         $deviceType = 'Tablet';
     }
     
     $browser = 'Unknown';
     if (preg_match('/edg/i', $userAgent)) {
         $browser = 'Microsoft Edge';
     } elseif (preg_match('/opr|opera/i', $userAgent)) {
         $browser = 'Opera';
     } elseif (preg_match('/chrome|crios/i', $userAgent)) {
         $browser = 'Chrome';
     } elseif (preg_match('/firefox|fxios/i', $userAgent)) {
         $browser = 'Firefox';
     } elseif (preg_match('/safari/i', $userAgent) && !preg_match('/chrome|crios/i', $userAgent)) {
         $browser = 'Safari';
     } elseif (preg_match('/msie|trident/i', $userAgent)) {
         $browser = 'Internet Explorer';
     }

    // 1. Try to find the session *this specific device* is already part of
    $mySession = \App\Models\TableSession::where('table_id', $table->id)
        ->whereIn('status', ['active', 'paid_leaving'])
        ->where(function ($query) use ($request, $deviceId) {
             $query->where('device_ip', $request->ip()) // Legacy/Fallback
                   ->orWhere('host_device_id', $deviceId) // I am host
                   ->orWhereJsonContains('users', ['device_id' => $deviceId]); // I am a user
        })
        ->first();

    // 2. If no session for ME, look for ANY active session on this table to JOIN
    $anyActiveSession = \App\Models\TableSession::where('table_id', $table->id)
        ->whereIn('status', ['active', 'paid_leaving'])
        ->first();

    // 3. Count active sessions for occupancy logic
    $activeSessions = \App\Models\TableSession::where('table_id', $table->id)
        ->whereIn('status', ['active', 'paid_leaving'])
        ->count();

    $currentSession = $mySession;

    if (!$currentSession) {
        if ($anyActiveSession) {
            // JOIN EXISTING SESSION
            $currentSession = $anyActiveSession;
        } else {
            // CREATE NEW SESSION (Host)
             $maxId = \App\Models\TableSession::max('id') ?? 0;
             $currentSession = \App\Models\TableSession::create([
                'session_id' => '#' . (1247 + $maxId + 1),
                'table_id' => $table->id,
                'device_ip' => $request->ip(),
                'host_device_id' => null, // Provisional: No host yet
                'users' => [], // Empty initially
                'metadata' => ['payment_mode' => null, 'customer_type' => null], // Wait for user selection
                'device_type' => $deviceType,
                'browser' => $browser,
                'user_agent' => $userAgent,
                'status' => 'active',
                'started_at' => now(),
                'last_activity_at' => now(),
                'current_activity' => 'Started Session',
                'network_name' => 'CafeOrder_WiFi',
            ]);
            $activeSessions++;
        }
    } else {
        // I am already part of a session, just update activity
        $currentSession->last_activity_at = now();
        $currentSession->save();
    }
    
    $table->occupied = $activeSessions;
    $table->updateStatusFromOccupancy();
    
    session([
        'table_id' => $table->id,
        'table_number' => $table->number,
        'device_id' => $deviceId,
    ]);
    
    $featuredItems = \App\Models\MenuItem::where('featured', true)
        ->limit(6)
        ->get(['name', 'description', 'price', 'image_path']);
    
    $response = Inertia::render('Customer/Home/Index', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'featuredItems' => $featuredItems,
        'tableId' => $table->id,
        'tableNumber' => $table->number,
        'token' => $token,
        'customerType' => $currentSession ? ($currentSession->metadata['customer_type'] ?? null) : null,
        'paymentMode' => $currentSession ? ($currentSession->metadata['payment_mode'] ?? 'host') : 'host',
        'sessionId' => $currentSession ? $currentSession->session_id : null,
    ])->toResponse($request);
    
    if (!$request->cookie(DeviceIdentifierService::COOKIE_NAME)) {
        $cookie = DeviceIdentifierService::createCookie($deviceId);
        $response->headers->setCookie($cookie);
    }
    
    return $response;
})->name('table.menu');

// Table Full Menu with Token (Secure)
Route::get('/table/{token}/menu', function ($token, \Illuminate\Http\Request $request) {
    $table = \App\Models\Table::where('qr_token', $token)->first();
    
    if (!$table) {
        return redirect()->route('scan.qr')->withErrors(['token' => 'Invalid table token']);
    }
    
    $deviceId = DeviceIdentifierService::getOrCreate($request);
    
    // Ensure session exists (replicate logic from table.menu)
    $existingSession = \App\Models\TableSession::where('table_id', $table->id)
        ->where('device_ip', $request->ip())
        ->whereIn('status', ['active', 'paid_leaving'])
        ->first();

    if (!$existingSession) {
         // Optionally create session or redirect to home to init session
         // Since the user is clicking "View All", they should have a session.
         // But to be safe, we can redirect to table.menu which handles init
         return redirect()->route('table.menu', ['token' => $token]);
    }
    
    // Refresh session activity
    $existingSession->last_activity_at = now();
    $existingSession->current_activity = 'Browsing full menu';
    $existingSession->save();
    
    // Ensure session in Laravel
    session([
        'table_id' => $table->id,
        'table_number' => $table->number,
        'device_id' => $deviceId,
    ]);
    
    $paymentMode = $existingSession->metadata['payment_mode'] ?? 'host';

    return Inertia::render('Customer/Menu/Index', [
        'tableId' => $table->id,
        'tableNumber' => $table->number,
        'paymentMode' => $paymentMode,
        'token' => $token,
    ]);
})->name('table.fullmenu');

// Protected customer routes (require QR scan session + device context)
Route::middleware(['table.device', 'customer.visibility'])->group(function () {
    Route::get('/menu', function () {
        $tableId = session('table_id');
        $session = \App\Models\TableSession::where('table_id', $tableId)
            ->whereIn('status', ['active', 'paid_leaving'])
            ->latest() // Get latest just in case
            ->first();

        // Default to individual if not set, or whatever existing logic
        // But per requirements, we want to respect what was selected in "customer-type" endpoint
        $paymentMode = $session->metadata['payment_mode'] ?? 'host';

        return Inertia::render('Customer/Menu/Index', [
            'tableId' => session('table_id'),
            'tableNumber' => session('table_number'),
            'paymentMode' => $paymentMode, 
        ]);
    })->name('menu');
    
    Route::prefix('payment')->name('payment.')->group(function () {
        Route::get('/gcash/{orderId}', function ($orderId) {
            return Inertia::render('Customer/Payment/GCash', [
                'orderId' => $orderId,
            ]);
        })->name('gcash');
    });

    Route::post('/api/session/customer-type', function (\Illuminate\Http\Request $request) {
        $request->validate([
            'type' => 'required|in:individual,group',
            'payment_mode' => 'nullable|required_if:type,group|in:host,split',
            'table_id' => 'nullable|integer|exists:tables,id',
        ]);

        // Accept table_id from request body as fallback for external devices
        $tableId = $request->input('table_id') ?? session('table_id');
        $deviceId = DeviceIdentifierService::getOrCreate($request);
        
        if (!$tableId) {
            return response()->json(['message' => 'No active session. Please provide table_id.'], 404);
        }

        // 1. Find ANY active session for this table (more robust than IP check)
        $session = \App\Models\TableSession::where('table_id', $tableId)
            ->whereIn('status', ['active', 'paid_leaving'])
            ->first();

        if ($session) {
            $metadata = $session->metadata ?? [];
            $previousCustomerType = $metadata['customer_type'] ?? null;
            $metadata['customer_type'] = $request->type;
            
            $users = $session->users ?? [];
            $isClaimingHost = false;

            if ($request->type === 'individual') {
                $metadata['payment_mode'] = 'individual';
            } else {
                $metadata['payment_mode'] = $request->payment_mode;
                
                // Initialize Host if choosing Hosted Group
                if ($request->payment_mode === 'host') {
                    // Claim if no host set yet
                    if (empty($session->host_device_id)) {
                        $session->host_device_id = $deviceId;
                        $isClaimingHost = true;
                    } 
                    // Or if I am already the host (re-confirming)
                    else if ($session->host_device_id === $deviceId) {
                        $isClaimingHost = true;
                    }
                }
            }
            
            // Upsert User in List
            $existingUserIndex = collect($users)->search(fn($u) => $u['device_id'] === $deviceId);
            
            $userRole = $isClaimingHost ? 'host' : 'guest';
            
            $userData = [
                'device_id' => $deviceId,
                'name' => $isClaimingHost ? 'Host' : ($request->input('name') ?? 'Guest'), // Basic name logic
                'role' => $userRole,
                'status' => 'approved',
                'joined_at' => now()->toISOString()
            ];

            if ($existingUserIndex !== false) {
                // Update existing
                $userData['joined_at'] = $users[$existingUserIndex]['joined_at']; // Keep join time
                // Keep name if already set custom? For now reset or keep
                 if (isset($users[$existingUserIndex]['name']) && $users[$existingUserIndex]['name'] !== 'Guest') {
                    $userData['name'] = $users[$existingUserIndex]['name'];
                 }
                $users[$existingUserIndex] = array_merge($users[$existingUserIndex], $userData);
            } else {
                // Append new
                $users[] = $userData;
            }

            $session->users = $users;
            $session->metadata = $metadata;
            $session->save();
            
            return response()->json(['success' => true, 'is_host' => $session->host_device_id === $deviceId]);
        }

        return response()->json(['message' => 'Session not found'], 404);
    })->name('session.customer-type');

    // New API Routes for Hosted Group Flow
    Route::post('/api/session/init-host', [\App\Http\Controllers\Api\TableSessionController::class, 'initHost'])->name('session.init-host');
    Route::post('/api/session/join-request', [\App\Http\Controllers\Api\TableSessionController::class, 'joinRequest'])->name('session.join-request');
    Route::post('/api/session/guest-action', [\App\Http\Controllers\Api\TableSessionController::class, 'handleGuestRequest'])->name('session.guest-action');
    Route::post('/api/session/update-settings', [\App\Http\Controllers\Api\TableSessionController::class, 'updateSettings'])->name('session.update-settings');
    Route::get('/api/session/{tableId}/status', [\App\Http\Controllers\Api\TableSessionController::class, 'getSessionStatus'])->name('session.status');

    
    Route::prefix('order')->name('order.')->group(function () {
        Route::get('/confirmation/{orderId}', function ($orderId) {
            return Inertia::render('Customer/Order/Confirmation', [
                'orderId' => $orderId,
            ]);
        })->name('confirmation');

        Route::get('/status', function () {
            return Inertia::render('Customer/Order/Status');
        })->name('status');

        Route::get('/history', function () {
            return Inertia::render('Customer/Order/History');
        })->name('history');
    });
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
    
    // Logout
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
    
    // Dashboard
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

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
            'edit'   => 'menu.edit',
            'update' => 'menu.update',
            'destroy' => 'menu.destroy',
        ]);

    // Add-ons Management
    Route::resource('addons', AddonController::class)
        ->except(['show'])
        ->names([
            'index' => 'addons.index',
            'create' => 'addons.create',
            'store' => 'addons.store',
            'edit' => 'addons.edit',
            'update' => 'addons.update',
            'destroy' => 'addons.destroy',
        ]);
    Route::patch('addons/{id}/toggle', [AddonController::class, 'toggleAvailability'])
        ->name('addons.toggle');

    // Analytics
    Route::get('/analytics', [\App\Http\Controllers\Admin\DashboardController::class, 'analytics'])->name('analytics');

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
        Route::get('/', function () {
            $staff = \App\Models\Staff::with('user')->get();
            
            // Transform staff data to match frontend expectations
            $transformedStaff = $staff->map(function ($s) {
                return [
                    'id' => $s->id,
                    'name' => $s->user->name,
                    'email' => $s->user->email,
                    'phone' => $s->phone,
                    'role' => $s->role,
                    'shift' => $s->shift,
                    'hourly_rate' => (float) $s->hourly_rate,
                    'status' => $s->status,
                    'hire_date' => $s->hire_date->format('Y-m-d'),
                    'image' => $s->image ? Storage::url($s->image) : null,
                    'created_at' => $s->created_at->toISOString(),
                    'updated_at' => $s->updated_at->toISOString(),
                ];
            });
            
            return Inertia::render('Admin/Staff/Index', [
                'staff' => $transformedStaff->toArray(),
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

