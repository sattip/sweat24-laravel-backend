<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'code',
        'link',
        'total_referrals',
        'points',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    protected $appends = ['points_earned', 'referred_users_count'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function referrals()
    {
        return $this->hasMany(Referral::class);
    }
    
    public function getPointsEarnedAttribute()
    {
        return $this->points ?? ($this->referrals()->count() * 10);
    }
    
    public function getReferredUsersCountAttribute()
    {
        return $this->referrals()->count();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($referralCode) {
            if (!$referralCode->code) {
                // Generate unique code
                $user = $referralCode->user;
                $baseCode = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $user->name), 0, 6));
                $referralCode->code = $baseCode . rand(1000, 9999);
                
                // Ensure uniqueness
                while (ReferralCode::where('code', $referralCode->code)->exists()) {
                    $referralCode->code = $baseCode . rand(1000, 9999);
                }
            }
            if (!$referralCode->link) {
                $referralCode->link = "sweat24.com/join?ref=" . $referralCode->code;
            }
        });
    }
}
