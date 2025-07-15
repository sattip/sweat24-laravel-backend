<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\NotificationFilter;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Create a new notification.
     */
    public function createNotification(array $data): Notification
    {
        return DB::transaction(function () use ($data) {
            // Create the notification
            $notification = Notification::create([
                'title' => $data['title'],
                'message' => $data['message'],
                'type' => $data['type'] ?? 'info',
                'priority' => $data['priority'] ?? 'medium',
                'channels' => $data['channels'] ?? ['in_app'],
                'filters' => $data['filters'] ?? [],
                'created_by' => auth()->id(),
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'status' => $data['status'] ?? 'draft',
            ]);

            // If sending immediately, process recipients
            if ($notification->status === 'sent' || 
                ($notification->status === 'scheduled' && $notification->isReadyToSend())) {
                $this->processNotification($notification);
            }

            return $notification;
        });
    }

    /**
     * Update a notification.
     */
    public function updateNotification(Notification $notification, array $data): Notification
    {
        // Can't update sent notifications
        if ($notification->status === 'sent') {
            throw new \Exception('Cannot update a sent notification');
        }

        $notification->update([
            'title' => $data['title'] ?? $notification->title,
            'message' => $data['message'] ?? $notification->message,
            'type' => $data['type'] ?? $notification->type,
            'priority' => $data['priority'] ?? $notification->priority,
            'channels' => $data['channels'] ?? $notification->channels,
            'filters' => $data['filters'] ?? $notification->filters,
            'scheduled_at' => $data['scheduled_at'] ?? $notification->scheduled_at,
            'status' => $data['status'] ?? $notification->status,
        ]);

        return $notification;
    }

    /**
     * Send a notification now.
     */
    public function sendNotification(Notification $notification): void
    {
        if ($notification->status === 'sent') {
            throw new \Exception('Notification has already been sent');
        }

        $this->processNotification($notification);
    }

    /**
     * Process a notification (determine recipients and create recipient records).
     */
    protected function processNotification(Notification $notification): void
    {
        DB::transaction(function () use ($notification) {
            // Get recipients based on filters
            $recipients = $this->getRecipients($notification->filters);

            // Create recipient records
            foreach ($recipients as $user) {
                NotificationRecipient::create([
                    'notification_id' => $notification->id,
                    'user_id' => $user->id,
                    'delivery_channels' => $notification->channels,
                    'delivery_status' => 'pending',
                ]);
            }

            // Update notification stats
            $notification->update([
                'status' => 'sent',
                'sent_at' => now(),
                'total_recipients' => $recipients->count(),
            ]);

            // Queue delivery jobs for each channel
            $this->queueDeliveries($notification);
        });
    }

    /**
     * Get recipients based on filter criteria.
     */
    protected function getRecipients(array $filters): Collection
    {
        $query = User::query()->where('status', 'active');

        // Apply saved filters
        if (!empty($filters['filter_ids'])) {
            $savedFilters = NotificationFilter::whereIn('id', $filters['filter_ids'])
                ->active()
                ->get();

            foreach ($savedFilters as $filter) {
                $query = $filter->applyToQuery($query);
            }
        }

        // Apply inline filters
        if (!empty($filters['inline'])) {
            $inlineFilter = new NotificationFilter(['criteria' => $filters['inline']]);
            $query = $inlineFilter->applyToQuery($query);
        }

        return $query->get();
    }

    /**
     * Queue delivery jobs for each channel.
     */
    protected function queueDeliveries(Notification $notification): void
    {
        $channels = $notification->channels;

        // For now, just mark in-app notifications as delivered
        if (in_array('in_app', $channels)) {
            $notification->recipients()->update([
                'delivered_at' => now(),
                'delivery_status' => 'delivered',
            ]);
            
            $notification->update([
                'delivered_count' => $notification->recipients()->delivered()->count(),
            ]);
        }

        // TODO: Implement email and SMS delivery
        if (in_array('email', $channels)) {
            // Queue email jobs
            Log::info('Email notifications queued for notification ' . $notification->id);
        }

        if (in_array('sms', $channels)) {
            // Queue SMS jobs
            Log::info('SMS notifications queued for notification ' . $notification->id);
        }
    }

    /**
     * Mark a notification as read for a user.
     */
    public function markAsRead(NotificationRecipient $recipient): void
    {
        $recipient->markAsRead();
    }

    /**
     * Get notification statistics.
     */
    public function getNotificationStats(Notification $notification): array
    {
        return [
            'total_recipients' => $notification->total_recipients,
            'delivered_count' => $notification->delivered_count,
            'read_count' => $notification->read_count,
            'delivery_rate' => $notification->total_recipients > 0 
                ? round(($notification->delivered_count / $notification->total_recipients) * 100, 2) 
                : 0,
            'read_rate' => $notification->delivered_count > 0 
                ? round(($notification->read_count / $notification->delivered_count) * 100, 2) 
                : 0,
        ];
    }

    /**
     * Preview recipients for given filters.
     */
    public function previewRecipients(array $filters): array
    {
        $recipients = $this->getRecipients($filters);

        return [
            'count' => $recipients->count(),
            'sample' => $recipients->take(10)->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'membership_type' => $user->membership_type,
                ];
            }),
        ];
    }

    /**
     * Create a test notification for development/debugging
     */
    public function createTestNotification(array $data): Notification
    {
        // Only available in development mode
        if (config('app.env') !== 'local') {
            throw new \Exception('Test notifications are only available in development mode');
        }

        $defaultData = [
            'title' => '[TEST] Test Notification',
            'message' => 'This is a test notification created at ' . now()->format('Y-m-d H:i:s'),
            'type' => 'info',
            'priority' => 'medium',
            'channels' => ['in_app'],
            'filters' => [],
            'status' => 'sent'
        ];

        return $this->createNotification(array_merge($defaultData, $data));
    }

    /**
     * Create a test notification for a specific user
     */
    public function createTestNotificationForUser(User $user, array $data = []): Notification
    {
        // Only available in development mode
        if (config('app.env') !== 'local') {
            throw new \Exception('Test notifications are only available in development mode');
        }

        $defaultData = [
            'title' => '[TEST] Personal Test Notification',
            'message' => "This is a test notification sent to {$user->name} at " . now()->format('Y-m-d H:i:s'),
            'type' => 'info',
            'priority' => 'medium',
            'channels' => ['in_app'],
            'filters' => [
                'inline' => [
                    'user_ids' => [$user->id]
                ]
            ],
            'status' => 'sent'
        ];

        return $this->createNotification(array_merge($defaultData, $data));
    }

    /**
     * Create a bulk test notification
     */
    public function createBulkTestNotification(array $data = []): Notification
    {
        // Only available in development mode
        if (config('app.env') !== 'local') {
            throw new \Exception('Test notifications are only available in development mode');
        }

        $defaultData = [
            'title' => '[BULK TEST] Bulk Test Notification',
            'message' => 'This is a bulk test notification sent to all users at ' . now()->format('Y-m-d H:i:s'),
            'type' => 'info',
            'priority' => 'medium',
            'channels' => ['in_app'],
            'filters' => [], // Empty filters = all users
            'status' => 'sent'
        ];

        return $this->createNotification(array_merge($defaultData, $data));
    }

    /**
     * Clear all test notifications (those with [TEST] in the title)
     */
    public function clearTestNotifications(): int
    {
        // Only available in development mode
        if (config('app.env') !== 'local') {
            throw new \Exception('Test notification clearing is only available in development mode');
        }

        return Notification::where('title', 'like', '%[TEST]%')
            ->orWhere('title', 'like', '%[BULK TEST]%')
            ->orWhere('title', 'like', '%[TARGETED TEST]%')
            ->orWhere('title', 'like', '%[DEBUG]%')
            ->delete();
    }

    /**
     * Simulate package expiry notifications for testing
     */
    public function simulatePackageExpiryNotifications(int $count = 5): Collection
    {
        // Only available in development mode
        if (config('app.env') !== 'local') {
            throw new \Exception('Package expiry simulation is only available in development mode');
        }

        $notifications = collect();
        $users = User::where('membership_type', 'Member')->take($count)->get();

        foreach ($users as $user) {
            $notification = $this->createTestNotificationForUser($user, [
                'title' => '[TEST] Package Expiry Reminder',
                'message' => "Your package is expiring soon! This is a test notification for {$user->name}.",
                'type' => 'warning',
                'priority' => 'high',
                'channels' => ['in_app', 'email']
            ]);

            $notifications->push($notification);
        }

        return $notifications;
    }
}