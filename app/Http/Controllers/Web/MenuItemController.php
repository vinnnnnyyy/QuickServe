<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MenuItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/Menu/Index', [
            'menuItems' => MenuItem::with('category')->get(),
        ]);
    }

    public function create()
    {
        $categories = Category::all()->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'scope' => $category->scope,
            ];
        });

        $addons = Addon::where('available', true)->orderBy('category')->orderBy('name')->get();
        $inventoryItems = InventoryItem::select('id', 'name', 'unit', 'unit_price', 'recipe_unit', 'conversion_factor')->orderBy('name')->get();

        return Inertia::render('Admin/Menu/Create', [
            'categories' => $categories,
            'addons' => $addons,
            'inventoryItems' => $inventoryItems,
        ]);
    }
    /**
     * Store a new menu item.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'notes' => 'nullable|string',
            'addon_ids' => 'nullable|array',
            'addon_ids.*' => 'integer|exists:addons,id',
        ]);

        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('menu_images', 'public');
        }

        $menuItem = MenuItem::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'category_id' => $validated['category_id'],
            'price' => (int) ($validated['price'] * 100),
            'temperature' => $validated['temperature'],
            'prep_time' => $validated['prep_time'],
            'size_labels' => $validated['size_labels'],
            'featured' => $validated['featured'],
            'popular' => $validated['popular'],
            'available' => $validated['available'],
            'image_path' => $imagePath,
            'notes' => $validated['notes'],
            'status' => 'published',
            'created_by' => Auth::id(),
        ]);

        if (!empty($validated['addon_ids'])) {
            $menuItem->addons()->sync($validated['addon_ids']);
        }

        if ($request->has('ingredients')) {
            $ingredients = [];
            foreach ($request->input('ingredients') as $ingredient) {
                if (isset($ingredient['id']) && isset($ingredient['quantity'])) {
                    $ingredients[$ingredient['id']] = ['quantity' => $ingredient['quantity']];
                }
            }
            $menuItem->ingredients()->sync($ingredients);
        }

        return redirect()->route('admin.menu.index')
                         ->with('success', 'Menu item added successfully!');
    }

    /**
     * Show the form for editing the specified menu item.
     */
    public function edit($id)
    {
        $menuItem = MenuItem::with(['category', 'addons'])->findOrFail($id);
        
        $categories = Category::all()->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'scope' => $category->scope,
            ];
        });

        $addons = Addon::where('available', true)->orderBy('category')->orderBy('name')->get();
        $inventoryItems = InventoryItem::select('id', 'name', 'unit', 'unit_price', 'recipe_unit', 'conversion_factor')->orderBy('name')->get();

        return Inertia::render('Admin/Menu/Edit', [
            'menuItem' => $menuItem,
            'categories' => $categories,
            'addons' => $addons,
            'inventoryItems' => $inventoryItems,
        ]);
    }

    /**
     * Update the specified menu item.
     */
    public function update(Request $request, $id)
    {
        $menuItem = MenuItem::findOrFail($id);
        
        $validated = $request->validate([
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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'notes' => 'nullable|string',
            'addon_ids' => 'nullable|array',
            'addon_ids.*' => 'integer|exists:addons,id',
        ]);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('menu_images', 'public');
            $validated['image_path'] = $imagePath;
        }

        $validated['price'] = (int) ($validated['price'] * 100);

        $menuItem->update($validated);

        $menuItem->addons()->sync($validated['addon_ids'] ?? []);

        if ($request->has('ingredients')) {
            $ingredients = [];
            foreach ($request->input('ingredients') as $ingredient) {
                if (isset($ingredient['id']) && isset($ingredient['quantity'])) {
                    $ingredients[$ingredient['id']] = ['quantity' => $ingredient['quantity']];
                }
            }
            $menuItem->ingredients()->sync($ingredients);
        }

        return redirect()->route('admin.menu.index')
                         ->with('success', 'Menu item updated successfully!');
    }

    /**
     * Remove the specified menu item.
     */
    public function destroy($id)
    {
        $menuItem = MenuItem::findOrFail($id);
        $menuItem->delete();

        return redirect()->route('admin.menu.index')
                         ->with('success', 'Menu item deleted successfully!');
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'scope' => 'required|string',
            Rule::unique('categories')->where(function ($query) use ($request) {
                return $query->where('name', $request->name)
                             ->where('scope', $request->scope);
            }),
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'scope' => $validated['scope'],
        ]);

        // Return the new category (ID and name)
        return response()->json([
            'id' => $category->id,
            'name' => $category->name,
        ]);
    }
}
