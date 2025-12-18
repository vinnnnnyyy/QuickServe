<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $staffRole = null;
        
        if ($user) {
            $staff = $user->staff;
            $staffRole = $staff ? strtolower($staff->role) : null;
        }
        
        // Helper to get active session data
        $tableSession = null;
        if (session('table_id')) {
            $tableSession = \App\Models\TableSession::where('table_id', session('table_id'))->first();
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'staffRole' => $staffRole,
            ],
            'tableSession' => $tableSession ? [
                'id' => $tableSession->session_id,
                'customer_type' => $tableSession->metadata['customer_type'] ?? null,
                'payment_mode' => $tableSession->metadata['payment_mode'] ?? null,
            ] : null,
            'sessionId' => session()->getId(), // Ensure this matches heartbeat logic
        ];
    }
}
