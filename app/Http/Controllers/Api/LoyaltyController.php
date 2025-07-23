<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyReward;
use App\Models\LoyaltyRedemption;
use App\Services\LoyaltyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoyaltyController extends Controller
{
    protected $loyaltyService;

    public function __construct(LoyaltyService $loyaltyService)
    {
        $this->loyaltyService = $loyaltyService;
    }

    /**
     * Get user's loyalty points dashboard
     */
    public function dashboard()
    {
        $user = Auth::user();
        
        $dashboard = [
            'current_balance' => $user->loyalty_points_balance,
            'expiring_points' => $user->expiring_points,
            'lifetime_earned' => $user->loyaltyPoints()->where('type', 'earned')->sum('amount'),
            'lifetime_redeemed' => abs($user->loyaltyPoints()->where('type', 'redeemed')->sum('amount')),
            'pending_redemptions' => $user->loyaltyRedemptions()
                                         ->whereIn('status', ['pending', 'approved'])
                                         ->count(),
            'recent_transactions' => $user->loyaltyPoints()
                                         ->with('reference')
                                         ->latest()
                                         ->limit(10)
                                         ->get(),
            'available_rewards_count' => $this->loyaltyService->getAvailableRewardsForUser($user)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $dashboard,
        ]);
    }

    /**
     * Get available rewards for the user
     */
    public function availableRewards(Request $request)
    {
        $user = Auth::user();
        $rewards = $this->loyaltyService->getAvailableRewardsForUser($user);

        // Φιλτράρισμα ανά τύπο
        if ($request->has('type')) {
            $rewards = $rewards->where('type', $request->type);
        }

        // Φιλτράρισμα μόνο αυτά που έχει αρκετούς πόντους
        if ($request->boolean('affordable_only')) {
            $rewards = $rewards->where('is_affordable', true);
        }

        // Ταξινόμηση
        $sortBy = $request->get('sort_by', 'points_cost');
        $sortDirection = $request->get('sort_direction', 'asc');
        
        $rewards = $rewards->sortBy($sortBy, SORT_REGULAR, $sortDirection === 'desc');

        return response()->json([
            'success' => true,
            'data' => [
                'user_balance' => $user->loyalty_points_balance,
                'rewards' => $rewards->values(),
            ],
        ]);
    }

    /**
     * Redeem a loyalty reward
     */
    public function redeemReward(Request $request, LoyaltyReward $loyaltyReward)
    {
        $user = Auth::user();

        try {
            $redemption = $this->loyaltyService->redeemReward($user, $loyaltyReward);

            return response()->json([
                'success' => true,
                'message' => 'Το δώρο εξαργυρώθηκε επιτυχώς!',
                'data' => [
                    'redemption' => $redemption,
                    'new_balance' => $user->fresh()->loyalty_points_balance,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get user's redemptions
     */
    public function myRedemptions(Request $request)
    {
        $user = Auth::user();
        
        $query = $user->loyaltyRedemptions()->with('loyaltyReward');

        // Φιλτράρισμα ανά status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Φιλτράρισμα ενεργών (δεν έχουν λήξει)
        if ($request->boolean('active_only')) {
            $query->active();
        }

        $redemptions = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $redemptions,
        ]);
    }

    /**
     * Get user's loyalty points history
     */
    public function pointsHistory(Request $request)
    {
        $user = Auth::user();
        
        $query = $user->loyaltyPoints()->with('reference');

        // Φιλτράρισμα ανά τύπο
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Φιλτράρισμα ανά πηγή
        if ($request->has('source')) {
            $query->where('source', $request->source);
        }

        // Φιλτράρισμα ανά ημερομηνία
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $history = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Check redemption by code
     */
    public function checkRedemption($redemptionCode)
    {
        $redemption = LoyaltyRedemption::where('redemption_code', $redemptionCode)
                                      ->with(['user', 'loyaltyReward'])
                                      ->first();

        if (!$redemption) {
            return response()->json([
                'success' => false,
                'message' => 'Ο κωδικός εξαργύρωσης δεν βρέθηκε',
            ], 404);
        }

        // Έλεγχος αν ο κωδικός ανήκει στον τρέχοντα χρήστη
        if ($redemption->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Δεν έχετε δικαίωμα πρόσβασης σε αυτόν τον κωδικό',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'redemption' => $redemption,
                'is_expired' => $redemption->isExpired(),
                'is_active' => $redemption->isActive(),
                'can_be_used' => $redemption->isActive() && !$redemption->used_at,
            ],
        ]);
    }

    /**
     * Get loyalty program stats for admin
     */
    public function stats()
    {
        // Μόνο για admins
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $stats = $this->loyaltyService->getLoyaltyStats();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
