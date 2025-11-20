<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'exercise_name',
        'test_date',
        'weight_kg',
        'reps',
        'time_seconds',
        'category',
        'is_pr',
        'improvement_percentage',
        'notes',
        'trainer_id',
    ];

    protected $casts = [
        'test_date' => 'date',
        'weight_kg' => 'decimal:2',
        'reps' => 'integer',
        'time_seconds' => 'integer',
        'is_pr' => 'boolean',
        'improvement_percentage' => 'decimal:2',
    ];

    /**
     * Get the user that this test belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the trainer who recorded this test.
     */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    /**
     * Calculate improvement percentage from the previous test.
     */
    public function calculateImprovement(): void
    {
        $previousTest = self::where('user_id', $this->user_id)
            ->where('exercise_name', $this->exercise_name)
            ->where('test_date', '<', $this->test_date)
            ->orderBy('test_date', 'desc')
            ->first();

        if (!$previousTest) {
            $this->improvement_percentage = null;
            return;
        }

        // Calculate based on category
        if ($this->category === 'endurance') {
            // For endurance, lower time is better
            if ($previousTest->time_seconds && $this->time_seconds) {
                $improvement = (($previousTest->time_seconds - $this->time_seconds) / $previousTest->time_seconds) * 100;
                $this->improvement_percentage = round($improvement, 2);
            }
        } else {
            // For strength/core, calculate based on volume (weight × reps)
            $previousVolume = ($previousTest->weight_kg ?? 0) * ($previousTest->reps ?? 1);
            $currentVolume = ($this->weight_kg ?? 0) * ($this->reps ?? 1);

            if ($previousVolume > 0) {
                $improvement = (($currentVolume - $previousVolume) / $previousVolume) * 100;
                $this->improvement_percentage = round($improvement, 2);
            }
        }
    }

    /**
     * Check if this test is a personal record.
     */
    public function checkIfPR(): void
    {
        if ($this->category === 'endurance') {
            // For endurance, check if this is the lowest time
            $bestTest = self::where('user_id', $this->user_id)
                ->where('exercise_name', $this->exercise_name)
                ->where('id', '!=', $this->id)
                ->orderBy('time_seconds', 'asc')
                ->first();

            $this->is_pr = !$bestTest || ($this->time_seconds < $bestTest->time_seconds);
        } else {
            // For strength/core, check if this is the highest volume
            $bestTest = self::where('user_id', $this->user_id)
                ->where('exercise_name', $this->exercise_name)
                ->where('id', '!=', $this->id)
                ->get()
                ->sortByDesc(function ($test) {
                    return ($test->weight_kg ?? 0) * ($test->reps ?? 1);
                })
                ->first();

            $currentVolume = ($this->weight_kg ?? 0) * ($this->reps ?? 1);
            $bestVolume = $bestTest ? (($bestTest->weight_kg ?? 0) * ($bestTest->reps ?? 1)) : 0;

            $this->is_pr = $currentVolume > $bestVolume;
        }

        // Update all other tests for this exercise to not be PR
        if ($this->is_pr) {
            self::where('user_id', $this->user_id)
                ->where('exercise_name', $this->exercise_name)
                ->where('id', '!=', $this->id)
                ->update(['is_pr' => false]);
        }
    }
}
