<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserPoints;
use App\Models\PointsTransaction;
use App\Models\PointsReward;
use App\Models\RewardRedemption;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PointsService
{
    /**
     * Add points to user account
     */
    public function addPoints(
        int $userId, 
        int $amount, 
        string $source, 
        string $description, 
        $referenceId = null, 
        $referenceType = null,
        array $metadata = []
    ): int {
        return DB::transaction(function () use ($userId, $amount, $source, $description, $referenceId, $referenceType, $metadata) {
            // Get or create user points record
            $userPoints = UserPoints::firstOrCreate(
                ['user_id' => $userId],
                [
                    'points_balance' => 0,
                    'total_earned' => 0,
                    'total_spent' => 0
                ]
            );

            // Calculate new balance
            $newBalance = $userPoints->points_balance + $amount;
            $newTotalEarned = $userPoints->total_earned + $amount;

            // Update user points
            $userPoints->update([
                'points_balance' => $newBalance,
                'total_earned' => $newTotalEarned
            ]);

            // Create transaction record
            PointsTransaction::create([
                'user_id' => $userId,
                'amount' => $amount,
                'type' => 'earned',
                'source' => $source,
                'description' => $description,
                'balance_after' => $newBalance,
                'reference_id' => $referenceId,
                'reference_type' => $referenceType,
                'metadata' => $metadata
            ]);

            return $newBalance;
        });
    }

    /**
     * Spend points from user account
     */
    public function spendPoints(
        int $userId, 
        int $amount, 
        string $source, 
        string $description,
        $referenceId = null,
        $referenceType = null,
        array $metadata = []
    ): int {
        return DB::transaction(function () use ($userId, $amount, $source, $description, $referenceId, $referenceType, $metadata) {
            $userPoints = UserPoints::where('user_id', $userId)->first();
            
            if (!$userPoints || $userPoints->points_balance < $amount) {
                throw new \Exception('Insufficient points');
            }

            // Calculate new balance
            $newBalance = $userPoints->points_balance - $amount;
            $newTotalSpent = $userPoints->total_spent + $amount;

            // Update user points
            $userPoints->update([
                'points_balance' => $newBalance,
                'total_spent' => $newTotalSpent
            ]);

            // Create transaction record
            PointsTransaction::create([
                'user_id' => $userId,
                'amount' => $amount,
                'type' => 'spent',
                'source' => $source,
                'description' => $description,
                'balance_after' => $newBalance,
                'reference_id' => $referenceId,
                'reference_type' => $referenceType,
                'metadata' => $metadata
            ]);

            return $newBalance;
        });
    }

    /**
     * Get user points balance
     */
    public function getUserBalance(int $userId): int
    {
        $userPoints = UserPoints::where('user_id', $userId)->first();
        return $userPoints ? $userPoints->points_balance : 0;
    }

    /**
     * Award points for purchase
     */
    public function awardPurchasePoints(int $userId, float $amount, int $orderId = null): int
    {
        $pointsPerEuro = 1.0; // Default 1 point per euro
        $points = floor($amount * $pointsPerEuro);
        
        return $this->addPoints(
            $userId,
            $points,
            'purchase',
            "Αγορά πακέτου €{$amount}",
            $orderId,
            'order'
        );
    }

    /**
     * Award points for order completion (used by OrderObserver)
     */
    public function awardPointsForOrder(\App\Models\Order $order): bool
    {
        try {
            // Check if points have already been applied to this order
            if ($order->points_applied) {
                \Log::info("Points already applied to order {$order->id}");
                return false;
            }

            // Calculate points based on order total
            $pointsPerEuro = 1.0; // Default 1 point per euro
            $points = floor($order->total * $pointsPerEuro);
            
            if ($points > 0) {
                // Award points
                $newBalance = $this->addPoints(
                    $order->user_id,
                    $points,
                    'purchase',
                    "Αγορά παραγγελίας #{$order->order_number}",
                    $order->id,
                    'order'
                );

                // Mark order as points applied
                $order->update([
                    'points_applied' => true,
                    'points_awarded' => $points,
                    'points_applied_at' => now()
                ]);

                \Log::info("Awarded {$points} points for order {$order->id}. New balance: {$newBalance}");
                return true;
            }

            return false;
        } catch (\Exception $e) {
            \Log::error("Failed to award points for order {$order->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if order has points applied
     */
    public function hasPointsApplied(\App\Models\Order $order): bool
    {
        return $order->points_applied ?? false;
    }

    /**
     * Redeem a reward with user points
     */
    public function redeemReward(int $userId, int $rewardId): \App\Models\RewardRedemption
    {
        // Get reward
        $reward = \App\Models\PointsReward::findOrFail($rewardId);
        
        // Validate reward is active
        if (!$reward->is_active) {
            throw new \Exception('Η ανταμοιβή δεν είναι ενεργή.');
        }

        // Check if reward has expired
        if ($reward->expires_at && $reward->expires_at < now()) {
            throw new \Exception('Η ανταμοιβή έχει λήξει.');
        }

        // Check max redemptions limit
        if ($reward->max_redemptions && $reward->current_redemptions >= $reward->max_redemptions) {
            throw new \Exception('Η ανταμοιβή δεν είναι πλέον διαθέσιμη.');
        }

        // Get user points
        $userPoints = \App\Models\UserPoints::where('user_id', $userId)->first();
        if (!$userPoints) {
            throw new \Exception('Δεν βρέθηκαν πόντοι για τον χρήστη.');
        }

        // Check if user has enough points
        if ($userPoints->points_balance < $reward->points_cost) {
            throw new \Exception('Δεν έχετε αρκετούς πόντους για αυτή την ανταμοιβή.');
        }

        \DB::beginTransaction();
        
        try {
            // Deduct points
            $this->spendPoints(
                $userId,
                $reward->points_cost,
                'redemption',
                "Εξαργύρωση: {$reward->name}",
                $rewardId,
                'reward'
            );

            // Generate unique reward code
            $rewardCode = $this->generateRewardCode($reward);

            // Create redemption record
            $redemption = \App\Models\RewardRedemption::create([
                'user_id' => $userId,
                'reward_id' => $rewardId,
                'points_spent' => $reward->points_cost,
                'reward_code' => $rewardCode,
                'status' => 'active',
                'instructions' => $this->getRewardInstructions($reward),
                'expires_at' => $this->calculateRewardExpiry($reward)
            ]);

            // Update reward redemption count
            $reward->increment('current_redemptions');

            \DB::commit();
            
            // Load the reward relationship
            $redemption->load('reward');
            
            return $redemption;

        } catch (\Exception $e) {
            \DB::rollback();
            throw $e;
        }
    }

    /**
     * Generate unique reward code
     */
    private function generateRewardCode(\App\Models\PointsReward $reward): string
    {
        do {
            // Generate code based on reward type
            $prefix = match($reward->reward_type) {
                'gift_card' => 'GC',
                'free_session' => 'FS', 
                'discount' => 'DC',
                'premium' => 'PM',
                'merchandise' => 'MR',
                'product' => 'PR',
                default => 'RW'
            };
            
            $code = $prefix . '-' . strtoupper(substr(uniqid(), -8));
            
        } while (\App\Models\RewardRedemption::where('reward_code', $code)->exists());
        
        return $code;
    }

    /**
     * Get reward instructions
     */
    private function getRewardInstructions(\App\Models\PointsReward $reward): string
    {
        return match($reward->reward_type) {
            'gift_card' => "Παρουσιάστε αυτόν τον κωδικό στη reception για να χρησιμοποιήσετε τη δωροκάρτα αξίας {$reward->reward_value}.",
            'free_session' => "Επικοινωνήστε με τη reception για να κλείσετε το ραντεβού σας. Αναφέρετε τον κωδικό εξαργύρωσης.",
            'discount' => "Χρησιμοποιήστε αυτόν τον κωδικό κατά την ανανέωση της συνδρομής σας για έκπτωση {$reward->reward_value}.",
            'premium' => "Οι premium υπηρεσίες θα ενεργοποιηθούν αυτόματα στον λογαριασμό σας.",
            'merchandise' => "Παρουσιάστε αυτόν τον κωδικό στη reception για να παραλάβετε το προϊόν.",
            'product' => "Παρουσιάστε αυτόν τον κωδικό στη reception για να παραλάβετε το προϊόν.",
            default => "Παρουσιάστε αυτόν τον κωδικό στη reception."
        };
    }

    /**
     * Calculate reward expiry date
     */
    private function calculateRewardExpiry(\App\Models\PointsReward $reward): ?\Carbon\Carbon
    {
        if ($reward->expires_at) {
            return $reward->expires_at;
        }

        // Default expiry based on reward type
        return match($reward->reward_type) {
            'gift_card' => now()->addMonths(12),
            'free_session' => now()->addMonths(3),
            'discount' => now()->addDays(30),
            'premium' => null, // No expiry for premium
            'merchandise' => now()->addMonths(6),
            'product' => now()->addMonths(6),
            default => now()->addMonths(6)
        };
    }
}