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
        'bonus_sessions',
        'bonus_sessions_used',
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
        // Custom package fields
        'is_custom_package',
        'custom_price',
        'custom_sessions',
        'custom_duration_days',
        'custom_notes',
        'assigned_by',
        'custom_assigned_at',
        // Payment fields
        'payment_method',
        'payment_status',
        'amount_paid',
        'amount_remaining',
        'installments',
        'payment_notes',
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
            'custom_assigned_at' => 'datetime',
            'is_frozen' => 'boolean',
            'auto_renew' => 'boolean',
            'is_custom_package' => 'boolean',
            'custom_price' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'amount_remaining' => 'decimal:2',
        ];
    }

    // Constants for payment methods
    const PAYMENT_METHOD_CASH = 'cash';
    const PAYMENT_METHOD_CARD = 'card';
    const PAYMENT_METHOD_BANK_TRANSFER = 'bank_transfer';
    const PAYMENT_METHOD_OTHER = 'other';

    // Constants for payment statuses
    const PAYMENT_STATUS_PENDING = 'pending';
    const PAYMENT_STATUS_PARTIAL = 'partial';
    const PAYMENT_STATUS_PAID = 'fully_paid';

    // Constants for notification stages
    const NOTIFICATION_7_DAYS = '7_days_before';
    const NOTIFICATION_3_DAYS = '3_days_before';
    const NOTIFICATION_EXPIRED = 'expired';

    // Constants for package statuses
    const STATUS_ACTIVE = 'active';
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
        // Convert Carbon objects to strings to avoid JSON encoding issues
        $processedNotes = $this->processNotesForJson($notes);

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
            'notes' => json_encode($processedNotes),
            'performed_by' => auth()->id(),
        ]);
    }

    /**
     * Process notes array to handle Carbon objects for JSON encoding
     */
    private function processNotesForJson($notes)
    {
        if (!is_array($notes)) {
            return $notes;
        }

        $processed = [];
        foreach ($notes as $key => $value) {
            if ($value instanceof \Carbon\Carbon) {
                $processed[$key] = $value->toISOString();
            } elseif (is_array($value)) {
                $processed[$key] = $this->processNotesForJson($value);
            } else {
                $processed[$key] = $value;
            }
        }

        return $processed;
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

    // ===== CUSTOM PACKAGE METHODS =====

    /**
     * Check if this is a custom package
     */
    public function isCustomPackage()
    {
        return $this->is_custom_package;
    }

    /**
     * Get the effective price (custom or original)
     */
    public function getEffectivePrice()
    {
        return $this->is_custom_package ? $this->custom_price : $this->package->price;
    }

    /**
     * Get the effective sessions (custom or original)
     */
    public function getEffectiveSessions()
    {
        return $this->is_custom_package ? $this->custom_sessions : $this->package->sessions;
    }

    /**
     * Get the effective duration in days (custom or original)
     */
    public function getEffectiveDurationDays()
    {
        return $this->is_custom_package ? $this->custom_duration_days : $this->package->duration;
    }

    /**
     * Calculate savings for custom package
     */
    public function getSavings()
    {
        if (!$this->is_custom_package) {
            return 0;
        }

        $originalPrice = $this->package->price;
        $customPrice = $this->custom_price;

        return max(0, $originalPrice - $customPrice);
    }

    /**
     * Get custom package summary
     */
    public function getCustomPackageSummary()
    {
        if (!$this->is_custom_package) {
            return null;
        }

        return [
            'custom_price' => $this->custom_price,
            'custom_sessions' => $this->custom_sessions,
            'custom_duration_days' => $this->custom_duration_days,
            'custom_notes' => $this->custom_notes,
            'assigned_by' => $this->assigned_by,
            'assigned_at' => $this->custom_assigned_at,
            'savings' => $this->getSavings(),
            'original_price' => $this->package->price,
            'original_sessions' => $this->package->sessions,
            'original_duration' => $this->package->duration,
        ];
    }

    /**
     * Scope for custom packages only
     */
    public function scopeCustomPackages($query)
    {
        return $query->where('is_custom_package', true);
    }

    /**
     * Scope for regular packages only
     */
    public function scopeRegularPackages($query)
    {
        return $query->where('is_custom_package', false);
    }

    /**
     * Check if user has special pricing for a specific service
     */
    public static function hasSpecialPricingForService($userId, $serviceId)
    {
        return static::where('user_id', $userId)
            ->where('is_custom_package', true)
            ->whereHas('package', function($query) use ($serviceId) {
                $query->where('service_id', $serviceId);
            })
            ->exists();
    }

    /**
     * Get user's special pricing details for a service
     */
    public static function getSpecialPricingForService($userId, $serviceId)
    {
        $customPackage = static::where('user_id', $userId)
            ->where('is_custom_package', true)
            ->whereHas('package', function($query) use ($serviceId) {
                $query->where('service_id', $serviceId);
            })
            ->with('package.service')
            ->first();

        if (!$customPackage) {
            return null;
        }

        return [
            'has_special_pricing' => true,
            'custom_package_id' => $customPackage->id,
            'package_name' => $customPackage->package->name,
            'service_name' => $customPackage->package->service->name,
            'original_price' => $customPackage->package->price,
            'custom_price' => $customPackage->custom_price,
            'price_difference' => $customPackage->package->price - $customPackage->custom_price,
            'original_sessions' => $customPackage->package->sessions,
            'custom_sessions' => $customPackage->custom_sessions,
            'sessions_difference' => $customPackage->custom_sessions - $customPackage->package->sessions,
            'original_duration' => $customPackage->package->duration,
            'custom_duration' => $customPackage->custom_duration_days,
            'duration_difference' => $customPackage->custom_duration_days - $customPackage->package->duration,
            'remaining_sessions' => $customPackage->remaining_sessions,
            'expiry_date' => $customPackage->expiry_date,
            'assigned_by' => $customPackage->assigned_by,
            'assigned_at' => $customPackage->custom_assigned_at,
            'custom_notes' => $customPackage->custom_notes,
        ];
    }

    /**
     * Get all user's special pricing packages
     */
    public static function getUserSpecialPricingSummary($userId)
    {
        $customPackages = static::where('user_id', $userId)
            ->where('is_custom_package', true)
            ->with('package.service')
            ->get();

        $summary = [
            'has_special_pricing' => $customPackages->count() > 0,
            'total_custom_packages' => $customPackages->count(),
            'total_savings' => 0,
            'packages' => []
        ];

        foreach ($customPackages as $customPackage) {
            $savings = $customPackage->package->price - $customPackage->custom_price;
            $summary['total_savings'] += $savings;

            $summary['packages'][] = [
                'package_id' => $customPackage->id,
                'package_name' => $customPackage->package->name,
                'service_name' => $customPackage->package->service->name,
                'original_price' => $customPackage->package->price,
                'custom_price' => $customPackage->custom_price,
                'savings' => $savings,
                'remaining_sessions' => $customPackage->remaining_sessions,
                'expiry_date' => $customPackage->expiry_date,
                'status' => $customPackage->status,
            ];
        }

        return $summary;
    }

    /**
     * Get special pricing comparison for all user's packages
     */
    public static function getUserSpecialPricingComparison($userId)
    {
        // Get all user's packages (both regular and custom)
        $allPackages = static::where('user_id', $userId)
            ->with('package.service')
            ->get();

        $comparison = [
            'user_id' => (int) $userId,
            'total_packages' => $allPackages->count(),
            'custom_packages_count' => $allPackages->where('is_custom_package', true)->count(),
            'regular_packages_count' => $allPackages->where('is_custom_package', false)->count(),
            'packages' => []
        ];

        foreach ($allPackages as $userPackage) {
            $packageData = [
                'package_id' => $userPackage->id,
                'package_name' => $userPackage->package->name,
                'service_name' => $userPackage->package->service->name,
                'is_custom_package' => $userPackage->is_custom_package,
                'status' => $userPackage->status,
                'remaining_sessions' => $userPackage->remaining_sessions,
                'expiry_date' => $userPackage->expiry_date,
            ];

            if ($userPackage->is_custom_package) {
                $packageData['pricing'] = [
                    'type' => 'custom',
                    'original_price' => $userPackage->package->price,
                    'custom_price' => $userPackage->custom_price,
                    'savings' => $userPackage->package->price - $userPackage->custom_price,
                    'original_sessions' => $userPackage->package->sessions,
                    'custom_sessions' => $userPackage->custom_sessions,
                    'original_duration' => $userPackage->package->duration,
                    'custom_duration' => $userPackage->custom_duration_days,
                ];
            } else {
                $packageData['pricing'] = [
                    'type' => 'standard',
                    'price' => $userPackage->package->price,
                    'sessions' => $userPackage->package->sessions,
                    'duration' => $userPackage->package->duration,
                ];
            }

            $comparison['packages'][] = $packageData;
        }

        return $comparison;
    }

    // ===== PAYMENT METHODS =====

    /**
     * Check if package is fully paid
     */
    public function isFullyPaid()
    {
        return $this->payment_status === self::PAYMENT_STATUS_PAID && $this->amount_remaining <= 0;
    }

    /**
     * Check if package has partial payment
     */
    public function hasPartialPayment()
    {
        return $this->payment_status === self::PAYMENT_STATUS_PARTIAL && $this->amount_paid > 0 && $this->amount_remaining > 0;
    }

    /**
     * Check if payment is pending
     */
    public function isPaymentPending()
    {
        return $this->payment_status === self::PAYMENT_STATUS_PENDING || $this->amount_remaining > 0;
    }

    /**
     * Record a payment
     */
    public function recordPayment($amount, $method = null, $notes = null)
    {
        $this->amount_paid += $amount;
        $totalAmount = $this->getEffectivePrice();
        $this->amount_remaining = max(0, $totalAmount - $this->amount_paid);

        if ($this->amount_remaining <= 0) {
            $this->payment_status = self::PAYMENT_STATUS_PAID;
        } elseif ($this->amount_paid > 0) {
            $this->payment_status = self::PAYMENT_STATUS_PARTIAL;
        }

        if ($method) {
            $this->payment_method = $method;
        }

        if ($notes) {
            $existingNotes = $this->payment_notes ? $this->payment_notes . "\n" : '';
            $this->payment_notes = $existingNotes . date('d/m/Y H:i') . ': ' . $notes;
        }

        $this->save();

        return $this;
    }

    /**
     * Get payment summary
     */
    public function getPaymentSummary()
    {
        $totalAmount = $this->getEffectivePrice();

        return [
            'total_amount' => $totalAmount,
            'amount_paid' => $this->amount_paid,
            'amount_remaining' => $this->amount_remaining,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'is_fully_paid' => $this->isFullyPaid(),
            'has_partial_payment' => $this->hasPartialPayment(),
            'is_pending' => $this->isPaymentPending(),
            'installments' => $this->installments,
            'payment_notes' => $this->payment_notes,
        ];
    }
}