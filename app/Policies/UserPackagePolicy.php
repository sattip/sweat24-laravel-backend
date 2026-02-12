<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPackagePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any user packages.
     */
    public function viewAny(User $user): bool
    {
        // Admins and trainers can view all user packages
        return $user->isAdmin() || $user->isTrainer();
    }

    /**
     * Determine whether the user can view the user package.
     */
    public function view(User $user, UserPackage $userPackage): bool
    {
        // Users can view their own packages
        // Admins and trainers can view any
        return $user->isAdmin()
            || $user->isTrainer()
            || $user->id === $userPackage->user_id;
    }

    /**
     * Determine whether the user can create user packages.
     */
    public function create(User $user): bool
    {
        // Admins and trainers can assign packages
        return $user->isAdmin() || $user->isTrainer();
    }

    /**
     * Determine whether the user can update the user package.
     */
    public function update(User $user, UserPackage $userPackage): bool
    {
        // Admins and trainers can update packages
        return $user->isAdmin() || $user->isTrainer();
    }

    /**
     * Determine whether the user can delete the user package.
     */
    public function delete(User $user, UserPackage $userPackage): bool
    {
        // Only admins can delete user packages
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can freeze the package.
     */
    public function freeze(User $user, UserPackage $userPackage): bool
    {
        // Users can freeze their own packages
        // Admins and trainers can freeze any
        return $user->isAdmin()
            || $user->isTrainer()
            || $user->id === $userPackage->user_id;
    }

    /**
     * Determine whether the user can unfreeze the package.
     */
    public function unfreeze(User $user, UserPackage $userPackage): bool
    {
        return $this->freeze($user, $userPackage);
    }

    /**
     * Determine whether the user can record a payment.
     */
    public function recordPayment(User $user, UserPackage $userPackage): bool
    {
        // Only admins and trainers can record payments
        return $user->isAdmin() || $user->isTrainer();
    }

    /**
     * Determine whether the user can view payment history.
     */
    public function viewPaymentHistory(User $user, UserPackage $userPackage): bool
    {
        // Users can view their own payment history
        // Admins can view any
        return $user->isAdmin() || $user->id === $userPackage->user_id;
    }
}
