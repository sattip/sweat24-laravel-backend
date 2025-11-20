<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPushToken extends Model
{
    protected $fillable = [
        'user_id',
        'token', 
        'platform',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Save or update push token for user
     */
    public static function saveToken(int $userId, string $token, string $platform): self
    {
        return static::updateOrCreate(
            [
                'user_id' => $userId,
                'platform' => $platform
            ],
            [
                'token' => $token,
                'is_active' => true
            ]
        );
    }

    /**
     * Get active tokens for user
     */
    public static function getActiveTokensForUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('user_id', $userId)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Deactivate token
     */
    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }
}

