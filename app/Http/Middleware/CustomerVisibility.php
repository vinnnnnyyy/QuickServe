<?php

namespace App\Http\Middleware;

use App\Models\Order;
use App\Services\DeviceIdentifierService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerVisibility
{
    public function handle(Request $request, Closure $next): Response
    {
        $deviceId = $request->cookie('qs_device_id') 
            ?? $request->attributes->get('device_id')
            ?? session('device_id');
        $tableId = $request->attributes->get('table_id') ?? session('table_id');

        // Allow my-orders and cancellation to proceed without table context (history view)
        $isHistoryView = $request->routeIs('api.orders.myOrders') || $request->routeIs('api.orders.cancel');

        if (!$deviceId || (!$isHistoryView && !$tableId)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Device context required.'], 403);
            }
            return redirect()->route('scan.qr');
        }

        if ($orderId = $request->route('id') ?? $request->route('orderId')) {
            if ($this->isOrderRoute($request->route()?->getName(), $request->path())) {
                $order = Order::find($orderId);
                
                if ($order && !$this->canAccessOrder($order, $deviceId, $tableId)) {
                    if ($request->expectsJson()) {
                        return response()->json(['message' => 'You cannot access this order.'], 403);
                    }
                    return redirect()->route('order.history');
                }
            }
        }

        return $next($request);
    }

    private function isOrderRoute(?string $routeName, string $path): bool
    {
        if ($routeName && str_starts_with($routeName, 'order.')) {
            return true;
        }

        if ($routeName && str_starts_with($routeName, 'api.orders.')) {
            return true;
        }

        return str_contains($path, 'orders/') || str_contains($path, 'order/');
    }

    private function canAccessOrder(Order $order, string $deviceId, ?int $tableId): bool
    {
        // If isolated device check, table doesn't matter as much (or check if order belongs to device)
        return $order->device_id === $deviceId;
    }
}
