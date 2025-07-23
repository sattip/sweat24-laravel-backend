<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReferralRewardTier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReferralRewardTierController extends Controller
{
    /**
     * Display a listing of referral reward tiers.
     */
    public function index(Request $request)
    {
        $query = ReferralRewardTier::query();

        // Φιλτράρισμα
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('reward_type')) {
            $query->where('reward_type', $request->reward_type);
        }

        // Ταξινόμηση
        $sortBy = $request->get('sort_by', 'referrals_required');
        $sortDirection = $request->get('sort_direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);

        $tiers = $query->get();

        return response()->json([
            'success' => true,
            'data' => $tiers,
        ]);
    }

    /**
     * Store a newly created referral reward tier.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'referrals_required' => 'required|integer|min:1|unique:referral_reward_tiers',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'reward_type' => 'required|in:discount,free_month,personal_training,custom',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'validity_days' => 'required|integer|min:1',
            'quarterly_only' => 'boolean',
            'next_renewal_only' => 'boolean',
            'is_active' => 'boolean',
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
        $data['quarterly_only'] = $request->get('quarterly_only', true);
        $data['next_renewal_only'] = $request->get('next_renewal_only', true);

        $tier = ReferralRewardTier::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Το tier ανταμοιβής συστάσεων δημιουργήθηκε επιτυχώς',
            'data' => $tier,
        ], 201);
    }

    /**
     * Display the specified referral reward tier.
     */
    public function show(ReferralRewardTier $referralRewardTier)
    {
        return response()->json([
            'success' => true,
            'data' => $referralRewardTier,
        ]);
    }

    /**
     * Update the specified referral reward tier.
     */
    public function update(Request $request, ReferralRewardTier $referralRewardTier)
    {
        $validator = Validator::make($request->all(), [
            'referrals_required' => 'required|integer|min:1|unique:referral_reward_tiers,referrals_required,' . $referralRewardTier->id,
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'reward_type' => 'required|in:discount,free_month,personal_training,custom',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'validity_days' => 'required|integer|min:1',
            'quarterly_only' => 'boolean',
            'next_renewal_only' => 'boolean',
            'is_active' => 'boolean',
            'terms_conditions' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Σφάλματα επικύρωσης',
                'errors' => $validator->errors(),
            ], 422);
        }

        $referralRewardTier->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Το tier ανταμοιβής συστάσεων ενημερώθηκε επιτυχώς',
            'data' => $referralRewardTier->fresh(),
        ]);
    }

    /**
     * Remove the specified referral reward tier.
     */
    public function destroy(ReferralRewardTier $referralRewardTier)
    {
        $referralRewardTier->delete();

        return response()->json([
            'success' => true,
            'message' => 'Το tier ανταμοιβής συστάσεων διαγράφηκε επιτυχώς',
        ]);
    }

    /**
     * Toggle active status
     */
    public function toggleStatus(ReferralRewardTier $referralRewardTier)
    {
        $referralRewardTier->update([
            'is_active' => !$referralRewardTier->is_active
        ]);

        return response()->json([
            'success' => true,
            'message' => $referralRewardTier->is_active 
                ? 'Το tier ενεργοποιήθηκε' 
                : 'Το tier απενεργοποιήθηκε',
            'data' => $referralRewardTier,
        ]);
    }
}
