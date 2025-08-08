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
            $this->refundSession($booking, $event->previousStatus ?? null);
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
            
            // Check if user is near to end of sessions (2 or 1 sessions left after deduction)
            $remainingSessions = $activePackage->fresh()->remaining_sessions;
            if ($remainingSessions <= 2 && $remainingSessions > 0) {
                // Dispatch event for sessions ending notification
                \App\Events\UserNearSessionsEnd::dispatch(
                    $booking->user, 
                    $activePackage->fresh(), 
                    $remainingSessions
                );
            }
            
            Log::info('Session deducted for booking', [
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'package_id' => $activePackage->id,
                'remaining_sessions' => $remainingSessions,
            ]);
        }
    }
    
    private function refundSession($booking, ?string $previousStatus = null): void
    {
        // Only refund if the booking was originally confirmed
        $wasConfirmed = $previousStatus ? $previousStatus === 'confirmed' : ($booking->getOriginal('status') === 'confirmed');
        if (!$wasConfirmed) {
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
