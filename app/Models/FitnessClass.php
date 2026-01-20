<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class FitnessClass extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'instructor',
        'date',
        'time',
        'duration',
        'max_participants',
        'current_participants',
        'priority_seats',
        'store_id',
        'location',
        'description',
        'status',
        'is_recurring',
        'recurrence_pattern',
        'recurrence_interval',
        'recurrence_end_date'
    ];

    protected $casts = [
        'date' => 'date',
        'duration' => 'integer',
        'max_participants' => 'integer',
        'current_participants' => 'integer',
        'priority_seats' => 'integer',
        'is_recurring' => 'boolean'
    ];

    /**
     * Relationship with Store
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Bookings relationship
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'class_id');
    }

    /**
     * Scope for active classes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for future classes
     */
    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', now()->toDateString())
                    ->orderBy('date', 'asc')
                    ->orderBy('time', 'asc');
    }

    /**
     * Scope for classes by date
     */
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    /**
     * Scope for classes by instructor
     */
    public function scopeByInstructor($query, $instructor)
    {
        return $query->where('instructor', $instructor);
    }

    /**
     * Get formatted time range
     */
    public function getTimeRangeAttribute()
    {
        $startTime = Carbon::createFromFormat('H:i', $this->time);
        $endTime = $startTime->addMinutes($this->duration);
        return $this->time . ' - ' . $endTime->format('H:i');
    }

    /**
     * Get current participants count dynamically from bookings
     */
    public function getCurrentParticipantsAttribute($value)
    {
        // If accessing via relationship (already loaded), count the loaded bookings
        if ($this->relationLoaded('bookings')) {
            return $this->bookings->where('status', '!=', 'cancelled')->count();
        }

        // Otherwise, use the database value or query the count
        return $value ?? $this->bookings()->where('status', '!=', 'cancelled')->count();
    }

    /**
     * Get available spots
     */
    public function getAvailableSpotsAttribute()
    {
        return $this->max_participants - $this->current_participants;
    }

    /**
     * Check if class is full
     */
    public function getIsFullAttribute()
    {
        return $this->current_participants >= $this->max_participants;
    }

    /**
     * Check if class is in the past
     */
    public function getIsPastAttribute()
    {
        $classDateTime = Carbon::parse($this->date->format('Y-m-d') . ' ' . $this->time);
        return $classDateTime->isPast();
    }

    /**
     * Get display location (store name or manual location)
     */
    public function getDisplayLocationAttribute()
    {
        return $this->store ? $this->store->name : ($this->location ?: 'Δεν έχει οριστεί');
    }
}
