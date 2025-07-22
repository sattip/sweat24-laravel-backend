<?php

namespace App\Listeners;

use App\Events\EventCreated;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendNewEventNotification implements ShouldQueue
{
    use InteractsWithQueue;

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function handle(EventCreated $event): void
    {
        try {
            $gymEvent = $event->event;
            
            // Create push notification for all active members
            $notification = $this->notificationService->createNotification([
                'title' => 'Νέα Εκδήλωση! 🎉',
                'message' => "Νέα εκδήλωση '{$gymEvent->title}' στις {$gymEvent->event_date->format('d/m/Y')}! Μάθετε περισσότερα και κάντε την κράτησή σας.",
                'type' => 'new_event',
                'priority' => 'medium',
                'channels' => ['in_app', 'push'],
                'filters' => [
                    'inline' => [
                        'membership_status' => 'active',
                    ],
                ],
                'status' => 'sent',
            ]);

            Log::info('New event notification sent', [
                'event_id' => $gymEvent->id,
                'event_title' => $gymEvent->title,
                'notification_id' => $notification->id,
                'recipients_count' => $notification->total_recipients,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send new event notification', [
                'event_id' => $event->event->id,
                'error' => $e->getMessage(),
            ]);

            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(EventCreated $event, $exception): void
    {
        Log::error('New event notification job failed', [
            'event_id' => $event->event->id,
            'exception' => $exception->getMessage(),
        ]);
    }
} 