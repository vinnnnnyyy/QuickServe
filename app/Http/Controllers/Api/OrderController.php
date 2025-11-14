<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\JsonStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    private JsonStorageService $storage;

    public function __construct(JsonStorageService $storage)
    {
        $this->storage = $storage;
        $this->initializeSampleData();
    }

    private function initializeSampleData(): void
    {
        $orders = $this->storage->get('orders');
        
        if ($orders->isEmpty()) {
            $sampleOrders = [
                [
                    'id' => 1,
                    'order_number' => 'ORD-001',
                    'customer_name' => 'John Smith',
                    'table_number' => 5,
                    'items' => [
                        ['id' => 1, 'name' => 'Iced Brown Sugar Latte', 'quantity' => 2, 'price' => 5.50],
                        ['id' => 2, 'name' => 'Blueberry Muffin', 'quantity' => 1, 'price' => 4.25]
                    ],
                    'total' => 15.25,
                    'status' => 'preparing',
                    'notes' => 'Extra hot',
                    'created_at' => now()->toISOString(),
                    'updated_at' => now()->toISOString()
                ]
            ];

            $this->storage->put('orders', collect($sampleOrders));
        }
    }

    public function index(): JsonResponse
    {
        $orders = $this->storage->get('orders');
        return response()->json($orders);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            // Handle both old format (admin) and new format (checkout)
            if ($request->has('customer_name')) {
            // Old admin format
            $request->validate([
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

            $data = $request->all();
            $data['order_number'] = 'ORD-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $data['status'] = 'pending';
        } else {
            // New checkout format
            $request->validate([
                'id' => 'required|string',
                'referenceNumber' => 'required|string',
                'customer' => 'required|array',
                'customer.nickname' => 'nullable|string',
                'customer.notes' => 'nullable|string',
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

            $data = $request->all();
            
            // Convert checkout format to storage format
            $storageData = [
                'id' => $data['id'],
                'order_number' => $data['referenceNumber'],
                'customer_name' => $data['customer']['nickname'] ?? 'Customer',
                'table_number' => $data['tableNumber'] ?? 'Table 1',
                'items' => collect($data['items'])->map(function($item) {
                    return [
                        'id' => $item['id'],
                        'name' => $item['name'],
                        'quantity' => $item['quantity'],
                        'price' => $item['displayPrice'] ?? $item['finalPrice'] ?? $item['price'] ?? 0,
                        'isCustomized' => $item['isCustomized'] ?? false
                    ];
                })->toArray(),
                'total' => $data['total'],
                'payment_method' => $data['paymentMethod'],
                'status' => $data['status'],
                'notes' => $data['customer']['notes'] ?? null,
                'created_at' => $data['createdAt'] ?? now()->toISOString(),
                'updated_at' => $data['updatedAt'] ?? now()->toISOString(),
                // Store original checkout data for reference
                'original_data' => $data
            ];
            
            $data = $storageData;
        }

        if (!isset($data['order_number'])) {
            $data['order_number'] = 'ORD-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }
        if (!isset($data['status'])) {
            $data['status'] = 'pending';
        }

        $order = $this->storage->create('orders', $data);
        
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
        $order = $this->storage->find('orders', $id);
        
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }
        
        return response()->json($order);
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

        $order = $this->storage->update('orders', $id, ['status' => $request->status]);
        
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }
        
        return response()->json($order);
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
