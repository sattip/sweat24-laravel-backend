<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralRewardTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrals_required',
        'name',
        'description',
        'reward_type',
        'discount_percentage',
        'discount_amount',
        'validity_days',
        'quarterly_only',
        'next_renewal_only',
        'is_active',
        'terms_conditions',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'quarterly_only' => 'boolean',
        'next_renewal_only' => 'boolean',
        'is_active' => 'boolean',
        'terms_conditions' => 'array',
    ];

    /**
     * Scope για ενεργά tiers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope για tiers ταξινομημένα ανά referrals_required
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('referrals_required', 'asc');
    }

    /**
     * Έλεγχος αν το tier ισχύει για συγκεκριμένο πακέτο
     */
    public function isValidForPackage($packageType)
    {
        if ($this->quarterly_only) {
            // Έλεγχος αν είναι τρίμηνο πακέτο (π.χ. duration >= 90 ημέρες)
            return in_array($packageType, ['quarterly', 'trimester', '3_months']) || 
                   (is_numeric($packageType) && $packageType >= 90);
        }

        return true;
    }

    /**
     * Υπολογισμός τελικής έκπτωσης
     */
    public function calculateDiscount($originalAmount)
    {
        if ($this->discount_percentage) {
            return $originalAmount * ($this->discount_percentage / 100);
        }

        if ($this->discount_amount) {
            return min($this->discount_amount, $originalAmount);
        }

        return 0;
    }

    /**
     * Περιγραφή του δώρου
     */
    public function getRewardDescriptionAttribute()
    {
        switch ($this->reward_type) {
            case 'discount':
                if ($this->discount_percentage) {
                    return "Έκπτωση {$this->discount_percentage}%";
                }
                if ($this->discount_amount) {
                    return "Έκπτωση €{$this->discount_amount}";
                }
                break;
            case 'free_month':
                return 'Δωρεάν μήνας';
            case 'personal_training':
                return 'Δωρεάν προσωπική προπόνηση';
            default:
                return $this->description;
        }

        return $this->description;
    }
}
