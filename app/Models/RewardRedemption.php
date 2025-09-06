<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardRedemption extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reward_id',
        'points_spent',
        'reward_code',
        'status',
        'instructions',
        'expires_at',
        'used_at',
        'used_by_staff_id',
        'notes',
    ];

    protected $casts = [
        'points_spent' => 'integer',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(PointsReward::class, 'reward_id');
    }

    public function usedByStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by_staff_id');
    }

    /**
     * Scope for active redemptions
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for expired redemptions
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
                    ->where('status', 'active');
    }

    /**
     * Check if redemption is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast() && $this->status === 'active';
    }

    /**
     * Mark redemption as used
     */
    public function markAsUsed(int $staffId = null): void
    {
        $this->update([
            'status' => 'used',
            'used_at' => now(),
            'used_by_staff_id' => $staffId,
        ]);
    }

    /**
     * Mark redemption as expired
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => 'expired']);
    }
}
