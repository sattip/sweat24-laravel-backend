<?php

namespace App\Policies;

use App\Models\CashRegisterEntry;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CashRegisterEntryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any cash register entries.
     */
    public function viewAny(User $user): bool
    {
        // Admins have full access, trainers have limited access
        return $user->canAccessLimitedFinancials();
    }

    /**
     * Determine whether the user can view the cash register entry.
     */
    public function view(User $user, CashRegisterEntry $entry): bool
    {
        // Admins can view any entry
        // Trainers can only view entries they created
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isTrainer()) {
            return $entry->created_by === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create cash register entries.
     */
    public function create(User $user): bool
    {
        // Admins and trainers can create entries
        return $user->isAdmin() || $user->isTrainer();
    }

    /**
     * Determine whether the user can update the cash register entry.
     */
    public function update(User $user, CashRegisterEntry $entry): bool
    {
        // Only admins can update entries (for audit trail)
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the cash register entry.
     */
    public function delete(User $user, CashRegisterEntry $entry): bool
    {
        // Only admins can delete entries
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view financial reports.
     */
    public function viewReports(User $user): bool
    {
        return $user->canAccessFinancials();
    }

    /**
     * Determine whether the user can export financial data.
     */
    public function export(User $user): bool
    {
        return $user->canAccessFinancials();
    }

    /**
     * Determine whether the user can open/close cash register sessions.
     */
    public function manageSessions(User $user): bool
    {
        return $user->isAdmin() || $user->isTrainer();
    }

    /**
     * Determine whether the user can reconcile the cash register.
     */
    public function reconcile(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can void an entry.
     */
    public function void(User $user, CashRegisterEntry $entry): bool
    {
        // Only admins can void entries
        return $user->isAdmin();
    }
}
