<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyReward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LoyaltyRewardController extends Controller
{
    /**
     * Display a listing of loyalty rewards.
     */
    public function index(Request $request)
    {
        $query = LoyaltyReward::query();

        // Φιλτράρισμα
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Αναζήτηση
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Ταξινόμηση
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $rewards = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $rewards,
        ]);
    }

    /**
     * Store a newly created loyalty reward.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'image_url' => 'nullable|url|max:500',
            'points_cost' => 'required|integer|min:1',
            'validity_days' => 'nullable|integer|min:1',
            'type' => 'required|in:gift,discount,service,product',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'max_redemptions' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after:valid_from',
            'terms_conditions' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Σφάλματα επικύρωσης',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['is_active'] = $request->get('is_active', true);
        $data['current_redemptions'] = 0;

        $reward = LoyaltyReward::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Το δώρο ανταμοιβής δημιουργήθηκε επιτυχώς',
            'data' => $reward,
        ], 201);
    }

    /**
     * Display the specified loyalty reward.
     */
    public function show(LoyaltyReward $loyaltyReward)
    {
        $loyaltyReward->load('redemptions.user');
        
        return response()->json([
            'success' => true,
            'data' => $loyaltyReward,
        ]);
    }

    /**
     * Update the specified loyalty reward.
     */
    public function update(Request $request, LoyaltyReward $loyaltyReward)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'image_url' => 'nullable|url|max:500',
            'points_cost' => 'required|integer|min:1',
            'validity_days' => 'nullable|integer|min:1',
            'type' => 'required|in:gift,discount,service,product',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'max_redemptions' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after:valid_from',
            'terms_conditions' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Σφάλματα επικύρωσης',
                'errors' => $validator->errors(),
            ], 422);
        }

        $loyaltyReward->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Το δώρο ανταμοιβής ενημερώθηκε επιτυχώς',
            'data' => $loyaltyReward->fresh(),
        ]);
    }

    /**
     * Remove the specified loyalty reward.
     */
    public function destroy(LoyaltyReward $loyaltyReward)
    {
        // Έλεγχος αν υπάρχουν ενεργές εξαργυρώσεις
        $activeRedemptions = $loyaltyReward->redemptions()
                                         ->whereIn('status', ['pending', 'approved'])
                                         ->count();

        if ($activeRedemptions > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Δεν μπορείτε να διαγράψετε αυτό το δώρο γιατί υπάρχουν ενεργές εξαργυρώσεις',
            ], 400);
        }

        $loyaltyReward->delete();

        return response()->json([
            'success' => true,
            'message' => 'Το δώρο ανταμοιβής διαγράφηκε επιτυχώς',
        ]);
    }

    /**
     * Toggle active status
     */
    public function toggleStatus(LoyaltyReward $loyaltyReward)
    {
        $loyaltyReward->update([
            'is_active' => !$loyaltyReward->is_active
        ]);

        return response()->json([
            'success' => true,
            'message' => $loyaltyReward->is_active 
                ? 'Το δώρο ενεργοποιήθηκε' 
                : 'Το δώρο απενεργοποιήθηκε',
            'data' => $loyaltyReward,
        ]);
    }

    /**
     * Get redemptions for a specific reward
     */
    public function redemptions(LoyaltyReward $loyaltyReward, Request $request)
    {
        $query = $loyaltyReward->redemptions()->with('user');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $redemptions = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $redemptions,
        ]);
    }
}
