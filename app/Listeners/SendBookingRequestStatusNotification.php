<?php

namespace App\Listeners;

use App\Events\BookingRequestStatusChanged;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendBookingRequestStatusNotification implements ShouldQueue
{
    use InteractsWithQueue;

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function handle(BookingRequestStatusChanged $event): void
    {
        try {
            $bookingRequest = $event->bookingRequest;
            $newStatus = $event->newStatus;

            // Only send notifications for confirmed or rejected status
            if (!in_array($newStatus, ['confirmed', 'rejected'])) {
                return;
            }

            // Determine recipient - either logged user or email-based identification
            $userId = $bookingRequest->user_id;
            if (!$userId) {
                // If no user_id, we can't send push notification
                Log::info('Skipping booking request notification - no user_id', [
                    'booking_request_id' => $bookingRequest->id,
                    'client_email' => $bookingRequest->client_email,
                ]);
                return;
            }

            // Prepare notification content based on status
            if ($event->isConfirmed()) {
                $title = 'Αίτημα Ραντεβού Εγκρίθηκε! ✅';
                $serviceLabel = $bookingRequest->service_type === 'ems' ? 'EMS' : 'Personal Training';
                $message = "Το αίτημά σας για {$serviceLabel} εγκρίθηκε! Ημερομηνία: {$bookingRequest->confirmed_date->format('d/m/Y')} στις {$bookingRequest->confirmed_time}.";
                $priority = 'high';
            } else {
                $title = 'Αίτημα Ραντεβού Απορρίφθηκε ❌';
                $message = "Το αίτημά σας για ραντεβού απορρίφθηκε. ";
                if ($bookingRequest->rejection_reason) {
                    $message .= "Λόγος: {$bookingRequest->rejection_reason}";
                }
                $priority = 'medium';
            }

            // Create push notification
            $notification = $this->notificationService->createNotification([
                'title' => $title,
                'message' => $message,
                'type' => 'booking_request_status',
                'priority' => $priority,
                'channels' => ['in_app', 'push'],
                'filters' => [
                    'inline' => [
                        'user_ids' => [$userId],
                    ],
                ],
                'status' => 'sent',
            ]);

            Log::info('Booking request status notification sent', [
                'booking_request_id' => $bookingRequest->id,
                'user_id' => $userId,
                'status' => $newStatus,
                'notification_id' => $notification->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send booking request status notification', [
                'booking_request_id' => $event->bookingRequest->id,
                'status' => $event->newStatus,
                'error' => $e->getMessage(),
            ]);

            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(BookingRequestStatusChanged $event, $exception): void
    {
        Log::error('Booking request status notification job failed', [
            'booking_request_id' => $event->bookingRequest->id,
            'status' => $event->newStatus,
            'exception' => $exception->getMessage(),
        ]);
    }
} 