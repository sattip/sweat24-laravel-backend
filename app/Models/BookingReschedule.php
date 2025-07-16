<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingReschedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'user_id',
        'original_class_id',
        'new_class_id',
        'original_datetime',
        'new_datetime',
        'reason',
        'status',
        'requested_at',
        'processed_at',
        'processed_by',
        'admin_notes',
    ];

    protected $casts = [
        'original_datetime' => 'datetime',
        'new_datetime' => 'datetime',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function originalClass()
    {
        return $this->belongsTo(GymClass::class, 'original_class_id');
    }

    public function newClass()
    {
        return $this->belongsTo(GymClass::class, 'new_class_id');
    }

    public function processedByUser()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function getRescheduleCountForMonth($userId, $month = null)
    {
        $month = $month ?? now();
        
        return self::where('user_id', $userId)
            ->where('status', 'approved')
            ->whereMonth('requested_at', $month->month)
            ->whereYear('requested_at', $month->year)
            ->count();
    }
}