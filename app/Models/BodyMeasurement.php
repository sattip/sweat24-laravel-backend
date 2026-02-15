<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BodyMeasurement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'weight',
        'height',
        'waist',
        'hips',
        'chest',
        'arm',
        'thigh',
        'body_fat',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'weight' => 'decimal:2',
        'height' => 'decimal:2',
        'waist' => 'decimal:2',
        'hips' => 'decimal:2',
        'chest' => 'decimal:2',
        'arm' => 'decimal:2',
        'thigh' => 'decimal:2',
        'body_fat' => 'decimal:2',
    ];

    /**
     * Get the user that owns the body measurement
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user (trainer) who took this measurement.
     */
    public function measurer()
    {
        return $this->belongsTo(User::class, 'measured_by');
    }

    /**
     * Calculate BMI if weight and height are available
     */
    public function getBmiAttribute()
    {
        if ($this->weight && $this->height) {
            $heightInMeters = $this->height / 100;
            $bmi = $this->weight / ($heightInMeters * $heightInMeters);
            return number_format($bmi, 1);
        }
        return null;
    }

    /**
     * Get formatted date
     */
    public function getFormattedDateAttribute()
    {
        return $this->date ? $this->date->format('Y-m-d') : null;
    }

    /**
     * Scope for latest measurements
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('date', 'desc');
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope for specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Format for API response
     */
    public function toApiArray()
    {
        return [
            'id' => $this->id,
            'date' => $this->formatted_date,
            'weight' => $this->weight ? (string)$this->weight : null,
            'height' => $this->height ? (string)$this->height : null,
            'waist' => $this->waist ? (string)$this->waist : null,
            'hips' => $this->hips ? (string)$this->hips : null,
            'chest' => $this->chest ? (string)$this->chest : null,
            'arm' => $this->arm ? (string)$this->arm : null,
            'thigh' => $this->thigh ? (string)$this->thigh : null,
            'bodyFat' => $this->body_fat ? (string)$this->body_fat : null,
            'notes' => $this->notes,
            'bmi' => $this->bmi,
        ];
    }

    /**
     * Get previous measurement for comparison
     */
    public function getPreviousMeasurement()
    {
        return self::where('user_id', $this->user_id)
            ->where('date', '<', $this->date)
            ->orderBy('date', 'desc')
            ->first();
    }

    /**
     * Calculate change from previous measurement.
     */
    public function calculateChanges()
    {
        $previous = $this->getPreviousMeasurement();

        if (!$previous) {
            return null;
        }

        $changes = [];
        $fields = ['weight', 'body_fat', 'chest', 'waist', 'hips', 'thigh', 'arm'];

        foreach ($fields as $field) {
            if ($this->$field && $previous->$field) {
                $change = $this->$field - $previous->$field;
                $changePercentage = ($change / $previous->$field) * 100;

                $changes[$field] = [
                    'previous' => $previous->$field,
                    'current' => $this->$field,
                    'change' => round($change, 1),
                    'change_percentage' => round($changePercentage, 1),
                ];
            }
        }

        return $changes;
    }

    /**
     * Get the latest measurement for a user.
     */
    public static function getLatestForUser($userId)
    {
        return self::where('user_id', $userId)
            ->orderBy('date', 'desc')
            ->first();
    }
}