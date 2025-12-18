<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Addon extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'category',
        'available',
        'max_quantity',
        'created_by',
    ];

    protected $casts = [
        'available' => 'boolean',
        'price' => 'integer',
        'max_quantity' => 'integer',
    ];

    protected $appends = ['price_formatted'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function menuItems()
    {
        return $this->belongsToMany(MenuItem::class, 'menu_item_addon');
    }

    public function getPriceFormattedAttribute()
    {
        return $this->price / 100;
    }
}
