<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AddonController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Addons/Index', [
            'addons' => Addon::orderBy('category')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        $categories = Addon::select('category')
            ->distinct()
            ->pluck('category')
            ->toArray();

        if (empty($categories)) {
            $categories = ['Extras', 'Milk', 'Toppings', 'Syrups'];
        }

        return Inertia::render('Admin/Addons/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category' => 'required|string|max:50',
            'available' => 'required|boolean',
            'max_quantity' => 'required|integer|min:1|max:10',
        ]);

        Addon::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'price' => (int) ($validated['price'] * 100),
            'category' => $validated['category'],
            'available' => $validated['available'],
            'max_quantity' => $validated['max_quantity'],
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('admin.addons.index')
                         ->with('success', 'Add-on created successfully!');
    }

    public function edit($id)
    {
        $addon = Addon::findOrFail($id);

        $categories = Addon::select('category')
            ->distinct()
            ->pluck('category')
            ->toArray();

        if (empty($categories)) {
            $categories = ['Extras', 'Milk', 'Toppings', 'Syrups'];
        }

        return Inertia::render('Admin/Addons/Edit', [
            'addon' => $addon,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, $id)
    {
        $addon = Addon::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category' => 'required|string|max:50',
            'available' => 'required|boolean',
            'max_quantity' => 'required|integer|min:1|max:10',
        ]);

        $addon->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'price' => (int) ($validated['price'] * 100),
            'category' => $validated['category'],
            'available' => $validated['available'],
            'max_quantity' => $validated['max_quantity'],
        ]);

        return redirect()->route('admin.addons.index')
                         ->with('success', 'Add-on updated successfully!');
    }

    public function destroy($id)
    {
        $addon = Addon::findOrFail($id);
        $addon->delete();

        return redirect()->route('admin.addons.index')
                         ->with('success', 'Add-on deleted successfully!');
    }

    public function toggleAvailability($id)
    {
        $addon = Addon::findOrFail($id);
        $addon->update(['available' => !$addon->available]);

        return response()->json($addon);
    }
}
