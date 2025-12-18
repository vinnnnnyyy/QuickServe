<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Table;
use App\Models\TableSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TableSessionController extends Controller
{
    /**
     * Get all table sessions
     */
    public function index(): JsonResponse
    {
        $sessions = TableSession::all();
        return response()->json($sessions);
    }

    /**
     * Get active sessions only
     */
    public function active(): JsonResponse
    {
        $active = TableSession::whereIn('status', ['active', 'paid_leaving'])->get();

        return response()->json($active);
    }

    /**
     * Get sessions for a specific table
     */
    public function byTable(int $tableId): JsonResponse
    {
        $tableSessions = TableSession::where('table_id', $tableId)->get();

        return response()->json($tableSessions);
    }

    /**
     * Create a new session
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'table_id' => 'required|integer|exists:tables,id',
            'device_ip' => 'nullable|string',
            'browser' => 'nullable|string',
            'user_agent' => 'nullable|string',
        ]);

        $data = $request->all();

        $maxId = TableSession::max('id') ?? 0;
        $data['session_id'] = '#' . (1247 + $maxId);
        $data['status'] = 'active';
        $data['started_at'] = now();
        $data['last_activity_at'] = now();
        $data['network_name'] = $data['network_name'] ?? 'CafeOrder_WiFi';
        $data['current_activity'] = $data['current_activity'] ?? 'Browsing menu';
        
        if (!isset($data['device_ip'])) {
            $data['device_ip'] = $request->ip();
        }
        
        $userAgent = $data['user_agent'] ?? $request->userAgent();
        $data['user_agent'] = $userAgent;
        $data['device_type'] = $this->detectDeviceType($userAgent);
        $data['browser'] = $data['browser'] ?? $this->detectBrowser($userAgent);

        $session = TableSession::create($data);

        $this->updateTableOccupancy($session->table_id);

        return response()->json($session, 201);
    }
    
    /**
     * Detect device type from user agent
     */
    private function detectDeviceType(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Unknown';
        }
        
        $userAgent = strtolower($userAgent);
        
        if (preg_match('/mobile|android.*mobile|iphone|ipod|blackberry|iemobile|opera mini|opera mobi/i', $userAgent)) {
            return 'Mobile';
        }
        
        if (preg_match('/tablet|ipad|playbook|silk|android(?!.*mobile)/i', $userAgent)) {
            return 'Tablet';
        }
        
        return 'Desktop';
    }
    
    /**
     * Detect browser from user agent
     */
    private function detectBrowser(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Unknown';
        }
        
        if (preg_match('/edg/i', $userAgent)) {
            return 'Microsoft Edge';
        }
        if (preg_match('/opr|opera/i', $userAgent)) {
            return 'Opera';
        }
        if (preg_match('/chrome|crios/i', $userAgent)) {
            return 'Chrome';
        }
        if (preg_match('/firefox|fxios/i', $userAgent)) {
            return 'Firefox';
        }
        if (preg_match('/safari/i', $userAgent) && !preg_match('/chrome|crios/i', $userAgent)) {
            return 'Safari';
        }
        if (preg_match('/msie|trident/i', $userAgent)) {
            return 'Internet Explorer';
        }
        
        return 'Unknown';
    }

    /**
     * Show a specific session
     */
    public function show(string $sessionId): JsonResponse
    {
        $session = TableSession::where('session_id', $sessionId)->first();

        if (!$session) {
            return response()->json(['message' => 'Session not found'], 404);
        }

        return response()->json($session);
    }

    /**
     * Update session activity
     */
    public function updateActivity(Request $request, string $sessionId): JsonResponse
    {
        $request->validate([
            'activity' => 'required|string',
        ]);

        $session = TableSession::where('session_id', $sessionId)->first();

        if (!$session) {
            return response()->json(['message' => 'Session not found'], 404);
        }

        $session->update([
            'current_activity' => $request->activity,
            'last_activity_at' => now(),
        ]);

        return response()->json($session);
    }

    /**
     * Mark session payment as completed
     */
    public function completePayment(string $sessionId): JsonResponse
    {
        $session = TableSession::where('session_id', $sessionId)->first();

        if (!$session) {
            return response()->json(['message' => 'Session not found'], 404);
        }

        $session->update([
            'payment_completed' => true,
            'payment_completed_at' => now(),
            'status' => 'paid_leaving',
        ]);

        return response()->json($session);
    }

    /**
     * End a session
     */
    public function end(string $sessionId): JsonResponse
    {
        $session = TableSession::where('session_id', $sessionId)->first();

        if (!$session) {
            return response()->json(['message' => 'Session not found'], 404);
        }

        $session->update([
            'status' => 'terminated',
            'ended_at' => now(),
        ]);

        $this->updateTableOccupancy($session->table_id);

        return response()->json(['message' => 'Session ended successfully', 'session' => $session]);
    }

    /**
     * Release a table (for paid_leaving sessions)
     */
    public function release(string $sessionId): JsonResponse
    {
        return $this->end($sessionId);
    }

    /**
     * Delete a session
     */
    public function destroy(string $sessionId): JsonResponse
    {
        $session = TableSession::where('session_id', $sessionId)->first();

        if (!$session) {
            return response()->json(['message' => 'Session not found'], 404);
        }

        $tableId = $session->table_id;
        $session->delete();

        $this->updateTableOccupancy($tableId);

        return response()->json(['message' => 'Session deleted successfully']);
    }

    /**
     * Initialize a Host Session
     */
    public function initHost(Request $request): JsonResponse
    {
        $request->validate([
            'table_id' => 'required|integer|exists:tables,id',
            'customer_name' => 'nullable|string'
        ]);

        $deviceId = \App\Services\DeviceIdentifierService::getOrCreate($request);
        $tableId = $request->table_id;

        // Check for existing session at this table
        $existingSession = TableSession::where('table_id', $tableId)
            ->whereIn('status', ['active', 'paid_leaving'])
            ->first();

        if ($existingSession) {
            // If session exists, we can't create a new host session unless it's the same host recovering it
            if ($existingSession->host_device_id === $deviceId) {
                 return response()->json([
                     'message' => 'Resuming hosting session',
                     'session' => $existingSession,
                     'is_host' => true
                 ]);
            }
            return response()->json(['message' => 'A session is already active. Please join instead.'], 409);
        }

        // Create new Host Session
        $session = new TableSession();
        $session->table_id = $tableId;
        $session->host_device_id = $deviceId;
        $session->session_id = '#' . (1247 + (TableSession::max('id') ?? 0));
        $session->users = [[
            'device_id' => $deviceId,
            'name' => $request->customer_name ?? 'Host',
            'role' => 'host',
            'status' => 'approved',
            'joined_at' => now()->toISOString()
        ]];
        $session->metadata = ['payment_mode' => 'host', 'customer_type' => 'group'];
        $session->status = 'active';
        $session->started_at = now();
        $session->last_activity_at = now();
        $session->save();

        // Update Table
        $this->updateTableOccupancy($tableId);
        
        // Set User Session
        session(['table_id' => $tableId, 'device_id' => $deviceId]);

        return response()->json([
            'session' => $session,
            'is_host' => true
        ], 201);
    }

    /**
     * Request to Join an Existing Group
     */
    public function joinRequest(Request $request): JsonResponse
    {
        $request->validate([
            'table_id' => 'required|integer',
            'customer_name' => 'required|string',
        ]);

        $deviceId = \App\Services\DeviceIdentifierService::getOrCreate($request);
        $tableId = $request->table_id;

        $session = TableSession::where('table_id', $tableId)
            ->where('status', 'active')
            ->first();

        if (!$session) {
            return response()->json(['message' => 'No active session found at this table.'], 404);
        }

        // Check if already joined
        $users = $session->users ?? [];
        $existingUserIndex = collect($users)->search(fn($u) => $u['device_id'] === $deviceId);

        if ($existingUserIndex !== false) {
             $user = $users[$existingUserIndex];
             // If already approved, return success
             if ($user['status'] === 'approved') {
                 session(['table_id' => $tableId, 'device_id' => $deviceId]);
                 return response()->json(['status' => 'approved', 'session' => $session]);
             }
             return response()->json(['status' => 'pending']);
        }

        // Add to users list as pending
        $users[] = [
            'device_id' => $deviceId,
            'name' => $request->customer_name,
            'role' => 'guest',
            'status' => 'pending',
            'joined_at' => now()->toISOString()
        ];

        $session->users = $users;
        $session->save();

        return response()->json(['status' => 'pending']);
    }

    /**
     * Approve or Reject a Guest (Host Only)
     */
    public function handleGuestRequest(Request $request): JsonResponse
    {
        $request->validate([
            'table_id' => 'required',
            'target_device_id' => 'required',
            'action' => 'required|in:approve,reject'
        ]);

        $hostDeviceId = \App\Services\DeviceIdentifierService::getOrCreate($request); // Current requester
        $session = TableSession::where('table_id', $request->table_id)->where('status', 'active')->first();

        if (!$session) return response()->json(['message' => 'Session not found'], 404);
        
        if ($session->host_device_id !== $hostDeviceId) {
            return response()->json(['message' => 'Unauthorized. Only the host can manage guests.'], 403);
        }

        $users = $session->users;
        $targetIndex = collect($users)->search(fn($u) => $u['device_id'] === $request->target_device_id);

        if ($targetIndex === false) return response()->json(['message' => 'Guest request not found'], 404);

        if ($request->action === 'approve') {
            $users[$targetIndex]['status'] = 'approved';
        } else {
            // Remove from array if rejected
            array_splice($users, $targetIndex, 1);
        }

        $session->users = $users;
        $session->save();

        return response()->json(['users' => $users]);
    }

    /**
     * Polling Status for Session Updates
     */
    public function getSessionStatus(Request $request, $tableId): JsonResponse
    {
         $deviceId = \App\Services\DeviceIdentifierService::getOrCreate($request);
         $session = TableSession::where('table_id', $tableId)
            ->where('status', 'active')
            ->first();

        if (!$session) return response()->json(['status' => 'ended']);

        // Identify requester
        \Illuminate\Support\Facades\Log::info("getSessionStatus Check: Device [$deviceId] vs Host [{$session->host_device_id}]");

        $isHost = $session->host_device_id === $deviceId;
        $currentUser = collect($session->users)->firstWhere('device_id', $deviceId);
        
        $myStatus = $currentUser ? $currentUser['status'] : 'unknown';

        return response()->json([
            'is_host' => $isHost,
            'my_status' => $myStatus,
            'users' => $session->users,
            'payment_mode' => $session->metadata['payment_mode'] ?? 'host',
            'cart_count' => \App\Models\CartItem::where('table_id', $tableId)->count() // Optimization: Just return count
        ]);
    }

    /**
     * Update Session Settings (Host Only)
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $request->validate([
            'table_id' => 'required|integer',
            'payment_mode' => 'nullable|in:host,individual',
        ]);

        $hostDeviceId = \App\Services\DeviceIdentifierService::getOrCreate($request);
        $session = TableSession::where('table_id', $request->table_id)
            ->where('status', 'active')
            ->first();

        if (!$session) {
            return response()->json(['message' => 'Session not found'], 404);
        }

        if ($session->host_device_id !== $hostDeviceId) {
            return response()->json(['message' => 'Unauthorized. Only the host can update settings.'], 403);
        }

        $metadata = $session->metadata ?? [];

        if ($request->has('payment_mode')) {
            $metadata['payment_mode'] = $request->payment_mode;
        }

        $session->metadata = $metadata;
        $session->save();

        return response()->json([
            'message' => 'Settings updated',
            'metadata' => $session->metadata
        ]);
    }

    /**
     * Disconnect current device session
     */
    public function disconnectCurrentDevice(Request $request): JsonResponse
    {
        $deviceIp = $request->ip();
        $tableId = session('table_id');
        $deviceId = \App\Services\DeviceIdentifierService::getOrCreate($request);

        if (!$tableId) {
            return response()->json(['message' => 'No active table session'], 404);
        }

        $session = TableSession::where('table_id', $tableId)
            ->whereIn('status', ['active', 'paid_leaving'])
            ->first();

        if (!$session) {
            return response()->json(['message' => 'Session not found'], 404);
        }

        // If I am the host, end the session? Or transfer?
        // For now, if Host leaves, session ends.
        if ($session->host_device_id === $deviceId) {
            $session->status = 'terminated';
            $session->ended_at = now();
            $session->save();
             $this->updateTableOccupancy($tableId);
        } else {
            // Just remove me from users list
            $users = $session->users ?? [];
            $myIndex = collect($users)->search(fn($u) => $u['device_id'] === $deviceId);
            if ($myIndex !== false) {
                array_splice($users, $myIndex, 1);
                $session->users = $users;
                $session->save();
            }
        }

        session()->forget(['table_id', 'table_number', 'device_id']);

        return response()->json(['message' => 'Disconnected successfully']);
    }

    /**
     * Expire stale sessions (inactive for more than X minutes)
     */
    public function expireStaleSessions(Request $request): JsonResponse
    {
        $minutesThreshold = (int) $request->input('minutes', 30);
        
        $expiredSessions = TableSession::whereIn('status', ['active', 'paid_leaving'])
            ->where('last_activity_at', '<', now()->subMinutes($minutesThreshold))
            ->get();

        $expiredCount = 0;
        $affectedTables = [];

        foreach ($expiredSessions as $session) {
            $session->update([
                'status' => 'expired',
                'ended_at' => now(),
            ]);
            $affectedTables[$session->table_id] = true;
            $expiredCount++;
        }

        foreach (array_keys($affectedTables) as $tableId) {
            $this->updateTableOccupancy($tableId);
        }

        return response()->json([
            'message' => "Expired {$expiredCount} stale sessions",
            'expired_count' => $expiredCount,
        ]);
    }

    /**
     * Clear all sessions for a table
     */
    public function clearTableSessions(int $tableId): JsonResponse
    {
        $count = TableSession::where('table_id', $tableId)
            ->whereIn('status', ['active', 'paid_leaving'])
            ->update([
                'status' => 'terminated',
                'ended_at' => now(),
            ]);

        $this->updateTableOccupancy($tableId);

        return response()->json(['message' => "Cleared {$count} sessions"]);
    }

    /**
     * Helper: Update table occupancy and status
     */
    private function updateTableOccupancy(int $tableId): void
    {
        $activeSessions = TableSession::where('table_id', $tableId)
            ->whereIn('status', ['active', 'paid_leaving'])
            ->count();

        $table = Table::find($tableId);

        if (!$table) {
            return;
        }

        $table->occupied = $activeSessions;
        $table->updateStatusFromOccupancy();
    }
}
