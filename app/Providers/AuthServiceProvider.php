<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\CashRegisterEntry;
use App\Models\GymClass;
use App\Models\Package;
use App\Models\User;
use App\Models\UserPackage;
use App\Policies\BookingPolicy;
use App\Policies\CashRegisterEntryPolicy;
use App\Policies\GymClassPolicy;
use App\Policies\PackagePolicy;
use App\Policies\UserPackagePolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Booking::class => BookingPolicy::class,
        Package::class => PackagePolicy::class,
        UserPackage::class => UserPackagePolicy::class,
        GymClass::class => GymClassPolicy::class,
        CashRegisterEntry::class => CashRegisterEntryPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
        $this->registerGates();
    }

    /**
     * Register authorization gates.
     */
    protected function registerGates(): void
    {
        // Admin-only gate
        Gate::define('admin', function (User $user) {
            return $user->isAdmin();
        });

        // Trainer or admin gate
        Gate::define('trainer', function (User $user) {
            return $user->isAdmin() || $user->isTrainer();
        });

        // User management gate
        Gate::define('manage-users', function (User $user) {
            return $user->isAdmin();
        });

        // Financial access gate (full access)
        Gate::define('access-financials', function (User $user) {
            return $user->canAccessFinancials();
        });

        // Limited financial access gate (for trainers)
        Gate::define('access-limited-financials', function (User $user) {
            return $user->canAccessLimitedFinancials();
        });

        // Class management gate
        Gate::define('manage-classes', function (User $user) {
            return $user->isAdmin() || $user->isTrainer();
        });

        // Booking management gate
        Gate::define('manage-bookings', function (User $user) {
            return $user->isAdmin() || $user->isTrainer();
        });

        // Package management gate
        Gate::define('manage-packages', function (User $user) {
            return $user->isAdmin();
        });

        // View reports gate
        Gate::define('view-reports', function (User $user) {
            return $user->isAdmin();
        });

        // Manage settings gate
        Gate::define('manage-settings', function (User $user) {
            return $user->isAdmin();
        });

        // Cash register gate
        Gate::define('access-cash-register', function (User $user) {
            return $user->isAdmin() || $user->isTrainer();
        });

        // Approve users gate
        Gate::define('approve-users', function (User $user) {
            return $user->isAdmin();
        });

        // Super admin gate (for dangerous operations)
        Gate::define('super-admin', function (User $user) {
            return $user->isSuperAdmin();
        });
    }
}
