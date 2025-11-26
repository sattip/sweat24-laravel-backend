<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftChecklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'store_id',
        'time_tracking_id',
        'type',
        // Cash register
        'cash_counted',
        'cash_amount',
        // Inventory
        'towels_checked',
        'towels_count',
        'water_checked',
        'water_count',
        // Equipment
        'equipment_checked',
        'equipment_notes',
        // Cleanliness
        'area_tidy',
        'locker_rooms_checked',
        'showers_checked',
        // Security (closing)
        'doors_locked',
        'lights_off',
        'ac_off',
        'alarm_set',
        // Notes
        'notes',
        'issues_reported',
        'completed_at',
    ];

    protected $casts = [
        'cash_amount' => 'decimal:2',
        'towels_count' => 'integer',
        'water_count' => 'integer',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user who completed this checklist
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the store this checklist is for
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the associated time tracking record
     */
    public function timeTracking()
    {
        return $this->belongsTo(TimeTracking::class);
    }

    /**
     * Scope for opening checklists
     */
    public function scopeOpening($query)
    {
        return $query->where('type', 'opening');
    }

    /**
     * Scope for closing checklists
     */
    public function scopeClosing($query)
    {
        return $query->where('type', 'closing');
    }

    /**
     * Check if all required items are completed
     */
    public function isComplete(): bool
    {
        $requiredFields = [
            'cash_counted',
            'towels_checked',
            'water_checked',
            'equipment_checked',
            'area_tidy',
        ];

        // For closing, also check security items
        if ($this->type === 'closing') {
            $requiredFields = array_merge($requiredFields, [
                'doors_locked',
                'lights_off',
                'ac_off',
                'alarm_set',
            ]);
        }

        foreach ($requiredFields as $field) {
            if (empty($this->$field)) {
                return false;
            }
        }

        return true;
    }
}
