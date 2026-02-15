<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutMuscleGroup extends Model
{
    use HasFactory;
    protected $fillable = [
        'booking_id',
        'user_id',
        'muscle_groups',
    ];

    protected $casts = [
        'muscle_groups' => 'array',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public const VALID_MUSCLE_GROUPS = [
        'total_body',
        'legs',
        'chest',
        'back',
        'shoulders',
        'arms',
        'core',
        'cardio',
    ];
}