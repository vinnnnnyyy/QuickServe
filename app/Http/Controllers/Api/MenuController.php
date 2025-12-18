<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MenuController extends Controller
{
    /**
     * Display a listing of menu items.
     */
    public function index()
    {
        $menuItems = MenuItem::with(['category', 'addons'])->get();
        return response()->json($menuItems);
    }

    /**
     * Store a newly created menu item.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|integer|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'temperature' => 'required|string|in:Hot,Cold,Both',
            'prep_time' => 'nullable|string|max:255',
            'size_labels' => 'required|array',
            'size_labels.*' => 'string',
            'featured' => 'required|boolean',
            'popular' => 'required|boolean',
            'available' => 'required|boolean',
            'image_path' => 'nullable|string',
            'notes' => 'nullable|string',
            'ingredients' => 'nullable|array',
            'ingredients.*.id' => 'required_with:ingredients|exists:inventory_items,id',
            'ingredients.*.quantity' => 'required_with:ingredients|numeric|min:0.0001',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        
        // Convert price to cents if it's in dollars
        if (isset($data['price'])) {
            $data['price'] = (int) ($data['price'] * 100);
        }

        // Set default status and creator
        $data['status'] = 'published';
        $data['created_by'] = auth()->id() ?? 1; // Default to admin user

        $menuItem = MenuItem::create($data);
        
        // Handle ingredients (Recipe)
        if ($request->has('ingredients')) {
            $ingredients = [];
            foreach ($request->ingredients as $ing) {
                // $ing should be { id: 1, quantity: 0.5 }
                if (isset($ing['id']) && isset($ing['quantity'])) {
                    $ingredients[$ing['id']] = ['quantity' => $ing['quantity']];
                }
            }
            $menuItem->ingredients()->sync($ingredients);
        }
        
        // Load the relationship
        $menuItem->load(['category', 'addons', 'ingredients']);

        return response()->json($menuItem, 201);
    }

    /**
     * Display the specified menu item.
     */
    public function show(string $id)
    {
        $menuItem = MenuItem::with(['category', 'addons'])->find($id);
        
        if (!$menuItem) {
            return response()->json(['message' => 'Menu item not found'], 404);
        }

        return response()->json($menuItem);
    }

    /**
     * Update the specified menu item.
     */
    public function update(Request $request, string $id)
    {
        $menuItem = MenuItem::find($id);
        
        if (!$menuItem) {
            return response()->json(['message' => 'Menu item not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'category_id' => 'sometimes|required|integer|exists:categories,id',
            'price' => 'sometimes|required|numeric|min:0',
            'temperature' => 'sometimes|required|string|in:Hot,Cold,Both',
            'prep_time' => 'nullable|string|max:255',
            'size_labels' => 'sometimes|required|array',
            'size_labels.*' => 'string',
            'featured' => 'sometimes|required|boolean',
            'popular' => 'sometimes|required|boolean',
            'available' => 'sometimes|required|boolean',
            'image_path' => 'nullable|string',
            'notes' => 'nullable|string',
            'ingredients' => 'nullable|array',
            'ingredients.*.id' => 'required_with:ingredients|exists:inventory_items,id',
            'ingredients.*.quantity' => 'required_with:ingredients|numeric|min:0.0001',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        
        // Convert price to cents if it's being updated
        if (isset($data['price'])) {
            $data['price'] = (int) ($data['price'] * 100);
        }

        $menuItem->update($data);
        
        // Handle ingredients (Recipe)
        if ($request->has('ingredients')) {
            $ingredients = [];
            foreach ($request->ingredients as $ing) {
                if (isset($ing['id']) && isset($ing['quantity'])) {
                    $ingredients[$ing['id']] = ['quantity' => $ing['quantity']];
                }
            }
            $menuItem->ingredients()->sync($ingredients);
        }
        
        // Reload the relationships
        $menuItem->load(['category', 'addons', 'ingredients']);

        return response()->json($menuItem);
    }

    /**
     * Remove the specified menu item.
     */
    public function destroy(string $id)
    {
        $menuItem = MenuItem::find($id);
        
        if (!$menuItem) {
            return response()->json(['message' => 'Menu item not found'], 404);
        }

        $menuItem->delete();

        return response()->json(['message' => 'Menu item deleted successfully']);
    }

    /**
     * Get menu items by category.
     */
    public function byCategory(string $category)
    {
        $menuItems = MenuItem::with(['category', 'addons'])
            ->whereHas('category', function ($query) use ($category) {
                $query->where('name', $category)
                      ->orWhere('id', $category);
            })
            ->get();

        return response()->json($menuItems);
    }

    /**
     * Reset sample data (for testing purposes).
     */
    public function resetSampleData()
    {
        // This could be used to reset menu items to sample data
        // Implementation depends on your seeding strategy
        
        return response()->json([
            'message' => 'Sample data reset functionality not yet implemented'
        ], 501);
    }
}
