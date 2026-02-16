<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LoyaltyRedemption extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'loyalty_reward_id',
        'points_used',
        'status',
        'redeemed_at',
        'expires_at',
        'used_at',
        'redemption_code',
        'admin_notes',
        'reward_snapshot',
    ];

    protected $casts = [
        'redeemed_at' => 'datetime',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'reward_snapshot' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($redemption) {
            if (empty($redemption->redemption_code)) {
                $redemption->redemption_code = $redemption->generateRedemptionCode();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function loyaltyReward()
    {
        return $this->belongsTo(LoyaltyReward::class);
    }

    /**
     * Γεννά μοναδικό κωδικό εξαργύρωσης
     */
    public function generateRedemptionCode()
    {
        do {
            $code = 'LYL-' . strtoupper(Str::random(8));
        } while (self::where('redemption_code', $code)->exists());

        return $code;
    }

    /**
     * Έλεγχος αν η εξαργύρωση έχει λήξει
     */
    public function isExpired()
    {
        return $this->expires_at && now() > $this->expires_at;
    }

    /**
     * Έλεγχος αν η εξαργύρωση είναι ενεργή
     */
    public function isActive()
    {
        return in_array($this->status, ['pending', 'approved']) && !$this->isExpired();
    }

    /**
     * Scope για ενεργές εξαργυρώσεις
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'approved'])
                    ->where(function($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * Scope για εξαργυρώσεις που λήγουν σύντομα
     */
    public function scopeExpiringSoon($query, $days = 7)
    {
        return $query->whereIn('status', ['pending', 'approved'])
                    ->where('expires_at', '<=', now()->addDays($days))
                    ->where('expires_at', '>', now());
    }
}
