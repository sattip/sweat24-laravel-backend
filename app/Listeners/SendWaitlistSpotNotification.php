<?php

namespace App\Listeners;

use App\Events\WaitlistSpotAvailable;
use App\Notifications\WaitlistSpotAvailableNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendWaitlistSpotNotification implements ShouldQueue
{
    use InteractsWithQueue;

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

            // Send in-app notification
            $user->notify(new WaitlistSpotAvailableNotification(
                $gymClass,
                $booking,
                $expiresAt
            ));

            Log::info('Waitlist spot notification sent', [
                'user_id' => $user->id,
                'class_id' => $gymClass->id,
                'booking_id' => $booking->id,
                'expires_at' => $expiresAt->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send waitlist spot notification', [
                'user_id' => $event->user->id,
                'class_id' => $event->gymClass->id,
                'error' => $e->getMessage()
            ]);
        }
    }
} 