<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriorityBookingSetting extends Model
{
    protected $fillable = [
        'priority_booking_window_days',
        'regular_booking_window_days',
        'priority_seats_release_hours',
        'default_priority_seats',
        'priority_system_enabled',
        'auto_release_enabled',
    ];

    protected $casts = [
        'priority_system_enabled' => 'boolean',
        'auto_release_enabled' => 'boolean',
    ];

    /**
     * Get the singleton settings instance
     */
    public static function getSettings()
    {
        return self::first() ?? self::create([
            'priority_booking_window_days' => 30,
            'regular_booking_window_days' => 14,
            'priority_seats_release_hours' => 48,
            'default_priority_seats' => 5,
            'priority_system_enabled' => true,
            'auto_release_enabled' => true,
        ]);
    }
}
