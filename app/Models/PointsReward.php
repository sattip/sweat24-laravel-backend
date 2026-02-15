<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PointsReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'points_cost',
        'reward_type',
        'reward_value',
        'image_url',
        'is_active',
        'max_redemptions',
        'current_redemptions',
        'sort_order',
        'terms_conditions',
        'expires_at',
    ];

    protected $casts = [
        'points_cost' => 'integer',
        'is_active' => 'boolean',
        'max_redemptions' => 'integer',
        'current_redemptions' => 'integer',
        'sort_order' => 'integer',
        'expires_at' => 'datetime',
    ];

    public function redemptions(): HasMany
    {
        return $this->hasMany(RewardRedemption::class, 'reward_id');
    }

    /**
     * Scope για ενεργές ανταμοιβές
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope για ανταμοιβές ανά τύπο
     */
    public function scopeByType($query, $type)
    {
        return $query->where('reward_type', $type);
    }

    /**
     * Scope για ανταμοιβές έως συγκεκριμένο κόστος πόντων
     */
    public function scopeAffordable($query, $maxPoints)
    {
        return $query->where('points_cost', '<=', $maxPoints);
    }

    /**
     * Check if reward is available for redemption
     */
    public function isAvailable(): bool
    {
        return $this->is_active && 
               (!$this->expires_at || $this->expires_at->isFuture()) &&
               (!$this->max_redemptions || $this->current_redemptions < $this->max_redemptions);
    }

    /**
     * Ενημέρωση του μετρητή εξαργυρώσεων
     */
    public function incrementRedemptions()
    {
        $this->increment('current_redemptions');
    }

    /**
     * Check if reward has stock available
     */
    public function hasStock(): bool
    {
        return is_null($this->max_redemptions) || 
               $this->current_redemptions < $this->max_redemptions;
    }

    /**
     * Get remaining stock
     */
    public function getRemainingStock(): ?int
    {
        if (is_null($this->max_redemptions)) {
            return null; // Unlimited
        }
        
        return max(0, $this->max_redemptions - $this->current_redemptions);
    }

    /**
     * Λήψη του ετικέτας τύπου ανταμοιβής
     */
    public function getRewardTypeLabel(): string
    {
        return match($this->reward_type) {
            'gift_card' => 'Δωροκάρτα',
            'free_session' => 'Δωρεάν Μάθημα',
            'product' => 'Προϊόν',
            'discount' => 'Έκπτωση',
            'premium' => 'Premium Feature',
            'merchandise' => 'Merchandise',
            default => $this->reward_type,
        };
    }

    /**
     * Έλεγχος αν η ανταμοιβή είναι προσιτή για τον χρήστη
     */
    public function isAffordableFor($userPoints): bool
    {
        return $this->isAvailable() && $this->points_cost <= $userPoints;
    }
}
