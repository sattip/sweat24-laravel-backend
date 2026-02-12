<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriorityBookingSetting extends Model
{
    use HasFactory;
    protected $fillable = [
        'default_priority_seats',
        'priority_advance_hours',
        'priority_release_hours',
        'priority_system_enabled',
        'auto_release_enabled',
        'priority_packages',
    ];

    protected $casts = [
        'priority_system_enabled' => 'boolean',
        'auto_release_enabled' => 'boolean',
        'priority_packages' => 'array',
    ];

    /**
     * Get the singleton settings instance
     */
    public static function getSettings()
    {
        return self::first() ?? self::create([
            'default_priority_seats' => 5,
            'priority_advance_hours' => 48,
            'priority_release_hours' => 24,
            'priority_system_enabled' => true,
            'auto_release_enabled' => true,
        ]);
    }
}
