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
        'cancellation_policy_id',
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
    
    public function cancellationPolicy()
    {
        return $this->belongsTo(CancellationPolicy::class);
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
    
    /**
     * Get applicable cancellation policy for this class
     */
    public function getApplicablePolicy()
    {
        // If class has specific policy assigned, use it
        if ($this->cancellation_policy_id && $this->cancellationPolicy) {
            return $this->cancellationPolicy;
        }
        
        // Otherwise, find the best matching policy based on class type
        $policies = CancellationPolicy::where('is_active', true)
            ->orderBy('priority', 'asc')
            ->get();
            
        foreach ($policies as $policy) {
            if ($policy->appliesToClassType($this->type)) {
                return $policy;
            }
        }
        
        // Return default policy if no specific match
        return CancellationPolicy::where('is_active', true)
            ->orderBy('priority', 'asc')
            ->first();
    }
}