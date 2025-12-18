<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Table extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'number',
        'location',
        'capacity',
        'occupied',
        'status',
        'qr_code',
        'qr_code_url',
        'qr_code_file_path',
        'qr_code_file_path',
        'qr_token',
        'access_code',
        'description',
        'features',
        'notes',
        'metadata',
        'is_active',
        'last_cleaned_at',
        'status_changed_at',
    ];

    protected $casts = [
        'number' => 'integer',
        'capacity' => 'integer',
        'occupied' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'features' => 'array',
        'last_cleaned_at' => 'datetime',
        'status_changed_at' => 'datetime',
    ];

    protected $attributes = [
        'occupied' => 0,
        'status' => 'available',
        'is_active' => true,
    ];

    /**
     * Get all sessions for this table
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(TableSession::class);
    }

    /**
     * Get all orders for this table
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get active sessions for this table
     */
    public function activeSessions(): HasMany
    {
        return $this->sessions()->where('status', 'active');
    }

    /**
     * Check if table is available
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available' && $this->is_active;
    }

    /**
     * Update table status based on occupancy
     */
    public function updateStatusFromOccupancy(): void
    {
        if ($this->occupied === 0) {
            $this->status = 'available';
        } elseif ($this->occupied >= $this->capacity) {
            $this->status = 'full';
        } else {
            $this->status = 'partial';
        }
        
        $this->status_changed_at = now();
        $this->save();
    }

    /**
     * Get location color for UI
     */
    public function getLocationColorAttribute(): string
    {
        return match($this->location) {
            'Indoor' => 'bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-400',
            'Outdoor' => 'bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400',
            'Patio' => 'bg-purple-100 dark:bg-purple-900/20 text-purple-700 dark:text-purple-400',
            'Bar' => 'bg-amber-100 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400',
            default => 'bg-gray-100 dark:bg-gray-900/20 text-gray-700 dark:text-gray-400',
        };
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'available' => 'bg-green-200 dark:bg-green-800 border-green-200 dark:border-green-800',
            'partial' => 'bg-yellow-200 dark:bg-yellow-800 border-yellow-200 dark:border-yellow-800',
            'full' => 'bg-red-200 dark:bg-red-800 border-red-200 dark:border-red-800',
            'cleaning' => 'bg-blue-200 dark:bg-blue-800 border-blue-200 dark:border-blue-800',
            'reserved' => 'bg-purple-200 dark:bg-purple-800 border-purple-200 dark:border-purple-800',
            'out_of_service' => 'bg-gray-200 dark:bg-gray-800 border-gray-200 dark:border-gray-800',
            default => 'bg-gray-200 dark:bg-gray-800 border-gray-200 dark:border-gray-800',
        };
    }

    /**
     * Get status text for UI
     */
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'available' => 'Available',
            'partial' => 'Partial',
            'full' => 'Full',
            'cleaning' => 'Cleaning',
            'reserved' => 'Reserved',
            'out_of_service' => 'Out of Service',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get status dot color for UI
     */
    public function getStatusDotAttribute(): string
    {
        return match($this->status) {
            'available' => 'bg-green-500',
            'partial' => 'bg-yellow-500',
            'full' => 'bg-red-500',
            'cleaning' => 'bg-blue-500',
            'reserved' => 'bg-purple-500',
            'out_of_service' => 'bg-gray-500',
            default => 'bg-gray-500',
        };
    }
}
