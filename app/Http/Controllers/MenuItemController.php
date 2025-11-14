<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\MenuItem;
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
        // Load all categories from database and pass to the component
        $categories = Category::all()->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'scope' => $category->scope,
            ];
        });

        return Inertia::render('Admin/Menu/Create', [
            'categories' => $categories,
        ]);
    }
    /**
     * Store a new menu item.
     */
    public function store(Request $request)
    {
        // 1. Validate the incoming data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|integer|exists:categories,id', // Expecting an ID
            'price' => 'required|numeric|min:0', // Expecting a float like 10.50
            'temperature' => 'required|string|in:Hot,Cold,Both',
            'prep_time' => 'nullable|string|max:255',
            'size_labels' => 'required|array', // Expecting an array like ["Small", "Large"]
            'size_labels.*' => 'string',
            'featured' => 'required|boolean',
            'popular' => 'required|boolean',
            'available' => 'required|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB max
            'notes' => 'nullable|string',
        ]);

        $imagePath = null;

        // 2. Handle the image upload
        if ($request->hasFile('image')) {
            // Store in 'public/menu_images'
            $imagePath = $request->file('image')->store('menu_images', 'public');
        }

        // 3. Create the Menu Item
        MenuItem::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'category_id' => $validated['category_id'],
            'price' => (int) ($validated['price'] * 100), // Convert to cents
            'temperature' => $validated['temperature'],
            'prep_time' => $validated['prep_time'],
            'size_labels' => $validated['size_labels'], // Already an array
            'featured' => $validated['featured'],
            'popular' => $validated['popular'],
            'available' => $validated['available'],
            'image_path' => $imagePath,
            'notes' => $validated['notes'],
            'status' => 'published', // Default from migration
            'created_by' => Auth::id(), // Get the authenticated user
        ]);

        // 4. Redirect back to the menu index page
        return redirect()->route('admin.menu.index') // Assuming you have this route
                         ->with('success', 'Menu item added successfully!');
    }

    /**
     * Show the form for editing the specified menu item.
     */
    public function edit($id)
    {
        $menuItem = MenuItem::with('category')->findOrFail($id);
        
        // Load all categories from database
        $categories = Category::all()->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'scope' => $category->scope,
            ];
        });

        return Inertia::render('Admin/Menu/Edit', [
            'menuItem' => $menuItem,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified menu item.
     */
    public function update(Request $request, $id)
    {
        $menuItem = MenuItem::findOrFail($id);
        
        // Validate the incoming data
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
        ]);

        // Handle image upload if new image is provided
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('menu_images', 'public');
            $validated['image_path'] = $imagePath;
        }

        // Update price to cents
        $validated['price'] = (int) ($validated['price'] * 100);

        // Update the menu item
        $menuItem->update($validated);

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
