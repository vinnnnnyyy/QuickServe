<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable = [
        'table_id',
        'product_id',
        'device_id',
        'quantity',
        'options',
        'notes'
    ];

    protected $casts = [
        'options' => 'array',
        'quantity' => 'integer',
        'table_id' => 'integer',
        'product_id' => 'integer'
    ];

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\MenuItem::class, 'product_id');
    }
}
