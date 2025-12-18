<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'reference_number',
        'customer_name',
        'customer_nickname',
        'customer_notes',
        'table_id',
        'table_number',
        'order_type',
        'items',
        'subtotal',
        'tax',
        'service_fee',
        'delivery_fee',
        'total',
        'payment_method',
        'payment_status',
        'payment_details',
        'paid_at',
        'status',
        'device_ip',
        'device_type',
        'session_id',
        'device_id',
        'original_data',
        'notes',
        'special_instructions',
        'confirmed_at',
        'queued_at',
        'preparing_at',
        'ready_at',
        'served_at',
        'completed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'items' => 'array',
        'payment_details' => 'array',
        'original_data' => 'array',
        'paid_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'queued_at' => 'datetime',
        'preparing_at' => 'datetime',
        'ready_at' => 'datetime',
        'served_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Get the total in decimal format (e.g., 18.50)
     */
    public function getTotalDecimalAttribute(): float
    {
        return $this->total / 100;
    }

    /**
     * Get the subtotal in decimal format
     */
    public function getSubtotalDecimalAttribute(): float
    {
        return $this->subtotal / 100;
    }

    /**
     * Get the tax in decimal format
     */
    public function getTaxDecimalAttribute(): float
    {
        return $this->tax / 100;
    }

    /**
     * Scope to get orders by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get active orders (not completed or cancelled)
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['completed', 'cancelled']);
    }

    /**
     * Scope to get recent orders
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to get orders by table
     */
    public function scopeByTable($query, string $tableNumber)
    {
        return $query->where('table_number', $tableNumber);
    }

    /**
     * Scope to get orders by device ID
     */
    public function scopeByDevice($query, string $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    /**
     * Scope to get orders for the current device and table context
     */
    public function scopeForCurrentContext($query, int $tableId, string $deviceId)
    {
        return $query->where('table_id', $tableId)->where('device_id', $deviceId);
    }

    /**
     * Scope to get orders by table ID
     */
    public function scopeByTableId($query, int $tableId)
    {
        return $query->where('table_id', $tableId);
    }

    /**
     * Check if the order belongs to the given device and table
     */
    public function belongsToContext(int $tableId, string $deviceId): bool
    {
        return $this->table_id === $tableId && $this->device_id === $deviceId;
    }

    /**
     * Check if order can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return $this->status === 'received';
    }

    /**
     * Update order status with timestamp
     */
    public function updateStatus(string $status): bool
    {
        $this->status = $status;
        
        // Update corresponding timestamp
        $timestampField = $status . '_at';
        if (in_array($timestampField, $this->fillable)) {
            $this->{$timestampField} = now();
        }
        
        return $this->save();
    }

    /**
     * Mark order as paid
     */
    public function markAsPaid(array $paymentDetails = []): bool
    {
        $this->payment_status = 'paid';
        $this->paid_at = now();
        
        if (!empty($paymentDetails)) {
            $this->payment_details = $paymentDetails;
        }
        
        return $this->save();
    }

    /**
     * Get items count
     */
    public function getItemsCountAttribute(): int
    {
        return count($this->items);
    }

    /**
     * Get items description
     */
    public function getItemsDescriptionAttribute(): string
    {
        $items = collect($this->items)->map(function ($item) {
            $name = $item['name'];
            $quantity = $item['quantity'];
            return $quantity > 1 ? "{$quantity}x {$name}" : $name;
        });

        return $items->join(', ');
    }

    /**
     * Generate a unique order number
     */
    public static function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-' . strtoupper(substr(uniqid(), -8));
        } while (self::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * Get the table that owns the order
     */
    public function table()
    {
        return $this->belongsTo(Table::class);
    }
}
