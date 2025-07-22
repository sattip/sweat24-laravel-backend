<?php

namespace App\Listeners;

use App\Events\BookingCreated;
use App\Events\BookingCancelled;
use App\Models\Booking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class UpdateClassParticipants
{
    public function __construct()
    {
        //
    }

    public function handle($event): void
    {
        $booking = $event->booking;
        
        if (!$booking->gymClass) {
            Log::warning('Booking has no associated gym class', ['booking_id' => $booking->id]);
            return;
        }
        
        // Calculate actual participants count based on active bookings
        $actualParticipants = Booking::where('class_id', $booking->class_id)
            ->whereNotIn('status', ['cancelled', 'waitlist'])
            ->count();
            
        // Update the gym class
        $booking->gymClass->update(['current_participants' => $actualParticipants]);
        
        Log::info('Updated class participants count', [
            'class_id' => $booking->class_id,
            'new_count' => $actualParticipants,
            'event' => class_basename($event),
            'booking_id' => $booking->id,
        ]);
    }
}
