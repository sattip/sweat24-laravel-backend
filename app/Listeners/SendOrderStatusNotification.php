<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendOrderStatusNotification implements ShouldQueue
{
    use InteractsWithQueue;

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function handle(OrderStatusChanged $event): void
    {
        try {
            $order = $event->order;
            $newStatus = $event->newStatus;
            $previousStatus = $event->previousStatus;

            // Skip notifications for certain status transitions
            if ($this->shouldSkipNotification($previousStatus, $newStatus)) {
                Log::info('Skipping order notification for status transition', [
                    'order_id' => $order->id,
                    'from' => $previousStatus,
                    'to' => $newStatus,
                ]);
                return;
            }

            // Prepare notification content based on new status
            $notificationData = $this->prepareNotificationContent($order, $newStatus);
            
            // Create push notification
            $notification = $this->notificationService->createNotification([
                'title' => $notificationData['title'],
                'message' => $notificationData['message'],
                'type' => 'order_status',
                'priority' => $notificationData['priority'],
                'channels' => ['in_app', 'push'],
                'filters' => [
                    'inline' => [
                        'user_ids' => [$order->user_id],
                    ],
                ],
                'status' => 'sent',
            ]);

            Log::info('Order status notification sent', [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'status' => $newStatus,
                'notification_id' => $notification->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send order status notification', [
                'order_id' => $event->order->id,
                'status' => $event->newStatus,
                'error' => $e->getMessage(),
            ]);

            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Prepare notification content based on order status
     */
    private function prepareNotificationContent($order, $status): array
    {
        $orderNumber = $order->order_number;
        $total = $order->total;

        switch ($status) {
            case 'processing':
                return [
                    'title' => 'Παραγγελία σε Επεξεργασία! 🔄',
                    'message' => "Η παραγγελία σας #{$orderNumber} βρίσκεται υπό επεξεργασία. Θα σας ενημερώσουμε όταν είναι έτοιμη!",
                    'priority' => 'medium',
                ];

            case 'ready_for_pickup':
                return [
                    'title' => 'Παραγγελία Έτοιμη για Παραλαβή! 📦',
                    'message' => "Η παραγγελία σας #{$orderNumber} είναι έτοιμη για παραλαβή από το γυμναστήριο! Σύνολο: €{$total}",
                    'priority' => 'high',
                ];

            case 'completed':
                return [
                    'title' => 'Παραγγελία Ολοκληρώθηκε! ✅',
                    'message' => "Η παραγγελία σας #{$orderNumber} ολοκληρώθηκε επιτυχώς! Ευχαριστούμε για την αγορά σας!",
                    'priority' => 'medium',
                ];

            case 'cancelled':
                return [
                    'title' => 'Παραγγελία Ακυρώθηκε ❌',
                    'message' => "Η παραγγελία σας #{$orderNumber} ακυρώθηκε. Για περισσότερες πληροφορίες επικοινωνήστε μαζί μας.",
                    'priority' => 'medium',
                ];

            default:
                return [
                    'title' => 'Ενημέρωση Παραγγελίας 📋',
                    'message' => "Η κατάσταση της παραγγελίας σας #{$orderNumber} ενημερώθηκε.",
                    'priority' => 'low',
                ];
        }
    }

    /**
     * Determine if we should skip notification for this status transition
     */
    private function shouldSkipNotification($from, $to): bool
    {
        // Skip notification if status hasn't really changed
        if ($from === $to) {
            return true;
        }

        // Skip notification for pending status (initial order creation handled elsewhere)
        if ($to === 'pending') {
            return true;
        }

        return false;
    }

    /**
     * Handle a job failure.
     */
    public function failed(OrderStatusChanged $event, $exception): void
    {
        Log::error('Order status notification job failed', [
            'order_id' => $event->order->id,
            'status' => $event->newStatus,
            'exception' => $exception->getMessage(),
        ]);
    }
} 