<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * Log an activity.
     *
     * @param string $activityType
     * @param string $action
     * @param Model|null $subject
     * @param array $properties
     * @param int|null $userId
     * @return ActivityLog
     */
    public static function log(
        string $activityType,
        string $action,
        ?Model $subject = null,
        array $properties = [],
        ?int $userId = null
    ): ActivityLog {
        $userId = $userId ?? Auth::id();

        // Skip logging if no user ID is available
        if (!$userId) {
            return new ActivityLog();
        }

        $data = [
            'user_id' => $userId,
            'activity_type' => $activityType,
            'action' => $action,
            'properties' => $properties,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ];

        if ($subject) {
            $data['model_type'] = get_class($subject);
            $data['model_id'] = $subject->getKey();
        }

        return ActivityLog::create($data);
    }

    /**
     * Log a user registration.
     */
    public static function logRegistration(Model $user): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_REGISTRATION,
            "New user registered: {$user->name}",
            $user,
            [
                'user_name' => $user->name,
                'user_email' => $user->email,
                'membership_type' => $user->membership_type,
            ],
            $user->id
        );
    }

    /**
     * Log a user login.
     */
    public static function logLogin(Model $user): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_LOGIN,
            "User logged in: {$user->name}",
            $user,
            [
                'user_name' => $user->name,
                'user_email' => $user->email,
            ],
            $user->id
        );
    }

    /**
     * Log a user logout.
     */
    public static function logLogout(Model $user): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_LOGOUT,
            "User logged out: {$user->name}",
            $user,
            [
                'user_name' => $user->name,
            ],
            $user->id
        );
    }

    /**
     * Log a booking creation.
     */
    public static function logBooking(Model $booking): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_BOOKING,
            "Class booked: {$booking->gymClass->title}",
            $booking,
            [
                'class_title' => $booking->gymClass->title,
                'class_date' => $booking->gymClass->start_time->format('Y-m-d H:i'),
                'user_name' => $booking->user->name,
            ]
        );
    }

    /**
     * Log a booking cancellation.
     */
    public static function logBookingCancellation(Model $booking): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_BOOKING_CANCELLATION,
            "Booking cancelled: {$booking->gymClass->title}",
            $booking,
            [
                'class_title' => $booking->gymClass->title,
                'class_date' => $booking->gymClass->start_time->format('Y-m-d H:i'),
                'user_name' => $booking->user->name,
                'reason' => $booking->cancellation_reason ?? 'No reason provided',
            ]
        );
    }

    /**
     * Log a payment.
     */
    public static function logPayment(Model $payment, float $amount, string $description): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_PAYMENT,
            "Payment received: €{$amount}",
            $payment,
            [
                'amount' => $amount,
                'description' => $description,
                'payment_method' => $payment->payment_method ?? 'Unknown',
            ]
        );
    }

    /**
     * Log a package purchase.
     */
    public static function logPackagePurchase(Model $userPackage): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_PACKAGE_PURCHASE,
            "Package purchased: {$userPackage->package->name}",
            $userPackage,
            [
                'package_name' => $userPackage->package->name,
                'user_name' => $userPackage->user->name,
                'price' => $userPackage->package->price,
                'duration_months' => $userPackage->package->duration_months,
            ]
        );
    }

    /**
     * Log a package expiry.
     */
    public static function logPackageExpiry(Model $userPackage): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_PACKAGE_EXPIRY,
            "Package expired: {$userPackage->package->name}",
            $userPackage,
            [
                'package_name' => $userPackage->package->name,
                'user_name' => $userPackage->user->name,
                'expired_at' => $userPackage->expiry_date,
            ]
        );
    }

    /**
     * Log a package renewal.
     */
    public static function logPackageRenewal(Model $userPackage): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_PACKAGE_RENEWAL,
            "Package renewed: {$userPackage->package->name}",
            $userPackage,
            [
                'package_name' => $userPackage->package->name,
                'user_name' => $userPackage->user->name,
                'new_expiry_date' => $userPackage->expiry_date,
            ]
        );
    }

    /**
     * Log a package freeze.
     */
    public static function logPackageFreeze(Model $userPackage): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_PACKAGE_FREEZE,
            "Package frozen: {$userPackage->package->name}",
            $userPackage,
            [
                'package_name' => $userPackage->package->name,
                'user_name' => $userPackage->user->name,
                'frozen_until' => $userPackage->frozen_until,
            ]
        );
    }

    /**
     * Log a package unfreeze.
     */
    public static function logPackageUnfreeze(Model $userPackage): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_PACKAGE_UNFREEZE,
            "Package unfrozen: {$userPackage->package->name}",
            $userPackage,
            [
                'package_name' => $userPackage->package->name,
                'user_name' => $userPackage->user->name,
            ]
        );
    }

    /**
     * Log a class creation.
     */
    public static function logClassCreated(Model $gymClass): ActivityLog|null
    {
        // Skip logging during seeding if no authenticated user
        if (!auth()->check()) {
            return null;
        }
        
        return self::log(
            ActivityLog::TYPE_CLASS_CREATED,
            "Class created: {$gymClass->title}",
            $gymClass,
            [
                'class_title' => $gymClass->title,
                'instructor' => $gymClass->instructor->name ?? 'No instructor',
                'start_time' => $gymClass->start_time ? $gymClass->start_time->format('Y-m-d H:i') : 'No start time',
            ]
        );
    }

    /**
     * Log a class update.
     */
    public static function logClassUpdated(Model $gymClass, array $changes = []): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_CLASS_UPDATED,
            "Class updated: {$gymClass->title}",
            $gymClass,
            [
                'class_title' => $gymClass->title,
                'changes' => $changes,
            ]
        );
    }

    /**
     * Log a class cancellation.
     */
    public static function logClassCancelled(Model $gymClass): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_CLASS_CANCELLED,
            "Class cancelled: {$gymClass->title}",
            $gymClass,
            [
                'class_title' => $gymClass->title,
                'start_time' => $gymClass->start_time ? $gymClass->start_time->format('Y-m-d H:i') : 'No start time',
                'affected_bookings' => $gymClass->bookings()->count(),
            ]
        );
    }

    /**
     * Log a user update.
     */
    public static function logUserUpdated(Model $user, array $changes = []): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_USER_UPDATED,
            "User profile updated: {$user->name}",
            $user,
            [
                'user_name' => $user->name,
                'changes' => $changes,
            ]
        );
    }

    /**
     * Log an evaluation submission.
     */
    public static function logEvaluationSubmitted(Model $evaluation): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_EVALUATION_SUBMITTED,
            "Evaluation submitted for class: {$evaluation->gymClass->title}",
            $evaluation,
            [
                'class_title' => $evaluation->gymClass->title,
                'user_name' => $evaluation->user->name,
                'rating' => $evaluation->rating,
            ]
        );
    }
}