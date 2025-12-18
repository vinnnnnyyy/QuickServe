<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceSession extends Model
{
    use HasUuids;

    protected $fillable = [
        'device_id',
        'table_id',
        'user_agent',
        'device_ip',
        'device_type',
        'browser',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function updateLastSeen(): void
    {
        $this->last_seen_at = now();
        $this->save();
    }

    public function scopeForDevice($query, string $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    public function scopeForTable($query, int $tableId)
    {
        return $query->where('table_id', $tableId);
    }

    public function scopeActive($query, int $minutesThreshold = 30)
    {
        return $query->where('last_seen_at', '>=', now()->subMinutes($minutesThreshold));
    }
}
