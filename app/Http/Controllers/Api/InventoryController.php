<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\JsonStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InventoryController extends Controller
{
    private JsonStorageService $storage;

    public function __construct(JsonStorageService $storage)
    {
        $this->storage = $storage;
        $this->initializeSampleData();
    }

    private function initializeSampleData(): void
    {
        $inventory = $this->storage->get('inventory');
        
        if ($inventory->isEmpty()) {
            $sampleInventory = [
                [
                    'id' => 1,
                    'item_name' => 'Coffee Beans (Arabica)',
                    'category' => 'Beverages',
                    'current_stock' => 25,
                    'unit' => 'lbs',
                    'min_stock' => 10,
                    'max_stock' => 50,
                    'cost_per_unit' => 12.50,
                    'supplier' => 'Premium Coffee Co.',
                    'status' => 'in_stock',
                    'last_restocked' => '2024-01-10',
                    'created_at' => now()->toISOString(),
                    'updated_at' => now()->toISOString()
                ],
                [
                    'id' => 2,
                    'item_name' => 'Whole Milk',
                    'category' => 'Dairy',
                    'current_stock' => 8,
                    'unit' => 'gallons',
                    'min_stock' => 5,
                    'max_stock' => 20,
                    'cost_per_unit' => 4.25,
                    'supplier' => 'Local Dairy Farm',
                    'status' => 'low_stock',
                    'last_restocked' => '2024-01-08',
                    'created_at' => now()->toISOString(),
                    'updated_at' => now()->toISOString()
                ]
            ];

            $this->storage->put('inventory', collect($sampleInventory));
        }
    }

    public function index(): JsonResponse
    {
        $inventory = $this->storage->get('inventory');
        return response()->json($inventory);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'item_name' => 'required|string|max:255',
            'category' => 'required|string',
            'current_stock' => 'required|numeric|min:0',
            'unit' => 'required|string',
            'min_stock' => 'required|numeric|min:0',
            'max_stock' => 'required|numeric|min:0',
            'cost_per_unit' => 'required|numeric|min:0',
            'supplier' => 'nullable|string',
            'last_restocked' => 'nullable|date'
        ]);

        $data = $request->all();
        $data['status'] = $data['current_stock'] <= $data['min_stock'] ? 'low_stock' : 'in_stock';

        $item = $this->storage->create('inventory', $data);
        
        return response()->json($item, 201);
    }

    public function show(int $id): JsonResponse
    {
        $item = $this->storage->find('inventory', $id);
        
        if (!$item) {
            return response()->json(['message' => 'Inventory item not found'], 404);
        }
        
        return response()->json($item);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->all();
        
        // Update status based on stock levels if current_stock is being updated
        if (isset($data['current_stock'])) {
            $item = $this->storage->find('inventory', $id);
            if ($item) {
                $minStock = $data['min_stock'] ?? $item['min_stock'];
                $data['status'] = $data['current_stock'] <= $minStock ? 'low_stock' : 'in_stock';
            }
        }

        $item = $this->storage->update('inventory', $id, $data);
        
        if (!$item) {
            return response()->json(['message' => 'Inventory item not found'], 404);
        }
        
        return response()->json($item);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->storage->delete('inventory', $id);
        
        if (!$deleted) {
            return response()->json(['message' => 'Inventory item not found'], 404);
        }
        
        return response()->json(['message' => 'Inventory item deleted successfully']);
    }
}
