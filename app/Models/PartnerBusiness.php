<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerBusiness extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'logo_url',
        'description',
        'contact_email',
        'contact_phone',
        'phone',
        'address',
        'is_active',
        'display_order',
    ];
    
    protected $appends = ['phone'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function offers()
    {
        return $this->hasMany(PartnerOffer::class);
    }

    public function activeOffers()
    {
        return $this->hasMany(PartnerOffer::class)->where('is_active', true);
    }
    
    // Accessor for admin panel compatibility
    public function getPhoneAttribute()
    {
        return $this->contact_phone ?? $this->attributes['phone'] ?? '';
    }
}
