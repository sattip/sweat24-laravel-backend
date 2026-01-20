<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        // Admins and trainers can view user list
        return $user->isAdmin() || $user->isTrainer();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Users can view their own profile
        // Admins and trainers can view any user
        return $user->isAdmin()
            || $user->isTrainer()
            || $user->id === $model->id;
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        // Only admins can create users directly
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Users can update their own profile
        // Admins can update any user
        return $user->isAdmin() || $user->id === $model->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Only admins can delete users
        // Cannot delete yourself
        return $user->isAdmin() && $user->id !== $model->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        // Only super admin can force delete
        return $user->isAdmin() && $user->id === 1 && $user->id !== $model->id;
    }

    /**
     * Determine whether the user can approve registrations.
     */
    public function approve(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can reject registrations.
     */
    public function reject(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can change roles.
     */
    public function changeRole(User $user, User $model): bool
    {
        // Only admins can change roles
        // Cannot change your own role
        return $user->isAdmin() && $user->id !== $model->id;
    }

    /**
     * Determine whether the user can view financial information.
     */
    public function viewFinancials(User $user, User $model): bool
    {
        // Users can view their own financial info
        // Admins can view any user's financial info
        // Trainers have limited financial access
        return $user->isAdmin()
            || $user->id === $model->id
            || ($user->isTrainer() && $user->canAccessLimitedFinancials());
    }

    /**
     * Determine whether the user can impersonate another user.
     */
    public function impersonate(User $user, User $model): bool
    {
        // Only super admin can impersonate
        // Cannot impersonate yourself or other admins
        return $user->isAdmin()
            && $user->id === 1
            && $user->id !== $model->id
            && !$model->isAdmin();
    }
}
