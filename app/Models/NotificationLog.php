<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    protected $fillable = [
        'notification_id',
        'user_id',
        'push_token',
        'platform',
        'status',
        'response_data',
        'error_message'
    ];

    protected $casts = [
        'response_data' => 'array',
        'sent_at' => 'datetime'
    ];

    public $timestamps = false;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log notification attempt
     */
    public static function logAttempt(
        string $notificationId,
        int $userId,
        string $pushToken,
        string $platform,
        string $status,
        array $responseData = null,
        string $errorMessage = null
    ): self {
        return static::create([
            'notification_id' => $notificationId,
            'user_id' => $userId,
            'push_token' => $pushToken,
            'platform' => $platform,
            'status' => $status,
            'response_data' => $responseData,
            'error_message' => $errorMessage
        ]);
    }

    /**
     * Get logs for specific notification
     */
    public static function getLogsForNotification(string $notificationId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('notification_id', $notificationId)
            ->orderBy('sent_at', 'desc')
            ->get();
    }

    /**
     * Get user's notification logs
     */
    public static function getUserLogs(int $userId, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('user_id', $userId)
            ->orderBy('sent_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get failed notifications for retry
     */
    public static function getFailedNotifications(int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('status', 'failed')
            ->where('sent_at', '>=', now()->subHours(24)) // Only recent failures
            ->orderBy('sent_at', 'desc')
            ->limit($limit)
            ->get();
    }
}

