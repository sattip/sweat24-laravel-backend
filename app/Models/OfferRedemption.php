<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfferRedemption extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'partner_offer_id',
        'verification_code',
        'status',
        'used_at',
        'expires_at',
        'notes',
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function partnerOffer()
    {
        return $this->belongsTo(PartnerOffer::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($redemption) {
            if (!$redemption->verification_code) {
                $redemption->verification_code = 'S24-' . strtoupper(substr(uniqid(), -6));
            }
            if (!$redemption->expires_at) {
                $redemption->expires_at = now()->addDay(); // Expires after 24 hours
            }
        });
    }
}
