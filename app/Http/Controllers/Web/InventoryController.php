<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function index(): Response
    {
        $items = InventoryItem::orderBy('name')->get();

        $totalItems = $items->count();
        $lowStockCount = $items->where(fn ($item) => $item->stock <= $item->min_stock_level)->count();
        $totalValue = $items->sum('total_value');

        return Inertia::render('Admin/Inventory/Index', [
            'inventory' => $items->map(function (InventoryItem $item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'category' => $item->category,
                    'stock' => $item->stock,
                    'unitPrice' => (float) $item->unit_price,
                    'totalValue' => (float) $item->total_value,
                    'status' => $item->status,
                    'statusColor' => $item->status_color,
                ];
            }),
            'summary' => [
                'totalItems' => $totalItems,
                'lowStockCount' => $lowStockCount,
                'totalValue' => $totalValue,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Inventory/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|max:100',
            'stock' => 'required|integer|min:0',
            'unitPrice' => 'required|numeric|min:0',
            'minStockLevel' => 'nullable|integer|min:0',
            'supplier' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $minStock = $validated['minStockLevel'] ?? 0;
        $status = $validated['stock'] === 0 ? 'Out of Stock' : ($validated['stock'] <= $minStock ? 'Low Stock' : 'In Stock');
        $statusColor = $status === 'Out of Stock'
            ? 'text-red-600 dark:text-red-400'
            : ($status === 'Low Stock' ? 'text-yellow-600 dark:text-yellow-400' : 'text-green-600 dark:text-green-400');

        $unitPrice = (float) $validated['unitPrice'];
        $totalValue = $validated['stock'] * $unitPrice;

        InventoryItem::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'],
            'stock' => $validated['stock'],
            'unit_price' => $unitPrice,
            'min_stock_level' => $minStock,
            'supplier' => $validated['supplier'] ?? null,
            'sku' => $validated['sku'] ?? null,
            'location' => $validated['location'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => $status,
            'status_color' => $statusColor,
            'total_value' => $totalValue,
        ]);

        return redirect()->route('admin.inventory.index')
            ->with('success', 'Inventory item created successfully');
    }

    public function edit(int $id): Response
    {
        $item = InventoryItem::findOrFail($id);

        return Inertia::render('Admin/Inventory/Edit', [
            'item' => $item,
            'id' => $id,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|max:100',
            'stock' => 'required|integer|min:0',
            'unitPrice' => 'required|numeric|min:0',
            'minStockLevel' => 'nullable|integer|min:0',
            'supplier' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $item = InventoryItem::findOrFail($id);

        $minStock = $validated['minStockLevel'] ?? 0;
        $status = $validated['stock'] === 0 ? 'Out of Stock' : ($validated['stock'] <= $minStock ? 'Low Stock' : 'In Stock');
        $statusColor = $status === 'Out of Stock'
            ? 'text-red-600 dark:text-red-400'
            : ($status === 'Low Stock' ? 'text-yellow-600 dark:text-yellow-400' : 'text-green-600 dark:text-green-400');

        $unitPrice = (float) $validated['unitPrice'];
        $totalValue = $validated['stock'] * $unitPrice;

        $item->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'],
            'stock' => $validated['stock'],
            'unit_price' => $unitPrice,
            'min_stock_level' => $minStock,
            'supplier' => $validated['supplier'] ?? null,
            'sku' => $validated['sku'] ?? null,
            'location' => $validated['location'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => $status,
            'status_color' => $statusColor,
            'total_value' => $totalValue,
        ]);

        return redirect()->route('admin.inventory.index')
            ->with('success', 'Inventory item updated successfully');
    }

    public function destroy(int $id)
    {
        $item = InventoryItem::findOrFail($id);
        $item->delete();

        return redirect()->route('admin.inventory.index')
            ->with('success', 'Inventory item deleted successfully');
    }
}
