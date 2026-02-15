<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BookingPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any bookings.
     */
    public function viewAny(User $user): bool
    {
        // Admins and trainers can view all bookings
        return $user->isAdmin() || $user->isTrainer();
    }

    /**
     * Determine whether the user can view the booking.
     */
    public function view(User $user, Booking $booking): bool
    {
        // Users can view their own bookings, admins/trainers can view any
        return $user->isAdmin()
            || $user->isTrainer()
            || $user->id === $booking->user_id;
    }

    /**
     * Determine whether the user can create bookings.
     */
    public function create(User $user): bool
    {
        // Any authenticated user can create bookings
        return true;
    }

    /**
     * Determine whether the user can update the booking.
     */
    public function update(User $user, Booking $booking): bool
    {
        // Admins and trainers can update any booking
        // Members can only update their own (within cancellation policy)
        return $user->isAdmin()
            || $user->isTrainer()
            || $user->id === $booking->user_id;
    }

    /**
     * Determine whether the user can delete the booking.
     */
    public function delete(User $user, Booking $booking): bool
    {
        // Only admins can delete bookings
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can cancel the booking.
     */
    public function cancel(User $user, Booking $booking): bool
    {
        // Users can cancel their own bookings, admins/trainers can cancel any
        return $user->isAdmin()
            || $user->isTrainer()
            || $user->id === $booking->user_id;
    }

    /**
     * Determine whether the user can mark the booking as attended.
     */
    public function markAttended(User $user, Booking $booking): bool
    {
        // Only admins and trainers can mark attendance
        return $user->isAdmin() || $user->isTrainer();
    }

    /**
     * Determine whether the user can manage the waitlist.
     */
    public function manageWaitlist(User $user): bool
    {
        return $user->isAdmin() || $user->isTrainer();
    }
}
