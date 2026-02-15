<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserPoints extends Model
{
    use HasFactory;

    protected $table = 'users_points';

    protected $fillable = [
        'user_id',
        'points_balance',
        'total_earned',
        'total_spent',
        'lifetime_rank'
    ];

    protected $casts = [
        'points_balance' => 'integer',
        'total_earned' => 'integer',
        'total_spent' => 'integer',
        'lifetime_rank' => 'integer'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PointsTransaction::class, 'user_id', 'user_id');
    }

    /**
     * Get user's rank based on total earned points
     */
    public function calculateRank(): int
    {
        return static::where('total_earned', '>', $this->total_earned)->count() + 1;
    }

    /**
     * Check if user can afford a certain amount of points
     */
    public function canAfford(int $points): bool
    {
        return $this->points_balance >= $points;
    }
}
