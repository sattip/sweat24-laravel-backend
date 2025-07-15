<?php

namespace App\Http\Controllers;

use App\Models\ReferralCode;
use App\Models\Referral;
use App\Models\ReferralReward;
use App\Models\User;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    // Get user's referral data
    public function getUserReferralData(Request $request)
    {
        $user = $request->user();
        
        // Get or create referral code with user relationship
        $referralCode = ReferralCode::with('user')->firstOrCreate(
            ['user_id' => $user->id],
            ['user_id' => $user->id]
        );

        // Get referrals made by this user
        $referrals = Referral::where('referrer_id', $user->id)
            ->with('referredUser')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get rewards for this user
        $rewards = ReferralReward::where('user_id', $user->id)
            ->orderBy('earned_at', 'desc')
            ->get();

        // Calculate next reward milestone
        $totalReferrals = $referrals->count();
        $nextRewardAt = $this->getNextRewardMilestone($totalReferrals);
        $nextReward = $this->getRewardForMilestone($nextRewardAt);

        return response()->json([
            'code' => $referralCode->code,
            'link' => $referralCode->link,
            'referrals' => $totalReferrals,
            'nextRewardAt' => $nextRewardAt,
            'nextReward' => $nextReward,
            'rewards' => $rewards,
            'friends' => $referrals->map(function ($referral) {
                return [
                    'name' => $referral->referredUser->name,
                    'joinDate' => $referral->joined_at ? $referral->joined_at->format('Y-m-d') : $referral->created_at->format('Y-m-d'),
                ];
            }),
        ]);
    }

    // Redeem a reward
    public function redeemReward(Request $request, ReferralReward $reward)
    {
        if ($reward->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($reward->status !== 'available') {
            return response()->json(['message' => 'Reward not available'], 400);
        }

        $reward->update([
            'status' => 'redeemed',
            'redeemed_at' => now(),
        ]);

        return response()->json(['message' => 'Reward redeemed successfully']);
    }

    // Process a referral (called when someone joins with a referral code)
    public function processReferral(Request $request)
    {
        $validated = $request->validate([
            'referral_code' => 'required|string|exists:referral_codes,code',
            'referred_user_id' => 'required|exists:users,id',
        ]);

        $referralCode = ReferralCode::where('code', $validated['referral_code'])->first();
        
        // Create referral record
        $referral = Referral::create([
            'referrer_id' => $referralCode->user_id,
            'referred_user_id' => $validated['referred_user_id'],
            'referral_code_id' => $referralCode->id,
            'status' => 'confirmed',
            'joined_at' => now(),
        ]);

        // Update referral code total
        $referralCode->increment('total_referrals');

        // Check for rewards
        $this->checkAndCreateRewards($referralCode->user_id, $referralCode->total_referrals);

        return response()->json(['message' => 'Referral processed successfully']);
    }

    private function getNextRewardMilestone($currentReferrals)
    {
        $milestones = [1, 3, 5, 10, 20];
        foreach ($milestones as $milestone) {
            if ($currentReferrals < $milestone) {
                return $milestone;
            }
        }
        return $currentReferrals + 5; // Every 5 after 20
    }

    private function getRewardForMilestone($milestone)
    {
        $rewards = [
            1 => "Δωρεάν προσωπική προπόνηση",
            3 => "50% έκπτωση τον επόμενο μήνα",
            5 => "Ένας μήνας δωρεάν συνδρομή",
            10 => "Δωρεάν personal training πακέτο",
            20 => "3 μήνες δωρεάν συνδρομή",
        ];

        return $rewards[$milestone] ?? "Ειδικό δώρο έκπληξη";
    }

    private function checkAndCreateRewards($userId, $totalReferrals)
    {
        $rewardRules = [
            1 => ['name' => 'Δωρεάν Προσωπική Προπόνηση', 'type' => 'personal_training'],
            3 => ['name' => '50% Έκπτωση τον επόμενο μήνα', 'type' => 'discount', 'value' => 50],
            5 => ['name' => 'Ένας μήνας δωρεάν συνδρομή', 'type' => 'free_month'],
        ];

        foreach ($rewardRules as $milestone => $rewardData) {
            if ($totalReferrals >= $milestone) {
                // Check if reward already exists
                $existingReward = ReferralReward::where('user_id', $userId)
                    ->where('referrals_required', $milestone)
                    ->first();

                if (!$existingReward) {
                    ReferralReward::create([
                        'user_id' => $userId,
                        'name' => $rewardData['name'],
                        'type' => $rewardData['type'],
                        'value' => $rewardData['value'] ?? null,
                        'earned_at' => now(),
                        'expires_at' => now()->addMonths(6),
                        'referrals_required' => $milestone,
                    ]);
                }
            }
        }
    }

    // Admin methods
    public function adminGetCodes()
    {
        $codes = ReferralCode::with('user')
            ->withCount('referrals')
            ->get()
            ->map(function ($code) {
                $code->referred_users_count = $code->referrals_count;
                $code->points_earned = $code->points;
                return $code;
            });

        return response()->json($codes);
    }

    public function adminGetRewards()
    {
        // Get system-wide rewards (not user-specific)
        $rewards = ReferralReward::whereNull('user_id')
            ->orderBy('points_required')
            ->get();

        return response()->json($rewards);
    }

    public function adminCreateReward(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'points_required' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $reward = ReferralReward::create($validated);
        return response()->json($reward, 201);
    }

    public function adminUpdateReward(Request $request, ReferralReward $reward)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'points_required' => 'sometimes|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $reward->update($validated);
        return response()->json($reward);
    }

    public function adminDeleteReward(ReferralReward $reward)
    {
        $reward->delete();
        return response()->json(['message' => 'Reward deleted successfully']);
    }

    public function adminGetReferrals()
    {
        $referrals = Referral::with(['referralCode', 'referredUser'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($referrals);
    }
}
