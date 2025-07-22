<?php

namespace App\Listeners;

use App\Events\BookingCreated;
use App\Events\BookingCancelled;
use App\Models\UserPackage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProcessSessionDeduction
{
    public function __construct()
    {
        //
    }

    public function handle($event): void
    {
        $booking = $event->booking;
        
        if (!$booking->user_id) {
            Log::warning('Booking has no associated user', ['booking_id' => $booking->id]);
            return;
        }
        
        if ($event instanceof BookingCreated && $booking->status === 'confirmed') {
            $this->deductSession($booking);
        } elseif ($event instanceof BookingCancelled) {
            $this->refundSession($booking);
        }
    }
    
    private function deductSession($booking): void
    {
        $activePackage = UserPackage::where('user_id', $booking->user_id)
            ->where('status', 'active')
            ->orderBy('expiry_date', 'desc')
            ->first();
            
        if ($activePackage && $activePackage->remaining_sessions > 0) {
            $activePackage->decrement('remaining_sessions');
            
            Log::info('Session deducted for booking', [
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'package_id' => $activePackage->id,
                'remaining_sessions' => $activePackage->remaining_sessions - 1,
            ]);
        }
    }
    
    private function refundSession($booking): void
    {
        // Only refund if the booking was originally confirmed
        if ($booking->getOriginal('status') !== 'confirmed') {
            return;
        }
        
        $activePackage = UserPackage::where('user_id', $booking->user_id)
            ->where('status', 'active')
            ->orderBy('expiry_date', 'desc')
            ->first();
            
        if ($activePackage) {
            $activePackage->increment('remaining_sessions');
            
            Log::info('Session refunded for cancelled booking', [
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'package_id' => $activePackage->id,
                'remaining_sessions' => $activePackage->remaining_sessions + 1,
            ]);
        }
    }
}
