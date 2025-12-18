<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Staff extends Model
{
    protected $fillable = [
        'user_id',
        'image',
        'phone',
        'role',
        'shift',
        'hourly_rate',
        'status',
        'hire_date',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'hire_date' => 'date',
    ];

    /**
     * Get the user that owns the staff member.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
