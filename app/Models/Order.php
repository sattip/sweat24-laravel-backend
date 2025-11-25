<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'user_id',
        'status',
        'is_preorder',
        'subtotal',
        'tax',
        'total',
        'customer_name',
        'customer_email',
        'customer_phone',
        'notes',
        'ready_at',
        'completed_at',
        'points_applied',
        'points_awarded',
        'points_applied_at'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'points_awarded' => 'decimal:2',
        'points_applied' => 'boolean',
        'is_preorder' => 'boolean',
        'ready_at' => 'datetime',
        'completed_at' => 'datetime',
        'points_applied_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            $order->order_number = 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Get the user that owns the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Check if order is editable.
     */
    public function isEditable(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    /**
     * Mark order as ready for pickup.
     */
    public function markAsReady(): void
    {
        $this->update([
            'status' => 'ready_for_pickup',
            'ready_at' => now()
        ]);
    }

    /**
     * Mark order as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);
    }

    /**
     * Cancel the order.
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Check if points have been applied to this order.
     */
    public function hasPointsApplied(): bool
    {
        return (bool) $this->points_applied;
    }

    /**
     * Get the loyalty points record for this order.
     */
    public function loyaltyPoints()
    {
        return $this->morphMany(LoyaltyPoint::class, 'reference');
    }

    /**
     * Get points preview for this order without applying them.
     */
    public function getPointsPreview(): array
    {
        $pointsService = app(\App\Services\PointsService::class);
        return $pointsService->previewPointsForOrder($this);
    }
}