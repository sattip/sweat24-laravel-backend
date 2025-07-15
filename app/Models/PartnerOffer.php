<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_business_id',
        'title',
        'description',
        'type',
        'discount_value',
        'discount_unit',
        'discount_percentage',
        'promo_code',
        'is_active',
        'valid_from',
        'valid_until',
        'usage_limit',
        'usage_limit_per_user',
        'total_usage_limit',
        'current_usage_count',
        'used_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'discount_value' => 'decimal:2',
    ];
    
    protected $appends = ['used_count', 'usage_limit'];

    public function partnerBusiness()
    {
        return $this->belongsTo(PartnerBusiness::class);
    }

    public function redemptions()
    {
        return $this->hasMany(OfferRedemption::class);
    }

    public function getFormattedOfferAttribute()
    {
        if ($this->type === 'percentage' || $this->discount_percentage) {
            return ($this->discount_percentage ?? $this->discount_value) . '% έκπτωση';
        } elseif ($this->type === 'fixed_amount') {
            return '€' . $this->discount_value . ' έκπτωση';
        }
        return $this->title;
    }
    
    // Accessor for admin panel compatibility
    public function getUsedCountAttribute()
    {
        return $this->current_usage_count ?? 0;
    }
    
    // Accessor for admin panel compatibility
    public function getUsageLimitAttribute()
    {
        return $this->total_usage_limit ?? 0;
    }
}
