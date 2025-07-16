<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'activity_type',
        'model_type',
        'model_id',
        'action',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Activity types
    const TYPE_REGISTRATION = 'registration';
    const TYPE_LOGIN = 'login';
    const TYPE_LOGOUT = 'logout';
    const TYPE_BOOKING = 'booking';
    const TYPE_BOOKING_CANCELLATION = 'booking_cancellation';
    const TYPE_PAYMENT = 'payment';
    const TYPE_PACKAGE_PURCHASE = 'package_purchase';
    const TYPE_PACKAGE_EXPIRY = 'package_expiry';
    const TYPE_PACKAGE_RENEWAL = 'package_renewal';
    const TYPE_PACKAGE_FREEZE = 'package_freeze';
    const TYPE_PACKAGE_UNFREEZE = 'package_unfreeze';
    const TYPE_CLASS_CREATED = 'class_created';
    const TYPE_CLASS_UPDATED = 'class_updated';
    const TYPE_CLASS_CANCELLED = 'class_cancelled';
    const TYPE_USER_UPDATED = 'user_updated';
    const TYPE_EVALUATION_SUBMITTED = 'evaluation_submitted';

    /**
     * Get the user that performed the activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the model that the activity was performed on.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo('model');
    }

    /**
     * Get activity type label.
     */
    public function getActivityTypeLabelAttribute(): string
    {
        $labels = [
            self::TYPE_REGISTRATION => 'New Registration',
            self::TYPE_LOGIN => 'User Login',
            self::TYPE_LOGOUT => 'User Logout',
            self::TYPE_BOOKING => 'Class Booking',
            self::TYPE_BOOKING_CANCELLATION => 'Booking Cancellation',
            self::TYPE_PAYMENT => 'Payment',
            self::TYPE_PACKAGE_PURCHASE => 'Package Purchase',
            self::TYPE_PACKAGE_EXPIRY => 'Package Expired',
            self::TYPE_PACKAGE_RENEWAL => 'Package Renewal',
            self::TYPE_PACKAGE_FREEZE => 'Package Frozen',
            self::TYPE_PACKAGE_UNFREEZE => 'Package Unfrozen',
            self::TYPE_CLASS_CREATED => 'Class Created',
            self::TYPE_CLASS_UPDATED => 'Class Updated',
            self::TYPE_CLASS_CANCELLED => 'Class Cancelled',
            self::TYPE_USER_UPDATED => 'User Updated',
            self::TYPE_EVALUATION_SUBMITTED => 'Evaluation Submitted',
        ];

        return $labels[$this->activity_type] ?? ucfirst(str_replace('_', ' ', $this->activity_type));
    }

    /**
     * Get activity icon.
     */
    public function getActivityIconAttribute(): string
    {
        $icons = [
            self::TYPE_REGISTRATION => 'fas fa-user-plus',
            self::TYPE_LOGIN => 'fas fa-sign-in-alt',
            self::TYPE_LOGOUT => 'fas fa-sign-out-alt',
            self::TYPE_BOOKING => 'fas fa-calendar-check',
            self::TYPE_BOOKING_CANCELLATION => 'fas fa-calendar-times',
            self::TYPE_PAYMENT => 'fas fa-credit-card',
            self::TYPE_PACKAGE_PURCHASE => 'fas fa-shopping-cart',
            self::TYPE_PACKAGE_EXPIRY => 'fas fa-clock',
            self::TYPE_PACKAGE_RENEWAL => 'fas fa-sync',
            self::TYPE_PACKAGE_FREEZE => 'fas fa-pause',
            self::TYPE_PACKAGE_UNFREEZE => 'fas fa-play',
            self::TYPE_CLASS_CREATED => 'fas fa-plus-circle',
            self::TYPE_CLASS_UPDATED => 'fas fa-edit',
            self::TYPE_CLASS_CANCELLED => 'fas fa-ban',
            self::TYPE_USER_UPDATED => 'fas fa-user-edit',
            self::TYPE_EVALUATION_SUBMITTED => 'fas fa-star',
        ];

        return $icons[$this->activity_type] ?? 'fas fa-info-circle';
    }

    /**
     * Get activity color.
     */
    public function getActivityColorAttribute(): string
    {
        $colors = [
            self::TYPE_REGISTRATION => 'blue',
            self::TYPE_LOGIN => 'green',
            self::TYPE_LOGOUT => 'gray',
            self::TYPE_BOOKING => 'indigo',
            self::TYPE_BOOKING_CANCELLATION => 'red',
            self::TYPE_PAYMENT => 'green',
            self::TYPE_PACKAGE_PURCHASE => 'purple',
            self::TYPE_PACKAGE_EXPIRY => 'orange',
            self::TYPE_PACKAGE_RENEWAL => 'teal',
            self::TYPE_PACKAGE_FREEZE => 'yellow',
            self::TYPE_PACKAGE_UNFREEZE => 'lime',
            self::TYPE_CLASS_CREATED => 'blue',
            self::TYPE_CLASS_UPDATED => 'yellow',
            self::TYPE_CLASS_CANCELLED => 'red',
            self::TYPE_USER_UPDATED => 'gray',
            self::TYPE_EVALUATION_SUBMITTED => 'pink',
        ];

        return $colors[$this->activity_type] ?? 'gray';
    }

    /**
     * Scope for filtering by activity type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('activity_type', $type);
    }

    /**
     * Scope for filtering by date range.
     */
    public function scopeDateBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for filtering by user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
