<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'category',
        'stock',
        'unit_price',
        'min_stock_level',
        'supplier',
        'supplier_email',
        'supplier_phone',
        'sku',
        'location',
        'notes',
        'status',
        'status_color',
        'total_value',
        'unit',
        'recipe_unit',
        'conversion_factor',
    ];

    protected $casts = [
        'stock' => 'integer',
        'min_stock_level' => 'integer',
        'unit_price' => 'decimal:2',
        'total_value' => 'decimal:2',
        'conversion_factor' => 'float',
    ];

    public function isLowStock(): bool
    {
        return $this->stock <= $this->min_stock_level;
    }
}
