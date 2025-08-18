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
        return $this->date->format('Y-m-d');
    }

    /**
     * Scope for latest measurements
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('date', 'desc');
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
}