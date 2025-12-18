<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Table;
use App\Models\TableSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TableController extends Controller
{
    /**
     * Display a listing of tables
     */
    public function index(): Response
    {
        $tables = Table::with(['sessions' => function ($query) {
            $query->select('id', 'table_id', 'session_id', 'device_ip', 'browser', 'status');
        }])->get();

        $sessions = TableSession::with('table')
            ->select('id', 'table_id', 'session_id', 'device_ip', 'device_type', 'browser', 'user_agent', 'status', 'started_at', 'last_activity_at', 'current_activity')
            ->get();

        $transformedTables = $tables->map(function (Table $table) {
            return [
                'id' => $table->id,
                'number' => $table->number,
                'location' => $table->location,
                'locationColor' => $this->getLocationColor($table->location),
                'capacity' => $table->capacity,
                'occupied' => $table->occupied ?? 0,
                'status' => $table->status,
                'statusColor' => $this->getStatusColor($table->status),
                'statusText' => $this->getStatusText($table->status),
                'statusDot' => $this->getStatusDot($table->status),
                'statusText' => $this->getStatusText($table->status),
                'statusDot' => $this->getStatusDot($table->status),
                'qrCode' => $table->qr_code,
                'qrToken' => $table->qr_token,
                'accessCode' => $table->access_code,
                'sessions' => $table->sessions->map(function (TableSession $session) {
                    return [
                        'id' => $session->session_id,
                        'device' => $session->device_ip,
                        'browser' => $session->browser,
                        'status' => $session->status,
                    ];
                })->toArray(),
            ];
        });

        $activeSessions = $sessions->filter(function (TableSession $session) {
            return in_array($session->status, ['active', 'paid_leaving']);
        })->map(function (TableSession $session) use ($sessions) {
            $table = $session->table;

            return [
                'id' => $session->session_id,
                'tableId' => $session->table_id,
                'tableNumber' => $table->number ?? 0,
                'location' => $table->location ?? 'Unknown',
                'capacity' => $table->capacity ?? 0,
                'deviceCount' => $this->getDeviceCount($sessions, $session->table_id),
                'currentDevice' => $this->getCurrentDevice($sessions, $session->table_id, $session->id),
                'deviceInfo' => [
                    'ip' => $session->device_ip ?? 'Unknown',
                    'browser' => $session->browser ?? 'Unknown',
                    'deviceType' => $session->device_type ?? 'Unknown',
                    'userAgent' => $session->user_agent ?? '',
                ],
                'status' => $session->status,
                'duration' => $session->started_at ? $this->formatDuration($session->started_at->toIso8601String()) : '0 min',
                'startTime' => $session->started_at ? $this->formatTime($session->started_at->toIso8601String()) : '',
                'lastActivity' => $session->last_activity_at ? $this->formatLastActivity($session->last_activity_at->toIso8601String()) : 'N/A',
                'activity' => $session->current_activity ?? 'Active',
            ];
        })->values();

        return Inertia::render('Admin/Tables/Index', [
            'tables' => $transformedTables,
            'activeSessions' => $activeSessions,
        ]);
    }

    /**
     * Show the form for creating a new table
     */
    public function create(): Response
    {
        $tables = Table::select('location', 'capacity', 'features', 'number')->get();

        $existingLocations = $tables->pluck('location')
            ->filter()
            ->unique()
            ->values();

        $existingCapacities = $tables->pluck('capacity')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $existingFeatures = $tables->pluck('features')
            ->filter()
            ->flatMap(fn ($features) => $features ?? [])
            ->filter()
            ->unique()
            ->values();

        $nextTableNumber = (Table::max('number') ?? 0) + 1;

        return Inertia::render('Admin/Tables/Create', [
            'existingLocations' => $existingLocations,
            'existingCapacities' => $existingCapacities,
            'existingFeatures' => $existingFeatures,
            'nextTableNumber' => $nextTableNumber,
        ]);
    }

    

    /**
     * Store a newly created table
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'number' => 'required|integer|min:1',
            'location' => 'required|string|max:50',
            'capacity' => 'required|integer|min:1|max:50',
            'description' => 'nullable|string',
            'notes' => 'nullable|string|max:500',
            'features' => 'nullable|array',
            'features.*' => 'string|max:50',
        ]);

        $validated['status'] = 'available';
        $validated['occupied'] = 0;
        $validated['qr_code'] = 'QR_TABLE_' . str_pad($validated['number'], 3, '0', STR_PAD_LEFT);
        $validated['features'] = array_values($validated['features'] ?? []);
        $validated['qr_token'] = Str::random(32);
        // Generate a random 6-character access code (uppercase alphanumeric)
        $validated['access_code'] = strtoupper(Str::random(6));
        
        $table = Table::create($validated);
        
        $serverIp = $request->ip() === '127.0.0.1' || $request->ip() === '::1' 
            ? gethostbyname(gethostname()) 
            : $request->server('SERVER_ADDR');
        
        $table->qr_code_url = 'http://' . $serverIp . ':' . $request->server('SERVER_PORT', '8000') . '/table/' . $table->qr_token;
        $table->save();

        return redirect()->route('admin.tables.index')
            ->with('success', 'Table created successfully');
    }

    /**
     * Show the form for editing the specified table
     */
    public function edit(int $id): Response
    {
        $table = Table::findOrFail($id);

        return Inertia::render('Admin/Tables/Edit', [
            'table' => $table,
            'id' => $id,
        ]);
    }

    /**
     * Update the specified table
     */
    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'number' => 'required|integer|min:1',
            'location' => 'required|string|max:50',
            'capacity' => 'required|integer|min:1|max:50',
            'status' => 'nullable|in:available,partial,full,cleaning,reserved,out_of_service',
            'description' => 'nullable|string',
            'notes' => 'nullable|string|max:500',
            'features' => 'nullable|array',
            'features.*' => 'string|max:50',
        ]);

        $validated['features'] = array_values($validated['features'] ?? []);

        $table = Table::findOrFail($id);
        $table->update($validated);
        
        if ($request->has('regenerate_qr')) {
            $table->qr_token = Str::random(32);
            $table->access_code = strtoupper(Str::random(6));
            
            $serverIp = $request->ip() === '127.0.0.1' || $request->ip() === '::1' 
                ? gethostbyname(gethostname()) 
                : $request->server('SERVER_ADDR');
            
            $table->qr_code_url = 'http://' . $serverIp . ':' . $request->server('SERVER_PORT', '8000') . '/table/' . $table->qr_token;
            $table->save();
        }

        return redirect()->route('admin.tables.index')
            ->with('success', 'Table updated successfully');
    }

    /**
     * Remove the specified table
     */
    public function destroy(int $id)
    {
        $table = Table::findOrFail($id);
        $table->delete();

        return redirect()->route('admin.tables.index')
            ->with('success', 'Table deleted successfully');
    }

    /**
     * Helper methods
     */
    private function getLocationColor(string $location): string
    {
        return match($location) {
            'Indoor' => 'bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-400',
            'Outdoor' => 'bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400',
            'Patio' => 'bg-purple-100 dark:bg-purple-900/20 text-purple-700 dark:text-purple-400',
            'Bar' => 'bg-amber-100 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400',
            default => 'bg-gray-100 dark:bg-gray-900/20 text-gray-700 dark:text-gray-400',
        };
    }

    private function getStatusColor(string $status): string
    {
        return match($status) {
            'available' => 'bg-green-200 dark:bg-green-800 border-green-200 dark:border-green-800',
            'partial' => 'bg-yellow-200 dark:bg-yellow-800 border-yellow-200 dark:border-yellow-800',
            'full' => 'bg-red-200 dark:bg-red-800 border-red-200 dark:border-red-800',
            'cleaning' => 'bg-blue-200 dark:bg-blue-800 border-blue-200 dark:border-blue-800',
            'reserved' => 'bg-purple-200 dark:bg-purple-800 border-purple-200 dark:border-purple-800',
            'out_of_service' => 'bg-gray-200 dark:bg-gray-800 border-gray-200 dark:border-gray-800',
            default => 'bg-gray-200 dark:bg-gray-800 border-gray-200 dark:border-gray-800',
        };
    }

    private function getStatusText(string $status): string
    {
        return match($status) {
            'available' => 'Available',
            'partial' => 'Partial',
            'full' => 'Full',
            'cleaning' => 'Cleaning',
            'reserved' => 'Reserved',
            'out_of_service' => 'Out of Service',
            default => ucfirst($status),
        };
    }

    private function getStatusDot(string $status): string
    {
        return match($status) {
            'available' => 'bg-green-500',
            'partial' => 'bg-yellow-500',
            'full' => 'bg-red-500',
            'cleaning' => 'bg-blue-500',
            'reserved' => 'bg-purple-500',
            'out_of_service' => 'bg-gray-500',
            default => 'bg-gray-500',
        };
    }

    private function getDeviceCount($sessions, int $tableId): int
    {
        return $sessions->where('table_id', $tableId)
            ->where('status', 'active')
            ->count();
    }

    private function getCurrentDevice($sessions, int $tableId, int $sessionId): int
    {
        $tableSessions = $sessions->where('table_id', $tableId)
            ->where('status', 'active')
            ->sortBy('started_at')
            ->values();

        // Simplified approach - just return the count + 1
        return $tableSessions->count() + 1;
    }

    private function formatDuration(string $startedAt): string
    {
        $start = new \DateTime($startedAt);
        $now = new \DateTime();
        $diff = $start->diff($now);

        if ($diff->h > 0) {
            return $diff->h . ' hr ' . $diff->i . ' min';
        }

        return $diff->i . ' min';
    }

    private function formatTime(string $time): string
    {
        $dt = new \DateTime($time);
        return $dt->format('g:i A');
    }

    private function formatLastActivity(string $time): string
    {
        $dt = new \DateTime($time);
        $now = new \DateTime();
        $diff = $dt->diff($now);

        if ($diff->i < 1) {
            return $diff->s . ' sec ago';
        }

        if ($diff->h < 1) {
            return $diff->i . ' min ago';
        }

        return $diff->h . ' hr ago';
    }
}
