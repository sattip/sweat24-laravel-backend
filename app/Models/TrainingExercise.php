<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingExercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_session_id',
        'exercise_id',
        'exercise_name',
        'sets',
        'reps',
        'weight_kg',
        'rest_seconds',
        'tempo',
        'rir',
        'exercise_type',
        'superset_with',
        'notes',
        'order',
    ];

    protected $casts = [
        'sets' => 'integer',
        'reps' => 'integer',
        'weight_kg' => 'decimal:2',
        'rest_seconds' => 'integer',
        'rir' => 'integer',
        'order' => 'integer',
    ];

    /**
     * Get the training session that this exercise belongs to.
     */
    public function trainingSession(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class);
    }

    /**
     * Get the exercise definition.
     */
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    /**
     * Calculate volume for this exercise.
     */
    public function getVolumeAttribute(): float
    {
        return ($this->sets ?? 0) * ($this->reps ?? 0) * ($this->weight_kg ?? 0);
    }

    /**
     * Get exercise type label.
     */
    public function getExerciseTypeLabelAttribute(): string
    {
        $labels = [
            'standard' => 'Κανονικό',
            'superset' => 'Superset',
            'drop' => 'Drop Set',
            'pyramid' => 'Πυραμίδα',
            'emom' => 'EMOM',
            'amrap' => 'AMRAP',
            'failure' => 'To Failure',
            'timed' => 'Χρονομετρημένο',
        ];

        return $labels[$this->exercise_type] ?? $this->exercise_type;
    }

    /**
     * Get exercise type icon/emoji.
     */
    public function getExerciseTypeIconAttribute(): string
    {
        $icons = [
            'standard' => '',
            'superset' => '🔁',
            'drop' => '⤵',
            'pyramid' => '△',
            'emom' => '🔄',
            'amrap' => '🎯',
            'failure' => '💥',
            'timed' => '⏱',
        ];

        return $icons[$this->exercise_type] ?? '';
    }

    /**
     * Get the display name for this exercise.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->exercise) {
            return $this->exercise->name_gr ?? $this->exercise->name_en;
        }

        return $this->exercise_name ?? 'Unknown Exercise';
    }
}
