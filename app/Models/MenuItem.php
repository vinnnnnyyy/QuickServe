<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    use HasFactory;

    // Allow mass assignment for these fields
    protected $fillable = [
        'name',
        'description',
        'category_id',
        'price',
        'temperature',
        'prep_time',
        'size_labels',
        'featured',
        'popular',
        'available',
        'image_path',
        'notes',
        'status',
        'created_by',
    ];

    // Cast the JSON column to an array
    protected $casts = [
        'size_labels' => 'array',
        'featured' => 'boolean',
        'popular' => 'boolean',
        'available' => 'boolean',
        'price' => 'integer',
    ];

    // Append computed attributes to JSON
    protected $appends = ['image_url', 'price_formatted'];

    // Relationship to User
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relationship to Category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Relationship to Addons
    public function addons()
    {
        return $this->belongsToMany(Addon::class, 'menu_item_addon');
    }

    // Relationship to Inventory (Ingredients)
    public function ingredients()
    {
        return $this->belongsToMany(InventoryItem::class, 'menu_item_ingredients')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }

    // Accessor for image URL
    public function getImageUrlAttribute()
    {
        if ($this->image_path) {
            return asset('storage/' . $this->image_path);
        }
        return null;
    }

    // Accessor for formatted price (convert cents to dollars)
    public function getPriceFormattedAttribute()
    {
        return $this->price / 100;
    }
}