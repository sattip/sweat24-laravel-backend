<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'trainer_id',
        'booking_id',
        'session_date',
        'duration_minutes',
        'session_type',
        'intensity',
        'muscle_groups',
        'includes_cardio',
        'includes_cognitive',
        'includes_mobility',
        'includes_balance',
        'includes_functional',
        'is_total_body',
        'total_volume',
        'notes',
    ];

    protected $casts = [
        'session_date' => 'date',
        'duration_minutes' => 'integer',
        'intensity' => 'integer',
        'muscle_groups' => 'array',
        'includes_cardio' => 'boolean',
        'includes_cognitive' => 'boolean',
        'includes_mobility' => 'boolean',
        'includes_balance' => 'boolean',
        'includes_functional' => 'boolean',
        'is_total_body' => 'boolean',
        'total_volume' => 'decimal:2',
    ];

    /**
     * Get the user that this session belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the trainer for this session.
     */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    /**
     * Get the booking associated with this session.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the exercises for this session.
     */
    public function exercises(): HasMany
    {
        return $this->hasMany(TrainingExercise::class)->orderBy('order');
    }

    /**
     * Calculate total volume from exercises.
     */
    public function calculateTotalVolume(): void
    {
        $totalVolume = $this->exercises->sum(function ($exercise) {
            return ($exercise->sets ?? 0) * ($exercise->reps ?? 0) * ($exercise->weight_kg ?? 0);
        });

        $this->total_volume = $totalVolume;
        $this->save();
    }

    /**
     * Get intensity label.
     */
    public function getIntensityLabelAttribute(): string
    {
        if ($this->intensity <= 3) {
            return 'Χαμηλή';
        } elseif ($this->intensity <= 6) {
            return 'Μέτρια';
        } else {
            return 'Υψηλή';
        }
    }

    /**
     * Get session type label.
     */
    public function getSessionTypeLabelAttribute(): string
    {
        $labels = [
            'personal' => 'Personal',
            'semi_personal' => 'Semi-Personal',
            'group' => 'Group',
        ];

        return $labels[$this->session_type] ?? $this->session_type;
    }

    /**
     * Scope to filter by user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by trainer.
     */
    public function scopeForTrainer($query, $trainerId)
    {
        return $query->where('trainer_id', $trainerId);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('session_date', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by intensity level.
     */
    public function scopeByIntensity($query, $minIntensity, $maxIntensity)
    {
        return $query->whereBetween('intensity', [$minIntensity, $maxIntensity]);
    }
}
