<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InventoryController extends Controller
{
    public function index(): JsonResponse
    {
        $items = InventoryItem::orderBy('name')->get();

        return response()->json($items);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|max:100',
            'stock' => 'required|integer|min:0',
            'unit_price' => 'required|numeric|min:0',
            'min_stock_level' => 'nullable|integer|min:0',
            'supplier' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $minStock = $validated['min_stock_level'] ?? 0;
        $stock = $validated['stock'];
        $unitPrice = (float) $validated['unit_price'];

        $status = $stock === 0 ? 'Out of Stock' : ($stock <= $minStock ? 'Low Stock' : 'In Stock');
        $statusColor = $status === 'Out of Stock'
            ? 'text-red-600 dark:text-red-400'
            : ($status === 'Low Stock' ? 'text-yellow-600 dark:text-yellow-400' : 'text-green-600 dark:text-green-400');

        $item = InventoryItem::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'],
            'stock' => $stock,
            'unit_price' => $unitPrice,
            'min_stock_level' => $minStock,
            'supplier' => $validated['supplier'] ?? null,
            'sku' => $validated['sku'] ?? null,
            'location' => $validated['location'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => $status,
            'status_color' => $statusColor,
            'total_value' => $stock * $unitPrice,
        ]);

        return response()->json($item, 201);
    }

    public function show(int $id): JsonResponse
    {
        $item = InventoryItem::find($id);

        if (!$item) {
            return response()->json(['message' => 'Inventory item not found'], 404);
        }

        return response()->json($item);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = InventoryItem::find($id);

        if (!$item) {
            return response()->json(['message' => 'Inventory item not found'], 404);
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'category' => 'sometimes|string|max:100',
            'stock' => 'sometimes|integer|min:0',
            'unit_price' => 'sometimes|numeric|min:0',
            'min_stock_level' => 'sometimes|integer|min:0',
            'supplier' => 'sometimes|nullable|string|max:255',
            'sku' => 'sometimes|nullable|string|max:100',
            'location' => 'sometimes|nullable|string|max:100',
            'notes' => 'sometimes|nullable|string',
        ]);

        $item->fill($data);

        $stock = $data['stock'] ?? $item->stock;
        $minStock = $data['min_stock_level'] ?? $item->min_stock_level;
        $unitPrice = $data['unit_price'] ?? $item->unit_price;

        $status = $stock === 0 ? 'Out of Stock' : ($stock <= $minStock ? 'Low Stock' : 'In Stock');
        $statusColor = $status === 'Out of Stock'
            ? 'text-red-600 dark:text-red-400'
            : ($status === 'Low Stock' ? 'text-yellow-600 dark:text-yellow-400' : 'text-green-600 dark:text-green-400');

        $item->status = $status;
        $item->status_color = $statusColor;
        $item->total_value = $stock * $unitPrice;

        $item->save();

        return response()->json($item);
    }

    public function destroy(int $id): JsonResponse
    {
        $item = InventoryItem::find($id);

        if (!$item) {
            return response()->json(['message' => 'Inventory item not found'], 404);
        }

        $item->delete();

        return response()->json(['message' => 'Inventory item deleted successfully']);
    }
}
