<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class WorkSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'clock_in',
        'clock_out',
        'notes',
        'hours_worked',
    ];

    protected $casts = [
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'hours_worked' => 'decimal:2',
    ];

    /**
     * Get the user that owns the work session.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate hours worked.
     */
    public function calculateHoursWorked(): ?float
    {
        if (!$this->clock_out) {
            return null;
        }

        $clockIn = Carbon::parse($this->clock_in);
        $clockOut = Carbon::parse($this->clock_out);

        return round($clockOut->diffInMinutes($clockIn) / 60, 2);
    }

    /**
     * Scope for active sessions (clocked in but not out).
     */
    public function scopeActive($query)
    {
        return $query->whereNull('clock_out');
    }

    /**
     * Scope for completed sessions.
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('clock_out');
    }

    /**
     * Scope for sessions on a specific date.
     */
    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('clock_in', $date);
    }

    /**
     * Scope for sessions in a date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('clock_in', [$startDate, $endDate]);
    }

    /**
     * Get formatted duration string.
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->hours_worked) {
            if (!$this->clock_out) {
                // Currently working
                $minutes = Carbon::parse($this->clock_in)->diffInMinutes(now());
                $hours = floor($minutes / 60);
                $mins = $minutes % 60;
                return sprintf('%dh %dm', $hours, $mins);
            }
            return '-';
        }

        $hours = floor($this->hours_worked);
        $minutes = round(($this->hours_worked - $hours) * 60);
        return sprintf('%dh %dm', $hours, $minutes);
    }
}
