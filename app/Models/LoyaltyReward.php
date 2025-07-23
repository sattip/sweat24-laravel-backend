<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'image_url',
        'points_cost',
        'validity_days',
        'type',
        'discount_percentage',
        'discount_amount',
        'max_redemptions',
        'current_redemptions',
        'is_active',
        'valid_from',
        'valid_until',
        'terms_conditions',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'terms_conditions' => 'array',
    ];

    protected $appends = ['is_available', 'redemptions_remaining'];

    public function redemptions()
    {
        return $this->hasMany(LoyaltyRedemption::class);
    }

    /**
     * Έλεγχος αν το reward είναι διαθέσιμο
     */
    public function getIsAvailableAttribute()
    {
        if (!$this->is_active) {
            return false;
        }

        // Έλεγχος ημερομηνιών ισχύος
        $now = now();
        if ($this->valid_from && $now < $this->valid_from) {
            return false;
        }
        if ($this->valid_until && $now > $this->valid_until) {
            return false;
        }

        // Έλεγχος μέγιστων εξαργυρώσεων
        if ($this->max_redemptions && $this->current_redemptions >= $this->max_redemptions) {
            return false;
        }

        return true;
    }

    /**
     * Υπόλοιπες εξαργυρώσεις
     */
    public function getRedemptionsRemainingAttribute()
    {
        if (!$this->max_redemptions) {
            return null; // Απεριόριστες
        }

        return max(0, $this->max_redemptions - $this->current_redemptions);
    }

    /**
     * Έλεγχος αν ο χρήστης μπορεί να εξαργυρώσει το reward
     */
    public function canBeRedeemedBy(User $user)
    {
        if (!$this->is_available) {
            return false;
        }

        // Έλεγχος αν έχει αρκετούς πόντους
        return $user->loyalty_points_balance >= $this->points_cost;
    }

    /**
     * Scope για διαθέσιμα rewards
     */
    public function scopeAvailable($query)
    {
        $now = now();
        return $query->where('is_active', true)
                    ->where(function($q) use ($now) {
                        $q->whereNull('valid_from')
                          ->orWhere('valid_from', '<=', $now);
                    })
                    ->where(function($q) use ($now) {
                        $q->whereNull('valid_until')
                          ->orWhere('valid_until', '>=', $now);
                    })
                    ->where(function($q) {
                        $q->whereNull('max_redemptions')
                          ->orWhereRaw('current_redemptions < max_redemptions');
                    });
    }
}
