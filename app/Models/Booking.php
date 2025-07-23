<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'customer_name',
        'customer_email',
        'class_id',
        'class_name',
        'instructor',
        'date',
        'time',
        'status',
        'type',
        'booking_type',
        'attended',
        'booking_time',
        'location',
        'avatar',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'booking_time' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gymClass()
    {
        return $this->belongsTo(GymClass::class, 'class_id');
    }

    // Add accessors for better formatting
    protected $appends = ['instructor_name', 'checked_in', 'is_waitlist'];

    public function getInstructorNameAttribute()
    {
        return $this->instructor ?? 'N/A';
    }

    public function getCheckedInAttribute()
    {
        return $this->attended ?? false;
    }

    public function getIsWaitlistAttribute()
    {
        return $this->status === 'waitlist';
    }

    // Format time as HH:MM if it's a datetime
    public function getTimeAttribute($value)
    {
        if ($value && strlen($value) > 5) {
            // If it's a full datetime, extract just the time part
            return \Carbon\Carbon::parse($value)->format('H:i');
        }
        return $value;
    }
}