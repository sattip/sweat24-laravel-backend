<?php

namespace App\Listeners;

use App\Events\WaitlistSpotAvailable;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendWaitlistSpotNotification implements ShouldQueue
{
    use InteractsWithQueue;

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(WaitlistSpotAvailable $event): void
    {
        try {
            $user = $event->user;
            $gymClass = $event->gymClass;
            $booking = $event->booking;
            $expiresAt = $event->expiresAt;

            Log::info('WaitlistSpotAvailable event handled', [
                'user_id' => $user->id,
                'class_id' => $gymClass->id,
                'booking_id' => $booking->id,
                'timestamp' => now()->toDateTimeString()
            ]);

            // ΔΙΟΡΘΩΣΗ: Έλεγχος για duplicate notification
            $existingNotification = \App\Models\NotificationRecipient::whereHas('notification', function($query) use ($gymClass, $user) {
                $query->where('title', 'Θέση Διαθέσιμη στη Λίστα Αναμονής! 🎯')
                      ->where('message', 'LIKE', "%{$gymClass->name}%")
                      ->where('created_at', '>=', now()->subMinutes(5)); // Τελευταία 5 λεπτά
            })->where('user_id', $user->id)->exists();

            if ($existingNotification) {
                Log::info('Duplicate waitlist notification prevented', [
                    'user_id' => $user->id,
                    'class_id' => $gymClass->id,
                    'booking_id' => $booking->id,
                ]);
                return;
            }

            // Create in-app notification for dashboard
            $notification = $this->notificationService->createNotification([
                'title' => 'Θέση Διαθέσιμη στη Λίστα Αναμονής! 🎯',
                'message' => "Μια θέση ελευθερώθηκε στο μάθημα '{$gymClass->name}' στις {$gymClass->date->format('d/m/Y')} στις {$gymClass->time}! Η κράτησή σας έχει ενεργοποιηθεί αυτόματα.",
                'type' => 'success',
                'priority' => 'high',
                'channels' => ['in_app'], // ΜΟΝΟ in-app για dashboard
                'filters' => [
                    'inline' => [
                        'user_ids' => [$user->id],
                    ],
                ],
                'status' => 'sent',
            ]);

            Log::info('Waitlist spot in-app notification created', [
                'user_id' => $user->id,
                'class_id' => $gymClass->id,
                'booking_id' => $booking->id,
                'notification_id' => $notification->id,
                'expires_at' => $expiresAt->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create waitlist spot notification', [
                'user_id' => $event->user->id,
                'class_id' => $event->gymClass->id,
                'error' => $e->getMessage()
            ]);

            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(WaitlistSpotAvailable $event, $exception): void
    {
        Log::error('Waitlist spot notification job failed', [
            'user_id' => $event->user->id,
            'class_id' => $event->gymClass->id,
            'exception' => $exception->getMessage(),
        ]);
    }
} 