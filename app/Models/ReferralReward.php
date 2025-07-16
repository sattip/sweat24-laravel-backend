<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'type',
        'value',
        'status',
        'earned_at',
        'expires_at',
        'redeemed_at',
        'referrals_required',
        'points_required',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'earned_at' => 'datetime',
        'expires_at' => 'datetime',
        'redeemed_at' => 'datetime',
        'is_active' => 'boolean',
    ];
    
    protected $appends = ['redemptions_count'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function redemptions()
    {
        return $this->hasMany(ReferralRedemption::class, 'reward_id');
    }
    
    public function getRedemptionsCountAttribute()
    {
        return $this->redemptions()->count();
    }
}
