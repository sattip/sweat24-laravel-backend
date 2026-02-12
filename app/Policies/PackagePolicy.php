<?php

namespace App\Policies;

use App\Models\Package;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PackagePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any packages.
     */
    public function viewAny(User $user): bool
    {
        // Anyone can view available packages
        return true;
    }

    /**
     * Determine whether the user can view the package.
     */
    public function view(User $user, Package $package): bool
    {
        // Anyone can view package details
        return true;
    }

    /**
     * Determine whether the user can create packages.
     */
    public function create(User $user): bool
    {
        // Only admins can create packages
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the package.
     */
    public function update(User $user, Package $package): bool
    {
        // Only admins can update packages
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the package.
     */
    public function delete(User $user, Package $package): bool
    {
        // Only admins can delete packages
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the package.
     */
    public function restore(User $user, Package $package): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the package.
     */
    public function forceDelete(User $user, Package $package): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can assign packages to users.
     */
    public function assign(User $user): bool
    {
        // Admins and trainers can assign packages
        return $user->isAdmin() || $user->isTrainer();
    }
}
