<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulkOperation extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'performed_by',
        'target_count',
        'successful_count',
        'failed_count',
        'status',
        'filters',
        'operation_data',
        'errors',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'operation_data' => 'array',
            'errors' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_COMPLETED_WITH_ERRORS = 'completed_with_errors';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_FAILED = 'failed';

    // Type constants
    const TYPE_PACKAGE_EXTENSION = 'package_extension';
    const TYPE_PRICING_ADJUSTMENT = 'pricing_adjustment';

    /**
     * Get the user who performed the operation
     */
    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Get the progress percentage
     */
    public function getProgressPercentageAttribute()
    {
        if ($this->status === self::STATUS_COMPLETED || $this->status === self::STATUS_COMPLETED_WITH_ERRORS) {
            return 100;
        }

        if ($this->target_count === 0) {
            return 0;
        }

        $processed = $this->successful_count + $this->failed_count;
        return round(($processed / $this->target_count) * 100, 2);
    }

    /**
     * Get the duration of the operation
     */
    public function getDurationAttribute()
    {
        if (!$this->started_at) {
            return null;
        }

        $endTime = $this->completed_at ?? now();
        return $this->started_at->diffForHumans($endTime, true);
    }

    /**
     * Check if the operation is still running
     */
    public function isRunning()
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_IN_PROGRESS]);
    }

    /**
     * Check if the operation completed successfully
     */
    public function isSuccessful()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if the operation had errors
     */
    public function hasErrors()
    {
        return $this->status === self::STATUS_COMPLETED_WITH_ERRORS || $this->failed_count > 0;
    }

    /**
     * Get the operation type label
     */
    public function getTypeLabel()
    {
        return match ($this->type) {
            self::TYPE_PACKAGE_EXTENSION => 'Package Extension',
            self::TYPE_PRICING_ADJUSTMENT => 'Pricing Adjustment',
            default => ucwords(str_replace('_', ' ', $this->type)),
        };
    }

    /**
     * Get the status label
     */
    public function getStatusLabel()
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_COMPLETED_WITH_ERRORS => 'Completed with Errors',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_FAILED => 'Failed',
            default => ucwords(str_replace('_', ' ', $this->status)),
        };
    }

    /**
     * Get the status color class for UI
     */
    public function getStatusColorClass()
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'text-yellow-600',
            self::STATUS_IN_PROGRESS => 'text-blue-600',
            self::STATUS_COMPLETED => 'text-green-600',
            self::STATUS_COMPLETED_WITH_ERRORS => 'text-orange-600',
            self::STATUS_CANCELLED => 'text-gray-600',
            self::STATUS_FAILED => 'text-red-600',
            default => 'text-gray-600',
        };
    }

    /**
     * Get the success rate percentage
     */
    public function getSuccessRateAttribute()
    {
        if ($this->target_count === 0) {
            return 0;
        }

        return round(($this->successful_count / $this->target_count) * 100, 2);
    }

    /**
     * Get formatted filters for display
     */
    public function getFormattedFilters()
    {
        $filters = $this->filters ?? [];
        $formatted = [];

        foreach ($filters as $key => $value) {
            if (empty($value)) {
                continue;
            }

            $label = ucwords(str_replace('_', ' ', $key));
            
            if (is_array($value)) {
                $formatted[$label] = implode(', ', $value);
            } elseif (is_bool($value)) {
                $formatted[$label] = $value ? 'Yes' : 'No';
            } else {
                $formatted[$label] = $value;
            }
        }

        return $formatted;
    }

    /**
     * Get formatted operation data for display
     */
    public function getFormattedOperationData()
    {
        $data = $this->operation_data ?? [];
        $formatted = [];

        foreach ($data as $key => $value) {
            if (empty($value)) {
                continue;
            }

            $label = ucwords(str_replace('_', ' ', $key));
            
            if (is_array($value)) {
                $formatted[$label] = implode(', ', $value);
            } elseif (is_bool($value)) {
                $formatted[$label] = $value ? 'Yes' : 'No';
            } else {
                $formatted[$label] = $value;
            }
        }

        return $formatted;
    }

    /**
     * Scope to filter by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by status
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeInDateRange($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Scope to get recent operations
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}