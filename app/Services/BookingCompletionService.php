<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\UserPackage;
use App\Models\CashRegisterEntry;
use App\Exceptions\BusinessValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class BookingCompletionService
{
    protected $cashRegisterService;

    public function __construct(CashRegisterService $cashRegisterService)
    {
        $this->cashRegisterService = $cashRegisterService;
    }

    /**
     * Complete a booking and handle package usage
     */
    public function completeBooking(int $bookingId, int $completedBy, ?string $trainerNotes = null): array
    {
        return DB::transaction(function () use ($bookingId, $completedBy, $trainerNotes) {
            $booking = Booking::findOrFail($bookingId);

            // Validate booking can be completed
            $this->validateBookingForCompletion($booking);

            // Get the user
            $user = \App\Models\User::find($booking->user_id);

            // Check if user is a guest/visitor
            $isGuest = $user && strtolower($user->membership_type) === 'guest';

            $userPackage = null;
            $cashEntry = null;

            // Only process package deduction for non-guest users
            if (!$isGuest) {
                // Find active package
                $userPackage = $this->findActivePackage($booking->user_id);

                if (!$userPackage) {
                    throw new BusinessValidationException('Δεν βρέθηκε ενεργό πακέτο για τον χρήστη.', 'NO_ACTIVE_PACKAGE');
                }

                // Validate package has remaining sessions
                if ($userPackage->remaining_sessions <= 0) {
                    throw new BusinessValidationException('Δεν έχετε διαθέσιμες συνεδρίες στο πακέτο σας.', 'INSUFFICIENT_SESSIONS');
                }

                // Calculate amount (with rounding adjustment for last session)
                $standardAmount = $this->cashRegisterService->calculatePerTrainingCost($userPackage);
                $amount = $this->cashRegisterService->handleRoundingAdjustment($userPackage, $standardAmount);

                // NOTE: Session deduction is handled by ProcessSessionDeduction listener
                // on BookingCreated event at booking creation time. Do NOT decrement here
                // to avoid double deduction (BUG-03).

                // Record income
                $cashEntry = $this->cashRegisterService->recordPackageIncome($booking, $userPackage);
            }

            // Update booking
            $booking->update([
                'status' => 'completed',
                'attended' => true,
            ]);

            // Save trainer notes if provided
            if ($trainerNotes && $user) {
                $existingNotes = $user->trainer_notes ?? '';
                $timestamp = now()->format('d/m/Y H:i');
                $newNote = "[{$timestamp}] {$trainerNotes}";
                $user->trainer_notes = $existingNotes ? $existingNotes . "\n\n" . $newNote : $newNote;
                $user->save();
            }

            Log::info('Booking completed successfully', [
                'booking_id' => $booking->id,
                'is_guest' => $isGuest,
                'user_package_id' => $userPackage ? $userPackage->id : null,
                'store_id' => $booking->store_id,
                'has_trainer_notes' => !empty($trainerNotes),
                'remaining_sessions' => $userPackage ? $userPackage->fresh()->remaining_sessions : 'N/A (guest)',
            ]);

            return [
                'booking' => $booking->fresh(),
                'user_package' => $userPackage ? $userPackage->fresh() : null,
                'cash_entry' => $cashEntry,
                'is_guest' => $isGuest,
            ];
        });
    }

    /**
     * Validate booking can be completed
     */
    protected function validateBookingForCompletion(Booking $booking): void
    {
        if ($booking->status === 'completed') {
            throw new BusinessValidationException('Η κράτηση έχει ήδη ολοκληρωθεί.', 'BOOKING_ALREADY_COMPLETED');
        }

        if ($booking->status === 'cancelled') {
            throw new BusinessValidationException('Δεν μπορείτε να ολοκληρώσετε μια ακυρωμένη κράτηση.', 'BOOKING_CANCELLED');
        }

        if (!$booking->store_id) {
            throw new BusinessValidationException('Η κράτηση πρέπει να έχει εκχωρημένο κατάστημα.', 'MISSING_STORE_ASSIGNMENT');
        }
    }

    /**
     * Find active package for user
     */
    protected function findActivePackage(int $userId): ?UserPackage
    {
        return UserPackage::where('user_id', $userId)
            ->where('status', 'active')
            ->where('is_frozen', false)
            ->where('remaining_sessions', '>', 0)
            ->where(function ($query) {
                $query->whereNull('expiry_date')
                      ->orWhere('expiry_date', '>=', now()->toDateString());
            })
            ->orderBy('expiry_date', 'desc')
            ->first();
    }

    /**
     * Get package usage summary for a user
     */
    public function getUserPackageSummary(int $userId): array
    {
        $packages = UserPackage::where('user_id', $userId)
            ->with(['package'])
            ->get();

        return $packages->map(function ($userPackage) {
            $usedSessions = $userPackage->total_sessions - $userPackage->remaining_sessions;
            $perTrainingCost = $this->cashRegisterService->calculatePerTrainingCost($userPackage);

            return [
                'id' => $userPackage->id,
                'name' => $userPackage->name,
                'total_sessions' => $userPackage->total_sessions,
                'remaining_sessions' => $userPackage->remaining_sessions,
                'used_sessions' => $usedSessions,
                'per_training_cost' => $perTrainingCost,
                'status' => $userPackage->status,
                'expiry_date' => $userPackage->expiry_date,
            ];
        })->toArray();
    }
}
