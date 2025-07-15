<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Booking;
use App\Models\UserPackage;
use App\Models\GymClass;
use App\Models\ClassEvaluation;
use App\Services\ActivityLogger;
use Illuminate\Support\ServiceProvider;

class ActivityLogServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Listen for User model events
        User::created(function ($user) {
            // Skip auto-logging for registration as it's handled in controller
            // to avoid double logging
        });

        User::updated(function ($user) {
            if ($user->wasChanged() && !$user->wasRecentlyCreated) {
                $changes = $user->getChanges();
                unset($changes['updated_at']); // Remove timestamp from changes
                
                if (!empty($changes)) {
                    ActivityLogger::logUserUpdated($user, $changes);
                }
            }
        });

        // Listen for Booking model events
        Booking::created(function ($booking) {
            // Skip auto-logging for bookings as it's handled in controller
            // to avoid double logging
        });

        // Listen for UserPackage model events
        UserPackage::created(function ($userPackage) {
            ActivityLogger::logPackagePurchase($userPackage);
        });

        UserPackage::updated(function ($userPackage) {
            if ($userPackage->wasChanged('frozen_until')) {
                if ($userPackage->frozen_until) {
                    ActivityLogger::logPackageFreeze($userPackage);
                } else {
                    ActivityLogger::logPackageUnfreeze($userPackage);
                }
            }
            
            if ($userPackage->wasChanged('expiry_date') && !$userPackage->wasRecentlyCreated) {
                ActivityLogger::logPackageRenewal($userPackage);
            }
        });

        // Listen for GymClass model events
        GymClass::created(function ($gymClass) {
            ActivityLogger::logClassCreated($gymClass);
        });

        GymClass::updated(function ($gymClass) {
            if ($gymClass->wasChanged() && !$gymClass->wasRecentlyCreated) {
                $changes = $gymClass->getChanges();
                unset($changes['updated_at']); // Remove timestamp from changes
                
                if (!empty($changes)) {
                    ActivityLogger::logClassUpdated($gymClass, $changes);
                }
            }
        });

        GymClass::deleted(function ($gymClass) {
            ActivityLogger::logClassCancelled($gymClass);
        });

        // Listen for ClassEvaluation model events
        ClassEvaluation::created(function ($evaluation) {
            ActivityLogger::logEvaluationSubmitted($evaluation);
        });
    }
}