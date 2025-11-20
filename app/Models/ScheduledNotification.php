<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledNotification extends Model
{
    protected $fillable = [
        'id',
        'user_id',
        'type',
        'title',
        'body',
        'scheduled_for',
        'related_id',
        'data',
        'is_sent',
        'sent_at'
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'data' => 'array',
        'is_sent' => 'boolean',
        'sent_at' => 'datetime'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get pending notifications that should be sent
     */
    public static function getPendingNotifications(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('scheduled_for', '<=', now())
            ->where('is_sent', false)
            ->get();
    }

    /**
     * Get user's pending notifications
     */
    public static function getUserPendingNotifications(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('user_id', $userId)
            ->where('is_sent', false)
            ->where('scheduled_for', '>', now())
            ->orderBy('scheduled_for')
            ->get();
    }

    /**
     * Mark notification as sent
     */
    public function markAsSent(): bool
    {
        return $this->update([
            'is_sent' => true,
            'sent_at' => now()
        ]);
    }

    /**
     * Cancel scheduled notification for specific package/appointment
     */
    public static function cancelForRelated(string $type, int $relatedId): int
    {
        return static::where('type', $type)
            ->where('related_id', $relatedId)
            ->where('is_sent', false)
            ->delete();
    }
}

