<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\DeviceIdentifierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(): JsonResponse
    {
        $orders = Order::with('table:id,number,location')->orderByDesc('created_at')->get();

        return response()->json($orders);
    }

    public function myOrders(Request $request): JsonResponse
    {
        $deviceId = $request->attributes->get('device_id') ?? session('device_id');
        $tableId = $request->attributes->get('table_id') ?? session('table_id');
        
        if (!$deviceId) {
            $deviceId = DeviceIdentifierService::getOrCreate($request);
        }
        
        $sessionId = session()->getId();

        
        $query = Order::with('table:id,number,location')
            ->where('device_id', $deviceId)
            ->orderByDesc('created_at');
        
        
        $orders = $query->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'reference_number' => $order->reference_number,
                    'customer_nickname' => $order->customer_nickname,
                    'table_number' => $order->table_number,
                    'items' => $order->items,
                    'total' => $order->total,
                    'total_formatted' => $order->total_decimal,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'payment_method' => $order->payment_method,
                    'can_cancel' => $order->canBeCancelled(),
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                ];
            });

        return response()->json($orders);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $deviceId = $request->attributes->get('device_id') ?? session('device_id');
        $tableId = $request->attributes->get('table_id') ?? session('table_id');
        
        if (!$deviceId) {
            $deviceId = DeviceIdentifierService::getOrCreate($request);
        }
        
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($order->device_id !== $deviceId) {
            // Fallback: Check if the order's session_id matches current session
            // This handles cases where device_id might rotate or be inconsistent but session persists
            if ($order->session_id !== session()->getId()) {
                 return response()->json(['message' => 'Unauthorized to cancel this order'], 403);
            }
        }

        // Allow cancel if status is 'received' (unconfirmed) or 'unpaid'
        if (!in_array($order->status, ['received', 'unpaid'])) {
            return response()->json([
                'message' => 'Order cannot be cancelled. It has already been confirmed.'
            ], 400);
        }

        $order->updateStatus('cancelled');

        return response()->json([
            'message' => 'Order cancelled successfully',
            'order' => $order
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $orderData = [];
            $deviceId = $request->attributes->get('device_id') ?? session('device_id');
            $tableId = $request->attributes->get('table_id') ?? session('table_id');
            
            if (!$deviceId) {
                $deviceId = DeviceIdentifierService::getOrCreate($request);
            }
            
            // Handle both old format (admin) and new format (checkout)
            if ($request->has('customer_name')) {
                // Old admin format
                $validated = $request->validate([
                    'customer_name' => 'required|string|max:255',
                    'table_id' => 'nullable|integer|exists:tables,id',
                    'table_number' => 'nullable|integer',
                    'items' => 'required|array',
                    'items.*.id' => 'required|integer',
                    'items.*.name' => 'required|string',
                    'items.*.quantity' => 'required|integer|min:1',
                    'items.*.price' => 'required|numeric|min:0',
                    'total' => 'required|numeric|min:0',
                    'notes' => 'nullable|string'
                ]);

                $orderData = [
                    'order_number' => Order::generateOrderNumber(),
                    'customer_name' => $validated['customer_name'],
                    'table_id' => $validated['table_id'] ?? $tableId,
                    'table_number' => $validated['table_number'] ?? 'Table 1',
                    'items' => $validated['items'],
                    'total' => (int)($validated['total'] * 100),
                    'subtotal' => (int)($validated['total'] * 100),
                    'status' => 'received',
                    'payment_status' => 'unpaid',
                    'notes' => $validated['notes'] ?? null,
                    'order_type' => 'dine_in',
                    'device_id' => $deviceId,
                    'device_ip' => $request->ip(),
                    'device_type' => $request->header('User-Agent'),
                    'session_id' => session()->getId(),
                ];
            } else {
                // New checkout format (customer orders)
                $validated = $request->validate([
                    'id' => 'required|string',
                    'referenceNumber' => 'required|string',
                    'customer' => 'required|array',
                    'customer.nickname' => 'nullable|string',
                    'customer.notes' => 'nullable|string',
                    'customer.tableNumber' => 'nullable|string',
                    'customer.tableId' => 'nullable|integer|exists:tables,id',
                    'items' => 'required|array',
                    'items.*.id' => 'required',
                    'items.*.name' => 'required|string',
                    'items.*.quantity' => 'required|integer|min:1',
                    'items.*.price' => 'nullable|numeric|min:0',
                    'items.*.finalPrice' => 'nullable|numeric|min:0',
                    'items.*.displayPrice' => 'nullable|numeric|min:0',
                    'total' => 'required|numeric|min:0',
                    'paymentMethod' => 'required|string',
                    'tableNumber' => 'nullable|string',
                    'tableId' => 'nullable|integer|exists:tables,id',
                    'status' => 'nullable|string'
                ]);
                
                $sessionId = session()->getId();
                $deviceIp = $request->ip();
                
                // Map checkout items to order items
                $items = collect($validated['items'])->map(function($item) {
                    return [
                        'id' => $item['id'],
                        'name' => $item['name'],
                        'quantity' => $item['quantity'],
                        'price' => $item['displayPrice'] ?? $item['finalPrice'] ?? $item['price'] ?? 0,
                        'isCustomized' => $item['isCustomized'] ?? false
                    ];
                })->toArray();

                // Map checkout payment status into our payment_status enum
                $rawStatus = $validated['status'] ?? null;
                if ($rawStatus === 'paid') {
                    $paymentStatus = 'paid';
                } elseif ($rawStatus === 'payment_failed') {
                    $paymentStatus = 'failed';
                } else {
                    $paymentStatus = $validated['paymentMethod'] === 'cash' ? 'unpaid' : 'pending';
                }

                $workflowStatus = 'received';

                // Check for active table session to determine group context
                $tableSession = null;
                if ($tableId) {
                    $tableSession = \App\Models\TableSession::where('table_id', $tableId)
                        ->where('status', 'active')
                        ->first();
                }

                $isGroupOrder = false;
                $paymentMode = $validated['paymentMethod'] ?? null;
                
                if ($tableSession && isset($tableSession->metadata['payment_mode'])) {
                     if ($tableSession->metadata['payment_mode'] === 'host') {
                         $isGroupOrder = true;
                         $paymentMode = 'host';
                     }
                }

                $orderData = [
                    'order_number' => Order::generateOrderNumber(),
                    'reference_number' => $validated['referenceNumber'],
                    'customer_nickname' => $validated['customer']['nickname'] ?? 'Customer',
                    'customer_notes' => $validated['customer']['notes'] ?? null,
                    'table_id' => $validated['customer']['tableId'] ?? $validated['tableId'] ?? $tableId,
                    'table_number' => $validated['customer']['tableNumber'] ?? $validated['tableNumber'] ?? 'Table 1',
                    'items' => $items,
                    'total' => (int)($validated['total'] * 100),
                    'subtotal' => (int)($validated['total'] * 100),
                    'payment_method' => $validated['paymentMethod'],
                    'payment_status' => $paymentStatus,
                    'status' => $workflowStatus,
                    'order_type' => 'dine_in',
                    'device_id' => $deviceId,
                    'device_ip' => $deviceIp,
                    'device_type' => $request->header('User-Agent'),
                    'session_id' => $sessionId,
                    'original_data' => array_merge($validated, [
                        'is_group_order' => $isGroupOrder,
                        'payment_mode' => $paymentMode
                    ]),
                ];
            }

            $order = Order::create($orderData);

            // Deduct Inventory Stock
            foreach ($orderData['items'] as $item) {
                 $menuItem = \App\Models\MenuItem::find($item['id']);
                 if ($menuItem) {
                     foreach ($menuItem->ingredients as $ingredient) {
                         // Quantity to deduct = (Quantity per item) * (Order Quantity)
                         $deduction = $ingredient->pivot->quantity;
                         if (isset($item['quantity'])) {
                             $deduction *= $item['quantity'];
                         }
                         
                         $ingredient->decrement('stock', $deduction);
                         
                         // Optional: Check if stock < 0 and warn (log for now)
                         if ($ingredient->stock < 0) {
                             // Log::warning("Inventory item {$ingredient->name} is negative: {$ingredient->stock}");
                         }
                     }
                 }
            }

            return response()->json($order, 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        $order = Order::with('table:id,number,location')->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json($order);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $order->update($request->all());

        return response()->json($order);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:received,confirmed,queued,preparing,ready,served,completed,cancelled'
        ]);

        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $order->updateStatus($request->status);

        return response()->json($order);
    }

    /**
     * Mark order as paid
     */
    public function markAsPaid(Request $request, int $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Optional: Validate payment details if you send them
        $paymentDetails = $request->input('payment_details', []);
        
        $order->markAsPaid($paymentDetails);

        // Auto-confirm if currently received
        if ($order->status === 'received') {
            $order->updateStatus('confirmed');
        }

        return response()->json($order);
    }

    public function destroy(int $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $order->delete();

        return response()->json(['message' => 'Order deleted successfully']);
    }
}
