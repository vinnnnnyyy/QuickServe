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
    ];

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
}