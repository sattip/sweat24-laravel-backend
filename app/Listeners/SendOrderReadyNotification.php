<?php

namespace App\Listeners;

use App\Events\OrderReadyForPickup;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendOrderReadyNotification implements ShouldQueue
{
    use InteractsWithQueue;

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function handle(OrderReadyForPickup $event): void
    {
        try {
            $order = $event->order;
            
            // Create push notification
            $notification = $this->notificationService->createNotification([
                'title' => 'Παραγγελία Έτοιμη για Παραλαβή! 📦',
                'message' => "Η παραγγελία σας #{$order->order_number} είναι έτοιμη για παραλαβή από το γυμναστήριο! Σύνολο: €{$order->total}",
                'type' => 'order_ready',
                'priority' => 'high',
                'channels' => ['in_app', 'push'],
                'filters' => [
                    'inline' => [
                        'user_ids' => [$order->user_id],
                    ],
                ],
                'status' => 'sent',
            ]);

            Log::info('Order ready notification sent', [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'notification_id' => $notification->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send order ready notification', [
                'order_id' => $event->order->id,
                'error' => $e->getMessage(),
            ]);

            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(OrderReadyForPickup $event, $exception): void
    {
        Log::error('Order ready notification job failed', [
            'order_id' => $event->order->id,
            'exception' => $exception->getMessage(),
        ]);
    }
} 