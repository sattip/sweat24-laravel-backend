<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'service_type',
        'instructor_id',
        'client_name',
        'client_email',
        'client_phone',
        'preferred_time_slots',
        'notes',
        'status',
        'admin_notes',
        'confirmed_date',
        'confirmed_time',
        'rejection_reason',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'preferred_time_slots' => 'array',
        'confirmed_date' => 'date',
        'processed_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_COMPLETED = 'completed';

    // Service type constants
    const SERVICE_EMS = 'ems';
    const SERVICE_PERSONAL = 'personal';

    /**
     * Get the user who made the request
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the instructor assigned to this request
     */
    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }

    /**
     * Get the admin who processed this request
     */
    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for confirmed requests
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    /**
     * Check if request is pending
     */
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if request is confirmed
     */
    public function isConfirmed()
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    /**
     * Confirm the booking request
     */
    public function confirm($date, $time, $instructorId = null, $adminNotes = null)
    {
        $this->update([
            'status' => self::STATUS_CONFIRMED,
            'confirmed_date' => $date,
            'confirmed_time' => $time,
            'instructor_id' => $instructorId ?: $this->instructor_id,
            'admin_notes' => $adminNotes,
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);
    }

    /**
     * Reject the booking request
     */
    public function reject($reason, $adminNotes = null)
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'admin_notes' => $adminNotes,
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);
    }

    /**
     * Cancel the booking request
     */
    public function cancel($reason = null)
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Mark the booking request as completed
     */
    public function markAsCompleted()
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
        ]);
    }

    /**
     * Get available service types
     */
    public static function getServiceTypes()
    {
        return [
            self::SERVICE_EMS => 'EMS Training',
            self::SERVICE_PERSONAL => 'Personal Training',
        ];
    }

    /**
     * Get all statuses
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_COMPLETED => 'Completed',
        ];
    }
} 