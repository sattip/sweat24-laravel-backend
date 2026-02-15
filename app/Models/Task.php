<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'priority', 'deadline', 'created_by', 
        'assigned_to', 'status', 'creation_date', 'completion_date'
    ];

    protected $casts = [
        // Remove datetime casting for deadline to prevent timezone conversion
        // 'deadline' => 'datetime',
        // Remove date casting to prevent timezone conversion
        // 'creation_date' => 'date',
        // 'completion_date' => 'date'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($task) {
            // Set creation_date to today's date as string (no timezone conversion)
            if (!$task->creation_date) {
                $task->creation_date = now()->format('Y-m-d');
            }
            
            // Set created_by if authenticated user exists
            if (auth()->check()) {
                $task->created_by = auth()->id();
            }
        });

        static::updating(function ($task) {
            // Automatically set completion_date when status changes to 'completed'
            if ($task->isDirty('status') && $task->status === 'completed') {
                $task->completion_date = now()->format('Y-m-d');
            } elseif ($task->isDirty('status') && $task->status !== 'completed') {
                $task->completion_date = null;
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function isOverdue(): bool
    {
        return $this->deadline && 
               $this->status !== 'completed' && 
               $this->deadline->isPast();
    }

    public function isDueToday(): bool
    {
        return $this->deadline && $this->deadline->isToday();
    }

    public function isDueWithin(int $days): bool
    {
        return $this->deadline && 
               $this->status !== 'completed' && 
               $this->deadline->diffInDays(Carbon::now(), false) <= $days &&
               !$this->deadline->isPast();
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeCreatedBy($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    public function scopeOverdue($query)
    {
        return $query->where('deadline', '<', Carbon::today())
                    ->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function scopeDueWithin($query, $days)
    {
        return $query->whereBetween('deadline', [
                        Carbon::today(),
                        Carbon::today()->addDays($days)
                    ])
                    ->whereNotIn('status', ['completed', 'cancelled']);
    }
}
