<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyReward;
use App\Models\LoyaltyRedemption;
use App\Models\FitnessClass;
use App\Models\Booking;
use App\Services\LoyaltyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        $currentPoints = $user->loyalty_points_balance ?? 0;
        $totalEarnedPoints = $user->loyaltyPoints()->where('type', 'earned')->sum('amount') ?? 0;
        $redeemedRewardsCount = $user->loyaltyRedemptions()->count();

        $tiers = config('loyalty.tiers');

        // Determine current and next tier based on total earned points
        $currentTier = $tiers[0];
        $nextTier = null;

        foreach ($tiers as $index => $tier) {
            if ($totalEarnedPoints >= $tier['min_points']) {
                $currentTier = $tier;
                if (isset($tiers[$index + 1])) {
                    $nextTier = $tiers[$index + 1];
                }
            }
        }

        $dashboard = [
            // Fields expected by frontend
            'current_points' => (int) $currentPoints,
            'total_earned_points' => (int) $totalEarnedPoints,
            'redeemed_rewards_count' => $redeemedRewardsCount,
            'current_tier' => [
                'name' => $currentTier['name'],
                'benefits' => $currentTier['benefits'],
            ],
            'next_tier' => $nextTier ? [
                'name' => $nextTier['name'],
                'points_required' => $nextTier['min_points'],
                'points_needed' => max(0, $nextTier['min_points'] - $totalEarnedPoints),
            ] : null,

            // Additional fields for backwards compatibility
            'current_balance' => (int) $currentPoints,
            'expiring_points' => $user->expiring_points ?? 0,
            'lifetime_earned' => (int) $totalEarnedPoints,
            'lifetime_redeemed' => abs($user->loyaltyPoints()->where('type', 'redeemed')->sum('amount') ?? 0),
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
        $userBalance = $user->loyalty_points_balance ?? 0;
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

        // Transform rewards to match expected format
        $transformedRewards = $rewards->values()->map(function ($reward) use ($userBalance) {
            return [
                'id' => $reward->id,
                'name' => $reward->name,
                'description' => $reward->description,
                'image_url' => $reward->image_url,
                'points_required' => (int) ($reward->points_cost ?? 0), // Frontend expects points_required
                'points_cost' => (int) ($reward->points_cost ?? 0), // Keep for backwards compatibility
                'category' => $reward->type ?? 'general', // Map type to category
                'type' => $reward->type,
                'is_affordable' => $userBalance >= ($reward->points_cost ?? 0),
                'is_limited_time' => $reward->valid_until !== null,
                'is_available' => $reward->is_available ?? true,
                'valid_from' => $reward->valid_from?->format('Y-m-d'),
                'valid_until' => $reward->valid_until?->format('Y-m-d'),
                'redemptions_remaining' => $reward->redemptions_remaining,
                'terms_conditions' => $reward->terms_conditions,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'user_balance' => $userBalance,
                'rewards' => $transformedRewards,
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

            // If reward is 'free_session', add bonus session to active membership
            if ($loyaltyReward->type === 'free_session') {
                $activeMembership = $user->userPackages()
                    ->where('status', 'active')
                    ->whereDate('expiry_date', '>=', now())
                    ->first();

                if ($activeMembership) {
                    $activeMembership->increment('bonus_sessions');

                    Log::info('Bonus session added', [
                        'user_id' => $user->id,
                        'package_id' => $activeMembership->id,
                        'bonus_sessions' => $activeMembership->bonus_sessions,
                    ]);
                }
            }

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

    /**
     * Get all redemptions for admin
     */
    public function adminGetRedemptions(Request $request)
    {
        // Μόνο για admins
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $query = LoyaltyRedemption::with(['user', 'loyaltyReward']);

        // Φιλτράρισμα ανά status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Φιλτράρισμα ανά χρήστη
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Φιλτράρισμα ανά reward
        if ($request->has('loyalty_reward_id')) {
            $query->where('loyalty_reward_id', $request->loyalty_reward_id);
        }

        // Φιλτράρισμα ανά ημερομηνία
        if ($request->has('from_date')) {
            $query->whereDate('redeemed_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('redeemed_at', '<=', $request->to_date);
        }

        // Ταξινόμηση
        $sortBy = $request->get('sort_by', 'redeemed_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $redemptions = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $redemptions,
        ]);
    }

    /**
     * Get points cost for booking a class
     */
    public function getBookingPointsCost($classId)
    {
        try {
            $user = Auth::user();
            $class = FitnessClass::findOrFail($classId);

            // 1 EUR = 1 point
            $pointsCost = (int) $class->price;

            return response()->json([
                'success' => true,
                'data' => [
                    'class_id' => $classId,
                    'class_name' => $class->name,
                    'price_eur' => $class->price,
                    'points_cost' => $pointsCost,
                    'user_balance' => $user->loyalty_points_balance ?? 0,
                    'can_afford' => ($user->loyalty_points_balance ?? 0) >= $pointsCost,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Σφάλμα κατά την ανάκτηση κόστους: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Book a class using loyalty points
     */
    public function bookWithPoints(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:fitness_classes,id',
            'payment_type' => 'required|in:full_points,partial_points,cash_only',
            'points_to_use' => 'required|integer|min:0',
            'cash_amount' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $user = Auth::user();
            $class = FitnessClass::findOrFail($validated['class_id']);
            $pointsBalance = $user->loyalty_points_balance ?? 0;

            // Validate points balance if using points
            if ($validated['points_to_use'] > 0) {
                if ($pointsBalance < $validated['points_to_use']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Δεν έχετε αρκετούς πόντους. Διαθέσιμοι: ' . $pointsBalance,
                    ], 400);
                }
            }

            // Calculate payment
            $totalCost = $class->price;
            $pointsValue = $validated['points_to_use']; // 1 point = 1 EUR
            $cashRequired = max(0, $totalCost - $pointsValue);

            // Validate full payment covers the cost
            if ($validated['payment_type'] === 'full_points' && $pointsValue < $totalCost) {
                return response()->json([
                    'success' => false,
                    'message' => 'Δεν έχετε αρκετούς πόντους για πλήρη πληρωμή.',
                ], 400);
            }

            // Create booking
            $booking = Booking::create([
                'user_id' => $user->id,
                'class_id' => $class->id,
                'store_id' => $class->store_id,
                'status' => 'confirmed',
                'points_used' => $validated['points_to_use'],
                'cash_paid' => $validated['cash_amount'] ?? $cashRequired,
                'total_cost' => $totalCost,
            ]);

            // Deduct points if used
            if ($validated['points_to_use'] > 0) {
                $this->loyaltyService->deductPoints(
                    $user->id,
                    $validated['points_to_use'],
                    'booking',
                    $booking->id,
                    "Κράτηση μαθήματος: {$class->name}"
                );
            }

            DB::commit();

            Log::info('Booking created with loyalty points', [
                'user_id' => $user->id,
                'booking_id' => $booking->id,
                'class_id' => $class->id,
                'points_used' => $validated['points_to_use'],
                'cash_paid' => $validated['cash_amount'] ?? $cashRequired,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Η κράτηση ολοκληρώθηκε επιτυχώς!',
                'data' => [
                    'booking' => $booking->load('fitnessClass', 'store'),
                    'remaining_points' => $user->fresh()->loyalty_points_balance ?? 0,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to book with points', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Σφάλμα κατά την κράτηση: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate mixed payment (points + cash)
     */
    public function calculateMixedPayment(Request $request)
    {
        $validated = $request->validate([
            'item_type' => 'required|in:class,package,product',
            'item_id' => 'required|integer',
            'points_to_use' => 'required|integer|min:0',
        ]);

        try {
            $user = Auth::user();
            $pointsBalance = $user->loyalty_points_balance ?? 0;

            // Get item price
            $price = 0;
            $itemName = '';

            switch ($validated['item_type']) {
                case 'class':
                    $item = FitnessClass::findOrFail($validated['item_id']);
                    $price = $item->price;
                    $itemName = $item->name;
                    break;
                case 'package':
                    // Future: Add package logic
                    return response()->json([
                        'success' => false,
                        'message' => 'Packages not yet supported',
                    ], 400);
                case 'product':
                    // Future: Add product logic
                    return response()->json([
                        'success' => false,
                        'message' => 'Products not yet supported',
                    ], 400);
            }

            $calculation = $this->loyaltyService->calculateMixedPayment(
                $user->id,
                $price,
                $validated['points_to_use']
            );

            $calculation['item_name'] = $itemName;

            return response()->json([
                'success' => true,
                'data' => $calculation,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Σφάλμα κατά τον υπολογισμό: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get transaction history for user
     */
    public function transactionHistory(Request $request)
    {
        try {
            $user = Auth::user();
            $limit = $request->get('limit', 50);

            $transactions = $this->loyaltyService->getTransactionHistory($user->id, $limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'transactions' => $transactions,
                    'current_balance' => $user->loyalty_points_balance ?? 0,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Σφάλμα κατά την ανάκτηση ιστορικού: ' . $e->getMessage(),
            ], 400);
        }
    }
}
