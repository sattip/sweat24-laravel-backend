<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriorityBookingSettings extends Model
{
    protected $fillable = [
        'default_priority_seats',
        'priority_advance_hours',
        'priority_release_hours',
        'auto_release_enabled',
        'priority_system_enabled',
        'priority_packages',
    ];

    protected $casts = [
        'default_priority_seats' => 'integer',
        'priority_advance_hours' => 'integer',
        'priority_release_hours' => 'integer',
        'auto_release_enabled' => 'boolean',
        'priority_system_enabled' => 'boolean',
        'priority_packages' => 'array',
    ];

    /**
     * Get the singleton settings instance
     */
    public static function getSettings()
    {
        return self::firstOrCreate(
            ['id' => 1],
            [
                'default_priority_seats' => 5,
                'priority_advance_hours' => 48,
                'priority_release_hours' => 24,
                'auto_release_enabled' => true,
                'priority_system_enabled' => true,
            ]
        );
    }
}