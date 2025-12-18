<?php

namespace App\Http\Middleware;

use App\Models\DeviceSession;
use App\Models\Table;
use App\Services\DeviceIdentifierService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class TableDeviceContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $tableId = $this->resolveTableId($request);
        $deviceId = DeviceIdentifierService::getOrCreate($request);
        
        // Allow my-orders and cancellation to proceed without strict table context (history actions)
        $isHistoryView = $request->routeIs('api.orders.myOrders') || $request->routeIs('api.orders.cancel');

        if (!$tableId && !$isHistoryView) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Table context required. Please scan the QR code.'], 403);
            }
            return redirect()->route('scan.qr');
        }

        if (!$tableId && $isHistoryView) {
            // Minimal context for history view
            $request->attributes->set('device_id', $deviceId);
             if (!$request->cookie(DeviceIdentifierService::COOKIE_NAME)) {
                $cookie = DeviceIdentifierService::createCookie($deviceId);
                $response = $next($request);
                $response->headers->setCookie($cookie);
                return $response;
            }
            return $next($request);
        }

        $table = Table::find($tableId);
        if (!$table || !$table->is_active) {
            // Clear stale session
            session()->forget(['table_id', 'table_number', 'device_id']);
            
            // If history view, proceed without table context
            if ($isHistoryView) {
                $request->attributes->set('device_id', $deviceId);
                return $next($request);
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Invalid or inactive table.'], 403);
            }
            return redirect()->route('scan.qr');
        }

        $deviceId = DeviceIdentifierService::getOrCreate($request);
        
        session([
            'table_id' => $table->id,
            'table_number' => $table->number,
            'device_id' => $deviceId,
        ]);

        $request->attributes->set('table_id', $table->id);
        $request->attributes->set('table_number', $table->number);
        $request->attributes->set('device_id', $deviceId);

        $this->recordDeviceSession($request, $table, $deviceId);

        $response = $next($request);

        if (!$request->cookie(DeviceIdentifierService::COOKIE_NAME)) {
            $cookie = DeviceIdentifierService::createCookie($deviceId);
            $response->headers->setCookie($cookie);
        }

        return $response;
    }

    private function resolveTableId(Request $request): ?int
    {
        if ($request->has('table_id')) {
            return (int) $request->input('table_id');
        }

        if (session()->has('table_id')) {
            return (int) session('table_id');
        }

        return null;
    }

    private function recordDeviceSession(Request $request, Table $table, string $deviceId): void
    {
        $userAgent = $request->userAgent() ?? 'unknown';
        
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

        DeviceSession::updateOrCreate(
            [
                'device_id' => $deviceId,
                'table_id' => $table->id,
            ],
            [
                'user_agent' => substr($userAgent, 0, 512),
                'device_ip' => $request->ip(),
                'device_type' => $deviceType,
                'browser' => $browser,
                'last_seen_at' => now(),
            ]
        );
    }
}
