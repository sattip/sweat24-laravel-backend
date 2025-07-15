<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GymClass extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'instructor',
        'instructor_id',
        'date',
        'time',
        'duration',
        'max_participants',
        'current_participants',
        'location',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
        ];
    }

    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }
    
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'class_id');
    }
    
    public function waitlist()
    {
        return $this->hasMany(ClassWaitlist::class, 'class_id')->orderBy('position');
    }
    
    public function activeWaitlist()
    {
        return $this->waitlist()->whereIn('status', ['waiting', 'notified']);
    }
    
    public function isFull()
    {
        return $this->current_participants >= $this->max_participants;
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
    
    public function hasAvailableSpots()
    {
        return $this->current_participants < $this->max_participants;
    }
    
    public function availableSpots()
    {
        return max(0, $this->max_participants - $this->current_participants);
    }
    
    public function evaluations()
    {
        return $this->hasMany(ClassEvaluation::class, 'class_id');
    }
}