<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationRecipient extends Model
{
    protected $fillable = [
        'notification_id',
        'user_id',
        'delivery_channels',
        'delivered_at',
        'read_at',
        'delivery_status',
        'failure_reason',
    ];

    protected $casts = [
        'delivery_channels' => 'array',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    /**
     * Get the notification.
     */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    /**
     * Get the user (recipient).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark the notification as delivered.
     */
    public function markAsDelivered(array $channels = []): void
    {
        $this->update([
            'delivered_at' => now(),
            'delivery_status' => 'delivered',
            'delivery_channels' => $channels ?: $this->delivery_channels,
        ]);
    }

    /**
     * Mark the notification as read.
     */
    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
            
            // Update read count on the main notification
            $this->notification->increment('read_count');
        }
    }

    /**
     * Mark the notification as failed.
     */
    public function markAsFailed(string $reason): void
    {
        $this->update([
            'delivery_status' => 'failed',
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Scope for unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope for delivered notifications.
     */
    public function scopeDelivered($query)
    {
        return $query->where('delivery_status', 'delivered');
    }
}
