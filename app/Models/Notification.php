<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Notification extends Model
{
    protected $fillable = [
        'title',
        'message',
        'type',
        'priority',
        'channels',
        'filters',
        'created_by',
        'scheduled_at',
        'sent_at',
        'status',
        'total_recipients',
        'delivered_count',
        'read_count',
    ];

    protected $casts = [
        'channels' => 'array',
        'filters' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    /**
     * Get the user who created the notification.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the recipients of the notification.
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(NotificationRecipient::class);
    }

    /**
     * Get recipients with a specific status.
     */
    public function deliveredRecipients(): HasMany
    {
        return $this->recipients()->where('delivery_status', 'delivered');
    }

    /**
     * Get recipients who have read the notification.
     */
    public function readRecipients(): HasMany
    {
        return $this->recipients()->whereNotNull('read_at');
    }

    /**
     * Scope for pending notifications.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'draft')->orWhere('status', 'scheduled');
    }

    /**
     * Scope for sent notifications.
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Check if notification is scheduled.
     */
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled' && $this->scheduled_at !== null;
    }

    /**
     * Check if notification is ready to send.
     */
    public function isReadyToSend(): bool
    {
        if ($this->status !== 'scheduled') {
            return false;
        }

        return $this->scheduled_at <= now();
    }
}
