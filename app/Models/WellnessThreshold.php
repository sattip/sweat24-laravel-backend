<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class WellnessThreshold extends Model
{
    use HasFactory;

    protected $fillable = [
        'metric',
        'level',
        'min_value',
        'max_value',
        'label_el',
        'label_en',
        'tooltip_el',
        'tooltip_en',
        'is_active',
    ];

    protected $casts = [
        'min_value' => 'decimal:2',
        'max_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Metric constants
    const METRIC_SLEEP = 'sleep';
    const METRIC_HYDRATION = 'hydration';
    const METRIC_CALORIES = 'calories';

    // Level constants
    const LEVEL_GREEN = 'green';
    const LEVEL_ORANGE = 'orange';
    const LEVEL_RED = 'red';

    /**
     * Get all thresholds grouped by metric.
     */
    public static function getThresholdsGrouped(): array
    {
        return Cache::remember('wellness_thresholds', 3600, function () {
            $thresholds = static::where('is_active', true)->get();

            $grouped = [];
            foreach ($thresholds as $threshold) {
                $grouped[$threshold->metric][$threshold->level] = [
                    'min_value' => $threshold->min_value,
                    'max_value' => $threshold->max_value,
                    'label_el' => $threshold->label_el,
                    'label_en' => $threshold->label_en,
                    'tooltip_el' => $threshold->tooltip_el,
                    'tooltip_en' => $threshold->tooltip_en,
                ];
            }

            return $grouped;
        });
    }

    /**
     * Clear the thresholds cache.
     */
    public static function clearCache(): void
    {
        Cache::forget('wellness_thresholds');
    }

    /**
     * Boot method to clear cache on changes.
     */
    protected static function booted(): void
    {
        static::saved(function () {
            static::clearCache();
        });

        static::deleted(function () {
            static::clearCache();
        });
    }

    /**
     * Get all metrics with their thresholds for admin display.
     */
    public static function getAllForAdmin(): array
    {
        $thresholds = static::orderBy('metric')->orderByRaw("
            CASE level
                WHEN 'green' THEN 1
                WHEN 'orange' THEN 2
                WHEN 'red' THEN 3
            END
        ")->get();

        $grouped = [];
        foreach ($thresholds as $threshold) {
            if (!isset($grouped[$threshold->metric])) {
                $grouped[$threshold->metric] = [
                    'metric' => $threshold->metric,
                    'label' => static::getMetricLabel($threshold->metric),
                    'unit' => static::getMetricUnit($threshold->metric),
                    'levels' => [],
                ];
            }
            $grouped[$threshold->metric]['levels'][$threshold->level] = $threshold;
        }

        return array_values($grouped);
    }

    /**
     * Get metric label.
     */
    public static function getMetricLabel(string $metric): string
    {
        return match ($metric) {
            self::METRIC_SLEEP => 'Ύπνος',
            self::METRIC_HYDRATION => 'Ενυδάτωση',
            self::METRIC_CALORIES => 'Θερμίδες (% TDEE)',
            default => $metric,
        };
    }

    /**
     * Get metric unit.
     */
    public static function getMetricUnit(string $metric): string
    {
        return match ($metric) {
            self::METRIC_SLEEP => 'ώρες',
            self::METRIC_HYDRATION => 'λίτρα',
            self::METRIC_CALORIES => '%',
            default => '',
        };
    }

    /**
     * Get level label.
     */
    public static function getLevelLabel(string $level): string
    {
        return match ($level) {
            self::LEVEL_GREEN => 'Φυσιολογικό',
            self::LEVEL_ORANGE => 'Προειδοποίηση',
            self::LEVEL_RED => 'Κρίσιμο',
            default => $level,
        };
    }

    /**
     * Get level color.
     */
    public static function getLevelColor(string $level): string
    {
        return match ($level) {
            self::LEVEL_GREEN => '#22c55e',
            self::LEVEL_ORANGE => '#f97316',
            self::LEVEL_RED => '#ef4444',
            default => '#6b7280',
        };
    }
}
