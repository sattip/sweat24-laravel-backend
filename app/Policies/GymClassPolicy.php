<?php

namespace App\Policies;

use App\Models\GymClass;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GymClassPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any gym classes.
     */
    public function viewAny(?User $user): bool
    {
        // Anyone can view classes (even unauthenticated)
        return true;
    }

    /**
     * Determine whether the user can view the gym class.
     */
    public function view(?User $user, GymClass $gymClass): bool
    {
        // Anyone can view class details
        return true;
    }

    /**
     * Determine whether the user can create gym classes.
     */
    public function create(User $user): bool
    {
        // Admins and trainers can create classes
        return $user->isAdmin() || $user->isTrainer();
    }

    /**
     * Determine whether the user can update the gym class.
     */
    public function update(User $user, GymClass $gymClass): bool
    {
        // Admins and trainers can update classes
        // Note: Since gym_classes uses a string 'instructor' field (not instructor_id FK),
        // we allow all trainers to update classes
        return $user->isAdmin() || $user->isTrainer();
    }

    /**
     * Determine whether the user can delete the gym class.
     */
    public function delete(User $user, GymClass $gymClass): bool
    {
        // Only admins can delete classes
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the gym class.
     */
    public function restore(User $user, GymClass $gymClass): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the gym class.
     */
    public function forceDelete(User $user, GymClass $gymClass): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can manage class schedule.
     */
    public function manageSchedule(User $user): bool
    {
        return $user->isAdmin() || $user->isTrainer();
    }

    /**
     * Determine whether the user can view class attendees.
     */
    public function viewAttendees(User $user, GymClass $gymClass): bool
    {
        // Admins and trainers can view class attendees
        return $user->isAdmin() || $user->isTrainer();
    }

    /**
     * Determine whether the user can cancel the class.
     */
    public function cancel(User $user, GymClass $gymClass): bool
    {
        // Admins and trainers can cancel classes
        return $user->isAdmin() || $user->isTrainer();
    }
}
