<?php

namespace App\Services;

use App\Models\User;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltyReward;
use App\Models\LoyaltyRedemption;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LoyaltyService
{
    /**
     * Προσθήκη πόντων από πληρωμή (1 ευρώ = 1 πόντος)
     */
    public function addPointsFromPayment(User $user, $paymentAmount, $reference = null)
    {
        $points = floor($paymentAmount); // 1 ευρώ = 1 πόντος (ακέραιος)
        
        if ($points <= 0) {
            return null;
        }

        $description = "Πόντοι από πληρωμή €{$paymentAmount}";
        $expiresAt = now()->addYear(); // Πόντοι λήγουν σε 1 χρόνο

        return $user->addLoyaltyPoints(
            $points,
            $description,
            'payment',
            $reference,
            $expiresAt
        );
    }

    /**
     * Εξαργύρωση reward
     */
    public function redeemReward(User $user, LoyaltyReward $reward)
    {
        if (!$reward->canBeRedeemedBy($user)) {
            throw new \Exception('Δεν μπορείτε να εξαργυρώσετε αυτό το δώρο.');
        }

        DB::beginTransaction();
        try {
            // Αφαίρεση πόντων
            $user->addLoyaltyPoints(
                -$reward->points_cost,
                "Εξαργύρωση: {$reward->name}",
                'redemption'
            );

            // Δημιουργία εξαργύρωσης
            $expiresAt = $reward->validity_days 
                ? now()->addDays($reward->validity_days) 
                : null;

            $redemption = LoyaltyRedemption::create([
                'user_id' => $user->id,
                'loyalty_reward_id' => $reward->id,
                'points_used' => $reward->points_cost,
                'status' => 'approved', // Αυτόματη έγκριση
                'redeemed_at' => now(),
                'expires_at' => $expiresAt,
                'reward_snapshot' => $reward->toArray(),
            ]);

            // Ενημέρωση μετρητή εξαργυρώσεων του reward
            $reward->increment('current_redemptions');

            DB::commit();

            Log::info('Loyalty reward redeemed', [
                'user_id' => $user->id,
                'reward_id' => $reward->id,
                'points_used' => $reward->points_cost,
                'redemption_code' => $redemption->redemption_code,
            ]);

            return $redemption;

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to redeem loyalty reward', [
                'user_id' => $user->id,
                'reward_id' => $reward->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Λήξη πόντων που έχουν λήξει
     */
    public function expirePoints()
    {
        $expiredPoints = LoyaltyPoint::where('expires_at', '<', now())
                                   ->where('type', 'earned')
                                   ->get();

        foreach ($expiredPoints as $point) {
            if ($point->amount > 0) {
                // Δημιουργία αρνητικής εγγραφής για τη λήξη
                $point->user->addLoyaltyPoints(
                    -$point->amount,
                    'Λήξη πόντων',
                    'expiration',
                    $point
                );

                // Ενημέρωση της αρχικής εγγραφής
                $point->update(['type' => 'expired']);
            }
        }

        return $expiredPoints->count();
    }

    /**
     * Υπολογισμός διαθέσιμων rewards για χρήστη
     */
    public function getAvailableRewardsForUser(User $user)
    {
        $userBalance = $user->loyalty_points_balance;
        
        return LoyaltyReward::available()
                           ->get()
                           ->map(function($reward) use ($userBalance) {
                               $reward->setAttribute('is_affordable', $userBalance >= $reward->points_cost);
                               return $reward;
                           });
    }

    /**
     * Στατιστικά loyalty program
     */
    public function getLoyaltyStats()
    {
        return [
            'total_points_issued' => LoyaltyPoint::where('type', 'earned')->sum('amount'),
            'total_points_redeemed' => LoyaltyPoint::where('type', 'redeemed')->sum('amount'),
            'active_points' => LoyaltyPoint::active()->where('type', 'earned')->sum('amount'),
            'total_redemptions' => LoyaltyRedemption::count(),
            'pending_redemptions' => LoyaltyRedemption::where('status', 'pending')->count(),
            'expiring_points_30_days' => LoyaltyPoint::expiringSoon(30)->sum('amount'),
            'users_with_points' => User::whereHas('loyaltyPoints', function($q) {
                $q->active()->where('type', 'earned');
            })->count(),
        ];
    }

    /**
     * Αφαίρεση πόντων από χρήστη
     */
    public function deductPoints($userId, $points, $source, $sourceId = null, $description = null)
    {
        $user = User::findOrFail($userId);
        $currentPoints = $user->loyalty_points_balance ?? 0;

        if ($currentPoints < $points) {
            throw new \Exception('Ανεπαρκείς πόντοι. Διαθέσιμοι: ' . $currentPoints . ', Απαιτούνται: ' . $points);
        }

        // Χρήση της υπάρχουσας μεθόδου addLoyaltyPoints με αρνητικό αριθμό
        $user->addLoyaltyPoints(
            -$points,
            $description ?? "Αφαίρεση πόντων: {$source}",
            $source,
            $sourceId
        );

        // Δημιουργία transaction record
        $balanceAfter = $user->fresh()->loyalty_points_balance ?? 0;

        DB::table('loyalty_transactions')->insert([
            'user_id' => $userId,
            'points' => -$points,
            'type' => 'deduction',
            'source' => $source,
            'source_id' => $sourceId,
            'description' => $description,
            'balance_after' => $balanceAfter,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('Loyalty points deducted', [
            'user_id' => $userId,
            'points' => $points,
            'source' => $source,
            'source_id' => $sourceId,
            'balance_after' => $balanceAfter,
        ]);

        return $balanceAfter;
    }

    /**
     * Προσθήκη πόντων σε χρήστη (για χειροκίνητες προσθήκες)
     */
    public function addPoints($userId, $points, $source, $sourceId = null, $description = null)
    {
        $user = User::findOrFail($userId);

        $user->addLoyaltyPoints(
            $points,
            $description ?? "Προσθήκη πόντων: {$source}",
            $source,
            $sourceId
        );

        // Δημιουργία transaction record
        $balanceAfter = $user->fresh()->loyalty_points_balance ?? 0;

        DB::table('loyalty_transactions')->insert([
            'user_id' => $userId,
            'points' => $points,
            'type' => 'earn',
            'source' => $source,
            'source_id' => $sourceId,
            'description' => $description,
            'balance_after' => $balanceAfter,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('Loyalty points added', [
            'user_id' => $userId,
            'points' => $points,
            'source' => $source,
            'source_id' => $sourceId,
            'balance_after' => $balanceAfter,
        ]);

        return $balanceAfter;
    }

    /**
     * Έλεγχος αν ο χρήστης μπορεί να αντέξει οικονομικά την αγορά με πόντους
     */
    public function canAfford($userId, $pointsCost)
    {
        $user = User::findOrFail($userId);
        return ($user->loyalty_points_balance ?? 0) >= $pointsCost;
    }

    /**
     * Υπολογισμός μικτής πληρωμής (πόντοι + μετρητά)
     */
    public function calculateMixedPayment($userId, $totalPrice, $pointsToUse)
    {
        $user = User::findOrFail($userId);
        $availablePoints = $user->loyalty_points_balance ?? 0;

        // Περιορισμός πόντων στο διαθέσιμο και στο συνολικό κόστος
        $pointsToUse = min($pointsToUse, $availablePoints, $totalPrice);
        $cashRequired = max(0, $totalPrice - $pointsToUse);

        return [
            'total_price' => $totalPrice,
            'points_available' => $availablePoints,
            'points_to_use' => $pointsToUse,
            'points_value_eur' => $pointsToUse, // 1 πόντος = 1 EUR
            'cash_required' => $cashRequired,
            'can_complete' => $pointsToUse <= $availablePoints,
        ];
    }

    /**
     * Λήψη ιστορικού συναλλαγών πόντων για χρήστη
     */
    public function getTransactionHistory($userId, $limit = 50)
    {
        return DB::table('loyalty_transactions')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
} 