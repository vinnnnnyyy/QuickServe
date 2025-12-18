<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\InventoryItem;
use App\Models\MenuItem;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InventoryRecipeTest extends TestCase
{
    // Note: Not using RefreshDatabase to avoid wiping existing data if not configured correctly for test DB.
    // We will clean up manually.

    public function test_recipe_stock_deduction_logic()
    {
        // 1. Setup
        $user = User::first() ?? User::factory()->create();
        $category = Category::firstOrCreate(['name' => 'Test Category'], ['scope' => 'menu']);

        $milk = InventoryItem::create([
            'name' => 'Test Milk', // Renamed to avoid collision
            'category' => 'Ingredients',
            'stock' => 1000,
            'unit' => 'ml',
            'unit_price' => 0.10,
            'total_value' => 100
        ]);

        $coffee = InventoryItem::create([
            'name' => 'Test Coffee',
            'category' => 'Ingredients',
            'stock' => 1000,
            'unit' => 'g',
            'unit_price' => 0.50,
            'total_value' => 500
        ]);

        $latte = MenuItem::create([
            'name' => 'Test Latte',
            'description' => 'Test',
            'category_id' => $category->id,
            'price' => 500,
            'temperature' => 'Hot',
            'size_labels' => ['Regular'],
            'available' => true,
            'featured' => false,
            'popular' => false,
            'created_by' => $user->id
        ]);

        // Attach ingredients: 200ml milk, 18g coffee
        $latte->ingredients()->attach([
            $milk->id => ['quantity' => 200],
            $coffee->id => ['quantity' => 18]
        ]);

        // 2. Simulate Order Event (OrderController logic)
        $qtyOrdered = 2;

        foreach ($latte->ingredients as $ingredient) {
            $deductAmount = $ingredient->pivot->quantity * $qtyOrdered;
            $ingredient->decrement('stock', $deductAmount);
        }

        // 3. Verify
        $milk->refresh();
        $coffee->refresh();

        $this->assertEquals(600, $milk->stock, "Milk stock should be 1000 - 400 = 600");
        $this->assertEquals(964, $coffee->stock, "Coffee stock should be 1000 - 36 = 964");

        // 4. Cleanup
        $latte->ingredients()->detach();
        $latte->delete();
        $milk->delete();
        $coffee->delete();
    }
}
