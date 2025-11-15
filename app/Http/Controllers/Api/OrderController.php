<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\JsonStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    private JsonStorageService $storage;

    public function __construct(JsonStorageService $storage)
    {
        $this->storage = $storage;
        // Sample data initialization removed - no longer needed
    }



    public function index(): JsonResponse
    {
        // Try to get from database first, then check JSON storage
        $dbOrders = collect([]);
        $jsonOrders = collect([]);
        
        try {
            $dbOrders = Order::orderBy('created_at', 'desc')->get();
        } catch (\Exception $e) {
            // Database might not be available
        }
        
        // Also get from JSON storage for backward compatibility
        try {
            $jsonOrders = $this->storage->get('orders');
        } catch (\Exception $e) {
            // JSON storage might not have orders
        }
        
        // Combine both sources (database orders take precedence)
        $allOrders = $dbOrders->concat($jsonOrders)->unique('id')->sortByDesc('created_at')->values();
        
        return response()->json($allOrders);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $orderData = [];
            
            // Handle both old format (admin) and new format (checkout)
            if ($request->has('customer_name')) {
                // Old admin format
                $validated = $request->validate([
                    'customer_name' => 'required|string|max:255',
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
                    'table_number' => $validated['table_number'] ?? 'Table 1',
                    'items' => $validated['items'],
                    'total' => (int)($validated['total'] * 100), // Convert to cents
                    'subtotal' => (int)($validated['total'] * 100),
                    'status' => 'received',
                    'payment_status' => 'unpaid',
                    'notes' => $validated['notes'] ?? null,
                    'order_type' => 'dine_in',
                ];
            } else {
                // New checkout format
                $validated = $request->validate([
                    'id' => 'required|string',
                    'referenceNumber' => 'required|string',
                    'customer' => 'required|array',
                    'customer.nickname' => 'nullable|string',
                    'customer.notes' => 'nullable|string',
                    'customer.tableNumber' => 'nullable|string',
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
                    'status' => 'required|string'
                ]);
                
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

                $orderData = [
                    'order_number' => Order::generateOrderNumber(),
                    'reference_number' => $validated['referenceNumber'],
                    'customer_nickname' => $validated['customer']['nickname'] ?? 'Customer',
                    'customer_notes' => $validated['customer']['notes'] ?? null,
                    'table_number' => $validated['customer']['tableNumber'] ?? $validated['tableNumber'] ?? 'Table 1',
                    'items' => $items,
                    'total' => (int)($validated['total'] * 100), // Convert to cents
                    'subtotal' => (int)($validated['total'] * 100),
                    'payment_method' => $validated['paymentMethod'],
                    'payment_status' => $validated['paymentMethod'] === 'cash' ? 'unpaid' : 'pending',
                    'status' => $validated['status'],
                    'order_type' => 'dine_in',
                    'device_ip' => $request->ip(),
                    'device_type' => $request->header('User-Agent'),
                    'session_id' => session()->getId(),
                    'original_data' => $validated,
                ];
            }

            // Try to save to database, fallback to JSON storage
            try {
                $order = Order::create($orderData);
                return response()->json($order, 201);
            } catch (\Exception $dbError) {
                // Fallback to JSON storage
                $jsonData = $orderData;
                $jsonData['total'] = $orderData['total'] / 100; // Convert back to decimal
                $jsonData['subtotal'] = $orderData['subtotal'] / 100;
                
                $order = $this->storage->create('orders', $jsonData);
                return response()->json($order, 201);
            }
            
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
        try {
            $order = Order::findOrFail($id);
            return response()->json($order);
        } catch (\Exception $e) {
            // Fallback to JSON storage
            $order = $this->storage->find('orders', $id);
            
            if (!$order) {
                return response()->json(['message' => 'Order not found'], 404);
            }
            
            return response()->json($order);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $order = $this->storage->update('orders', $id, $request->all());
        
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }
        
        return response()->json($order);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:received,confirmed,queued,preparing,ready,served,completed,cancelled'
        ]);

        try {
            $order = Order::findOrFail($id);
            $order->updateStatus($request->status);
            return response()->json($order);
        } catch (\Exception $e) {
            // Fallback to JSON storage
            $order = $this->storage->update('orders', $id, ['status' => $request->status]);
            
            if (!$order) {
                return response()->json(['message' => 'Order not found'], 404);
            }
            
            return response()->json($order);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->storage->delete('orders', $id);
        
        if (!$deleted) {
            return response()->json(['message' => 'Order not found'], 404);
        }
        
        return response()->json(['message' => 'Order deleted successfully']);
    }
}
