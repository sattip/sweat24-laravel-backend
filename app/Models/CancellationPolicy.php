<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CancellationPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'hours_before',
        'penalty_percentage',
        'allow_reschedule',
        'reschedule_hours_before',
        'max_reschedules_per_month',
        'is_active',
        'priority',
        'applicable_to',
    ];

    protected $casts = [
        'allow_reschedule' => 'boolean',
        'is_active' => 'boolean',
        'applicable_to' => 'array',
        'penalty_percentage' => 'decimal:2',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('priority', 'desc');
    }

    public function appliesToClassType($classType)
    {
        if (!$this->applicable_to || empty($this->applicable_to)) {
            return true; // Applies to all if not specified
        }
        
        return in_array($classType, $this->applicable_to['class_types'] ?? []);
    }

    public function appliesToPackage($packageId)
    {
        if (!$this->applicable_to || empty($this->applicable_to)) {
            return true; // Applies to all if not specified
        }
        
        return in_array($packageId, $this->applicable_to['package_ids'] ?? []);
    }

    public function canCancelWithoutPenalty($hoursUntilClass)
    {
        return $hoursUntilClass >= $this->hours_before;
    }

    public function canReschedule($hoursUntilClass)
    {
        if (!$this->allow_reschedule) {
            return false;
        }
        
        $requiredHours = $this->reschedule_hours_before ?? $this->hours_before;
        return $hoursUntilClass >= $requiredHours;
    }

    public function calculatePenalty($amount)
    {
        return $amount * ($this->penalty_percentage / 100);
    }
}