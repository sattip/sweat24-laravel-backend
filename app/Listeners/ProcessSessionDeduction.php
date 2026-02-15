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
            ->where('is_frozen', false)
            ->where(function($query) {
                $query->whereNull('expiry_date')
                      ->orWhere('expiry_date', '>=', now()->toDateString());
            })
            ->orderBy('expiry_date', 'desc')
            ->first();

        if (!$activePackage) {
            return;
        }

        // Atomic decrement: only decrements if remaining_sessions > 0
        $updated = UserPackage::where('id', $activePackage->id)
            ->where('remaining_sessions', '>', 0)
            ->decrement('remaining_sessions');

        if ($updated) {
            $remainingSessions = $activePackage->fresh()->remaining_sessions;

            if ($remainingSessions <= 2 && $remainingSessions > 0) {
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
            Log::info('No session refund needed - booking was not confirmed', [
                'booking_id' => $booking->id,
                'previous_status' => $previousStatus,
            ]);
            return;
        }

        $activePackage = UserPackage::where('user_id', $booking->user_id)
            ->where('status', 'active')
            ->where('is_frozen', false)
            ->where(function($query) {
                $query->whereNull('expiry_date')
                      ->orWhere('expiry_date', '>=', now()->toDateString());
            })
            ->orderBy('expiry_date', 'desc')
            ->first();

        if ($activePackage) {
            // Cap refund: remaining_sessions must not exceed total_sessions
            $updated = UserPackage::where('id', $activePackage->id)
                ->whereColumn('remaining_sessions', '<', 'total_sessions')
                ->increment('remaining_sessions');

            if ($updated) {
                $afterRefund = $activePackage->fresh()->remaining_sessions;
                Log::info('Session refunded for cancelled booking', [
                    'booking_id' => $booking->id,
                    'user_id' => $booking->user_id,
                    'package_id' => $activePackage->id,
                    'sessions_after_refund' => $afterRefund,
                ]);
            } else {
                Log::warning('Refund skipped - remaining_sessions already at total_sessions', [
                    'booking_id' => $booking->id,
                    'package_id' => $activePackage->id,
                ]);
            }
        } else {
            Log::warning('No active package found for refund', [
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
            ]);
        }
    }
}
