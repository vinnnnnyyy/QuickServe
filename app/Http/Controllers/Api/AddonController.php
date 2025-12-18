<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AddonController extends Controller
{
    public function index()
    {
        $addons = Addon::orderBy('category')->orderBy('name')->get();
        return response()->json($addons);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category' => 'required|string|max:50',
            'available' => 'required|boolean',
            'max_quantity' => 'required|integer|min:1|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $addon = Addon::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => (int) ($request->price * 100),
            'category' => $request->category,
            'available' => $request->available,
            'max_quantity' => $request->max_quantity,
            'created_by' => Auth::id() ?? 1,
        ]);

        return response()->json($addon, 201);
    }

    public function show(string $id)
    {
        $addon = Addon::find($id);
        
        if (!$addon) {
            return response()->json(['message' => 'Add-on not found'], 404);
        }

        return response()->json($addon);
    }

    public function update(Request $request, string $id)
    {
        $addon = Addon::find($id);
        
        if (!$addon) {
            return response()->json(['message' => 'Add-on not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category' => 'required|string|max:50',
            'available' => 'required|boolean',
            'max_quantity' => 'required|integer|min:1|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $addon->update([
            'name' => $request->name,
            'description' => $request->description,
            'price' => (int) ($request->price * 100),
            'category' => $request->category,
            'available' => $request->available,
            'max_quantity' => $request->max_quantity,
        ]);

        return response()->json($addon);
    }

    public function destroy(string $id)
    {
        $addon = Addon::find($id);
        
        if (!$addon) {
            return response()->json(['message' => 'Add-on not found'], 404);
        }

        $addon->delete();

        return response()->json(['message' => 'Add-on deleted successfully']);
    }

    public function categories()
    {
        $categories = Addon::select('category')
            ->distinct()
            ->pluck('category')
            ->toArray();

        if (empty($categories)) {
            $categories = ['Extras', 'Milk', 'Toppings', 'Syrups'];
        }

        return response()->json($categories);
    }
}
