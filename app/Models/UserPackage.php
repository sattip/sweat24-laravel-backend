<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'package_id',
        'name',
        'assigned_date',
        'expiry_date',
        'remaining_sessions',
        'total_sessions',
        'status',
        'is_frozen',
        'frozen_at',
        'unfrozen_at',
        'freeze_duration_days',
        'last_notification_sent_at',
        'notification_stage',
        'auto_renew',
        'renewed_from_package_id',
        'renewed_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_date' => 'date',
            'expiry_date' => 'date',
            'frozen_at' => 'datetime',
            'unfrozen_at' => 'datetime',
            'last_notification_sent_at' => 'datetime',
            'renewed_at' => 'datetime',
            'is_frozen' => 'boolean',
            'auto_renew' => 'boolean',
        ];
    }

    // Constants for notification stages
    const NOTIFICATION_7_DAYS = '7_days_before';
    const NOTIFICATION_3_DAYS = '3_days_before';
    const NOTIFICATION_EXPIRED = 'expired';

    // Constants for package statuses
    const STATUS_ACTIVE = 'active';
    const STATUS_PAUSED = 'paused';
    const STATUS_EXPIRED = 'expired';
    const STATUS_EXPIRING_SOON = 'expiring_soon';
    const STATUS_FROZEN = 'frozen';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function renewedFromPackage()
    {
        return $this->belongsTo(UserPackage::class, 'renewed_from_package_id');
    }

    public function renewals()
    {
        return $this->hasMany(UserPackage::class, 'renewed_from_package_id');
    }

    public function history()
    {
        return $this->hasMany(PackageHistory::class);
    }

    public function notificationLogs()
    {
        return $this->hasMany(PackageNotificationLog::class);
    }

    // Helper methods
    public function getDaysUntilExpiry()
    {
        if (!$this->expiry_date) {
            return null;
        }
        
        return now()->diffInDays($this->expiry_date, false);
    }

    public function isExpiringSoon()
    {
        $days = $this->getDaysUntilExpiry();
        return $days !== null && $days > 0 && $days <= 7;
    }

    public function isExpired()
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function canBeUsed()
    {
        return $this->status === self::STATUS_ACTIVE && 
               !$this->is_frozen && 
               !$this->isExpired() &&
               $this->remaining_sessions > 0;
    }

    public function freeze($durationDays = null)
    {
        $this->update([
            'is_frozen' => true,
            'frozen_at' => now(),
            'freeze_duration_days' => $durationDays,
            'status' => self::STATUS_FROZEN,
        ]);

        // Log the action
        $this->logHistory('frozen', [
            'duration_days' => $durationDays,
        ]);
    }

    public function unfreeze()
    {
        if (!$this->is_frozen) {
            return false;
        }

        // Calculate new expiry date if freeze had duration
        if ($this->frozen_at && $this->freeze_duration_days) {
            $frozenDays = $this->frozen_at->diffInDays(now());
            $daysToExtend = min($frozenDays, $this->freeze_duration_days);
            $newExpiryDate = $this->expiry_date->addDays($daysToExtend);
        } else {
            $newExpiryDate = $this->expiry_date;
        }

        $this->update([
            'is_frozen' => false,
            'unfrozen_at' => now(),
            'status' => $this->isExpired() ? self::STATUS_EXPIRED : self::STATUS_ACTIVE,
            'expiry_date' => $newExpiryDate,
        ]);

        // Log the action
        $this->logHistory('unfrozen', [
            'new_expiry_date' => $newExpiryDate,
        ]);

        return true;
    }

    public function renew($newPackageId = null, $additionalSessions = null)
    {
        $newPackageId = $newPackageId ?: $this->package_id;
        $package = Package::find($newPackageId);
        
        if (!$package) {
            return false;
        }

        // Create new user package
        $newUserPackage = self::create([
            'user_id' => $this->user_id,
            'package_id' => $newPackageId,
            'name' => $package->name,
            'assigned_date' => now(),
            'expiry_date' => now()->addDays($package->duration),
            'remaining_sessions' => $additionalSessions ?: $package->sessions,
            'total_sessions' => $additionalSessions ?: $package->sessions,
            'status' => self::STATUS_ACTIVE,
            'renewed_from_package_id' => $this->id,
            'renewed_at' => now(),
        ]);

        // Update current package status
        $this->update(['status' => self::STATUS_EXPIRED]);

        // Log the renewal
        $newUserPackage->logHistory('renewed', [
            'previous_package_id' => $this->id,
            'upgrade' => $newPackageId !== $this->package_id,
        ]);

        return $newUserPackage;
    }

    public function updateLifecycleStatus()
    {
        if ($this->is_frozen) {
            $this->status = self::STATUS_FROZEN;
        } elseif ($this->isExpired()) {
            $this->status = self::STATUS_EXPIRED;
        } elseif ($this->isExpiringSoon()) {
            $this->status = self::STATUS_EXPIRING_SOON;
        } else {
            $this->status = self::STATUS_ACTIVE;
        }

        $this->save();
    }

    public function logHistory($action, $notes = [])
    {
        PackageHistory::create([
            'user_package_id' => $this->id,
            'user_id' => $this->user_id,
            'action' => $action,
            'previous_status' => $this->getOriginal('status'),
            'new_status' => $this->status,
            'sessions_before' => $this->getOriginal('remaining_sessions'),
            'sessions_after' => $this->remaining_sessions,
            'expiry_date_before' => $this->getOriginal('expiry_date'),
            'expiry_date_after' => $this->expiry_date,
            'notes' => json_encode($notes),
            'performed_by' => auth()->id(),
        ]);
    }

    public function logNotification($type, $channel, $success = true, $error = null)
    {
        PackageNotificationLog::create([
            'user_package_id' => $this->id,
            'user_id' => $this->user_id,
            'notification_type' => $type,
            'channel' => $channel,
            'sent_successfully' => $success,
            'error_message' => $error,
            'days_until_expiry' => $this->getDaysUntilExpiry(),
        ]);
    }
}