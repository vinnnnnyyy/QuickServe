<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Table|null $table
 */
class TableSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'session_id',
        'table_id',
        'host_device_id',
        'users',
        'device_ip',
        'device_type',
        'browser',
        'user_agent',
        'status',
        'current_activity',
        'last_activity_at',
        'started_at',
        'ended_at',
        'duration_minutes',
        'network_name',
        'signal_strength',
        'connection_type',
        'order_ids',
        'payment_completed',
        'payment_completed_at',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'table_id' => 'integer',
        'duration_minutes' => 'integer',
        'payment_completed' => 'boolean',
        'order_ids' => 'array',
        'users' => 'array',
        'metadata' => 'array',
        'last_activity_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'payment_completed_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'active',
        'payment_completed' => false,
        'duration_minutes' => 0,
        'connection_type' => 'wifi',
    ];

    /**
     * Get the table that owns the session
     */
    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    /**
     * Check if session is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * End the session
     */
    public function end(): void
    {
        $this->status = 'terminated';
        $this->ended_at = now();
        $this->calculateDuration();
        $this->save();
    }

    /**
     * Calculate session duration in minutes
     */
    public function calculateDuration(): void
    {
        if ($this->started_at) {
            $end = $this->ended_at ?? now();
            $this->duration_minutes = $this->started_at->diffInMinutes($end);
        }
    }

    /**
     * Update last activity
     */
    public function updateActivity(string $activity): void
    {
        $this->current_activity = $activity;
        $this->last_activity_at = now();
        $this->save();
    }

    /**
     * Mark payment as completed
     */
    public function markPaymentCompleted(): void
    {
        $this->payment_completed = true;
        $this->payment_completed_at = now();
        $this->status = 'paid_leaving';
        $this->save();
    }

    /**
     * Get formatted duration
     */
    public function getDurationAttribute(): string
    {
        $this->calculateDuration();
        
        if ($this->duration_minutes < 60) {
            return $this->duration_minutes . ' min';
        }
        
        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;
        
        return $hours . ' hr ' . $minutes . ' min';
    }

    /**
     * Get start time formatted
     */
    public function getStartTimeAttribute(): string
    {
        return $this->started_at ? $this->started_at->format('g:i A') : '';
    }

    /**
     * Get last activity time ago
     */
    public function getLastActivityAgoAttribute(): string
    {
        if (!$this->last_activity_at) {
            return 'N/A';
        }
        
        $diff = $this->last_activity_at->diffInMinutes(now());
        
        if ($diff < 1) {
            return $this->last_activity_at->diffInSeconds(now()) . ' sec ago';
        }
        
        if ($diff < 60) {
            return $diff . ' min ago';
        }
        
        return $this->last_activity_at->diffInHours(now()) . ' hr ago';
    }

    /**
     * Get device count for this table
     */
    public function getDeviceCountAttribute(): int
    {
        return TableSession::where('table_id', $this->table_id)
            ->where('status', 'active')
            ->count();
    }

    /**
     * Get current device number
     */
    public function getCurrentDeviceAttribute(): int
    {
        $sessions = TableSession::where('table_id', $this->table_id)
            ->where('status', 'active')
            ->orderBy('started_at')
            ->pluck('id')
            ->toArray();
        
        $position = array_search($this->id, $sessions);
        
        return $position !== false ? $position + 1 : 1;
    }
}
