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
        'service_id',
        'priority_seats',
        'priority_seats_booked',
        'priority_seats_release_at',
        'priority_booking_enabled',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'priority_seats' => 'integer',
            'priority_seats_booked' => 'integer',
            'priority_seats_release_at' => 'datetime',
            'priority_booking_enabled' => 'boolean',
        ];
    }

    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
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

    /**
     * Check if priority seats are available
     */
    public function hasPrioritySeatsAvailable(): bool
    {
        if (!$this->priority_booking_enabled) {
            return false;
        }

        return $this->priority_seats_booked < $this->priority_seats;
    }

    /**
     * Get available priority seats count
     */
    public function availablePrioritySeats(): int
    {
        return max(0, $this->priority_seats - $this->priority_seats_booked);
    }

    /**
     * Check if regular seats are available
     */
    public function hasRegularSeatsAvailable(): bool
    {
        $regularSeatsTotal = $this->max_participants - $this->priority_seats;
        $regularSeatsBooked = $this->current_participants - $this->priority_seats_booked;
        
        return $regularSeatsBooked < $regularSeatsTotal;
    }

    /**
     * Get available regular seats count
     */
    public function availableRegularSeats(): int
    {
        $regularSeatsTotal = $this->max_participants - $this->priority_seats;
        $regularSeatsBooked = $this->current_participants - $this->priority_seats_booked;
        
        return max(0, $regularSeatsTotal - $regularSeatsBooked);
    }

    /**
     * Check if priority seats should be released
     */
    public function shouldReleasePrioritySeats(): bool
    {
        if (!$this->priority_booking_enabled) {
            return false;
        }

        if (!$this->priority_seats_release_at) {
            // Calculate release time (24 hours before class)
            $classDateTime = \Carbon\Carbon::parse($this->date . ' ' . $this->time);
            $releaseTime = $classDateTime->subHours(24);
            return now()->gte($releaseTime);
        }

        return now()->gte($this->priority_seats_release_at);
    }

    /**
     * Release unused priority seats to regular pool
     */
    public function releasePrioritySeats(): void
    {
        if (!$this->shouldReleasePrioritySeats()) {
            return;
        }

        // Move unused priority seats to regular pool
        $unusedPrioritySeats = $this->priority_seats - $this->priority_seats_booked;
        
        if ($unusedPrioritySeats > 0) {
            // Update the class to reflect released seats
            $this->update([
                'priority_seats' => $this->priority_seats_booked, // Keep only booked priority seats
                'priority_seats_release_at' => now(),
            ]);
        }
    }

    /**
     * Book a priority seat
     */
    public function bookPrioritySeat(): bool
    {
        if (!$this->hasPrioritySeatsAvailable()) {
            return false;
        }

        $this->increment('priority_seats_booked');
        $this->increment('current_participants');
        
        return true;
    }

    /**
     * Cancel a priority booking
     */
    public function cancelPriorityBooking(): void
    {
        $this->decrement('priority_seats_booked');
        $this->decrement('current_participants');
    }

    /**
     * Book a regular seat
     */
    public function bookRegularSeat(): bool
    {
        if (!$this->hasRegularSeatsAvailable() && !$this->shouldReleasePrioritySeats()) {
            return false;
        }

        // Release priority seats if needed
        if ($this->shouldReleasePrioritySeats()) {
            $this->releasePrioritySeats();
        }

        $this->increment('current_participants');
        
        return true;
    }

    /**
     * Get booking availability info
     */
    public function getAvailabilityInfo(): array
    {
        return [
            'total_capacity' => $this->max_participants,
            'current_participants' => $this->current_participants,
            'priority_seats' => $this->priority_seats,
            'priority_seats_booked' => $this->priority_seats_booked,
            'priority_seats_available' => $this->availablePrioritySeats(),
            'regular_seats_available' => $this->availableRegularSeats(),
            'total_available' => $this->availableSpots(),
            'priority_enabled' => $this->priority_booking_enabled,
            'priority_released' => $this->shouldReleasePrioritySeats(),
        ];
    }
}