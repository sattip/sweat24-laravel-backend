<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WellnessScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'sleep_hours',
        'sleep_quality',
        'hydration_liters',
        'calories_consumed',
        'tdee',
        'calories_percentage',
        'energy_level',
        'mood_level',
        'stress_level',
        'soreness_level',
        'hrv',
        'wellness_score',
        'sleep_alert',
        'hydration_alert',
        'calories_alert',
        'overall_alert',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'sleep_hours' => 'decimal:2',
        'hydration_liters' => 'decimal:2',
        'calories_consumed' => 'integer',
        'tdee' => 'integer',
        'calories_percentage' => 'decimal:2',
        'energy_level' => 'integer',
        'mood_level' => 'integer',
        'stress_level' => 'integer',
        'soreness_level' => 'integer',
        'hrv' => 'integer',
        'wellness_score' => 'integer',
    ];

    // Alert level constants
    const ALERT_GREEN = 'green';
    const ALERT_ORANGE = 'orange';
    const ALERT_RED = 'red';

    /**
     * Get the user that owns this wellness score.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate and set alert levels based on thresholds.
     */
    public function calculateAlerts(): void
    {
        $thresholds = WellnessThreshold::getThresholdsGrouped();

        // Sleep alert
        if ($this->sleep_hours !== null) {
            $this->sleep_alert = $this->determineAlertLevel(
                $this->sleep_hours,
                $thresholds['sleep'] ?? []
            );
        }

        // Hydration alert
        if ($this->hydration_liters !== null) {
            $this->hydration_alert = $this->determineAlertLevel(
                $this->hydration_liters,
                $thresholds['hydration'] ?? []
            );
        }

        // Calories alert (based on percentage of TDEE)
        if ($this->calories_percentage !== null) {
            $this->calories_alert = $this->determineAlertLevel(
                $this->calories_percentage,
                $thresholds['calories'] ?? []
            );
        }

        // Overall alert - if 2+ orange/red, show red
        $this->overall_alert = $this->calculateOverallAlert();
    }

    /**
     * Determine alert level for a metric value.
     */
    private function determineAlertLevel(float $value, array $thresholds): string
    {
        // Check red first (worst case)
        if (isset($thresholds['red'])) {
            $red = $thresholds['red'];
            if ($red['max_value'] !== null && $value <= $red['max_value']) {
                return self::ALERT_RED;
            }
        }

        // Check orange
        if (isset($thresholds['orange'])) {
            $orange = $thresholds['orange'];
            $inRange = true;
            if ($orange['min_value'] !== null && $value < $orange['min_value']) {
                $inRange = false;
            }
            if ($orange['max_value'] !== null && $value > $orange['max_value']) {
                $inRange = false;
            }
            if ($inRange && ($orange['min_value'] !== null || $orange['max_value'] !== null)) {
                return self::ALERT_ORANGE;
            }
        }

        // Check green
        if (isset($thresholds['green'])) {
            $green = $thresholds['green'];
            if ($green['min_value'] !== null && $value >= $green['min_value']) {
                return self::ALERT_GREEN;
            }
        }

        return self::ALERT_GREEN;
    }

    /**
     * Calculate overall alert based on individual alerts.
     */
    private function calculateOverallAlert(): string
    {
        $alerts = [
            $this->sleep_alert,
            $this->hydration_alert,
            $this->calories_alert,
        ];

        $redCount = 0;
        $orangeCount = 0;

        foreach ($alerts as $alert) {
            if ($alert === self::ALERT_RED) {
                $redCount++;
            } elseif ($alert === self::ALERT_ORANGE) {
                $orangeCount++;
            }
        }

        // If any red or 2+ orange, return red
        if ($redCount > 0 || ($redCount + $orangeCount) >= 2) {
            return self::ALERT_RED;
        }

        // If any orange, return orange
        if ($orangeCount > 0) {
            return self::ALERT_ORANGE;
        }

        return self::ALERT_GREEN;
    }

    /**
     * Calculate wellness score (0-100).
     */
    public function calculateWellnessScore(): int
    {
        $scores = [];
        $weights = [
            'sleep' => 25,
            'hydration' => 20,
            'calories' => 20,
            'energy' => 15,
            'mood' => 10,
            'stress' => 5,
            'soreness' => 5,
        ];

        // Sleep score (0-100 based on 8 hours being optimal)
        if ($this->sleep_hours !== null) {
            $sleepScore = min(100, ($this->sleep_hours / 8) * 100);
            $scores['sleep'] = $sleepScore;
        }

        // Hydration score (0-100 based on 2.5L being optimal)
        if ($this->hydration_liters !== null) {
            $hydrationScore = min(100, ($this->hydration_liters / 2.5) * 100);
            $scores['hydration'] = $hydrationScore;
        }

        // Calories score (0-100 based on 100% TDEE being optimal)
        if ($this->calories_percentage !== null) {
            $caloriesScore = min(100, $this->calories_percentage);
            $scores['calories'] = $caloriesScore;
        }

        // Energy level (already 1-10, convert to 0-100)
        if ($this->energy_level !== null) {
            $scores['energy'] = $this->energy_level * 10;
        }

        // Mood level
        if ($this->mood_level !== null) {
            $scores['mood'] = $this->mood_level * 10;
        }

        // Stress level (inverted - low stress = high score)
        if ($this->stress_level !== null) {
            $scores['stress'] = (11 - $this->stress_level) * 10;
        }

        // Soreness level (inverted - low soreness = high score)
        if ($this->soreness_level !== null) {
            $scores['soreness'] = (11 - $this->soreness_level) * 10;
        }

        // Calculate weighted average
        $totalWeight = 0;
        $weightedSum = 0;

        foreach ($scores as $key => $score) {
            if (isset($weights[$key])) {
                $weightedSum += $score * $weights[$key];
                $totalWeight += $weights[$key];
            }
        }

        if ($totalWeight === 0) {
            return 0;
        }

        $this->wellness_score = (int) round($weightedSum / $totalWeight);
        return $this->wellness_score;
    }

    /**
     * Get alert emoji for display.
     */
    public function getAlertEmoji(): string
    {
        return match ($this->overall_alert) {
            self::ALERT_RED => '❗',
            self::ALERT_ORANGE => '⚠️',
            default => '✅',
        };
    }

    /**
     * Get tooltip message based on alerts.
     */
    public function getTooltipMessage(): string
    {
        $messages = [];

        if ($this->sleep_alert === self::ALERT_RED) {
            $messages[] = 'Ύπνος κάτω από 5h';
        } elseif ($this->sleep_alert === self::ALERT_ORANGE) {
            $messages[] = 'Μέτριος ύπνος';
        }

        if ($this->hydration_alert === self::ALERT_RED) {
            $messages[] = 'σοβαρή αφυδάτωση';
        } elseif ($this->hydration_alert === self::ALERT_ORANGE) {
            $messages[] = 'χαμηλή ενυδάτωση';
        }

        if ($this->calories_alert === self::ALERT_RED) {
            $messages[] = 'πολύ χαμηλές θερμίδες';
        } elseif ($this->calories_alert === self::ALERT_ORANGE) {
            $messages[] = 'χαμηλές θερμίδες';
        }

        if (empty($messages)) {
            return 'Όλοι οι δείκτες φυσιολογικοί';
        }

        $message = ucfirst(implode(', ', $messages)) . '.';

        if ($this->overall_alert === self::ALERT_RED) {
            $message .= ' Πρότεινε ελαφρύτερη συνεδρία.';
        }

        return $message;
    }

    /**
     * Check if user already submitted today.
     */
    public static function hasSubmittedToday(int $userId): bool
    {
        return static::where('user_id', $userId)
            ->where('date', today())
            ->exists();
    }

    /**
     * Get today's score for a user.
     */
    public static function getTodayScore(int $userId): ?self
    {
        return static::where('user_id', $userId)
            ->where('date', today())
            ->first();
    }

    /**
     * Scope for today's entries.
     */
    public function scopeToday($query)
    {
        return $query->where('date', today());
    }

    /**
     * Scope for users with alerts.
     */
    public function scopeWithAlerts($query)
    {
        return $query->whereIn('overall_alert', [self::ALERT_ORANGE, self::ALERT_RED]);
    }

    /**
     * Scope for date range.
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('date', [$from, $to]);
    }
}
