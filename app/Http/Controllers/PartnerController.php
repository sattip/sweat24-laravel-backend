<?php

namespace App\Http\Controllers;

use App\Models\PartnerBusiness;
use App\Models\PartnerOffer;
use App\Models\OfferRedemption;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    // Get all active partner businesses with their offers
    public function index()
    {
        $partners = PartnerBusiness::where('is_active', true)
            ->with(['activeOffers'])
            ->orderBy('display_order')
            ->get();

        $partnersWithFormattedOffers = $partners->map(function ($partner) {
            // Get the primary offer for this partner
            $primaryOffer = $partner->activeOffers->first();
            
            return [
                'id' => $partner->id,
                'name' => $partner->name,
                'logoUrl' => $partner->logo_url ?: '/placeholder.svg',
                'offer' => $primaryOffer ? $primaryOffer->formatted_offer : 'Ειδική προσφορά',
                'description' => $partner->description,
                'offer_id' => $primaryOffer ? $primaryOffer->id : null,
            ];
        });

        return response()->json($partnersWithFormattedOffers);
    }

    // Generate redemption code for an offer
    public function generateRedemptionCode(Request $request, PartnerOffer $offer)
    {
        $user = $request->user();
        
        // Check if user has already used this offer today
        $todayRedemption = OfferRedemption::where('user_id', $user->id)
            ->where('partner_offer_id', $offer->id)
            ->whereDate('created_at', today())
            ->where('status', '!=', 'expired')
            ->first();

        if ($todayRedemption) {
            return response()->json([
                'message' => 'You have already used this offer today',
                'redemption' => $todayRedemption
            ], 400);
        }

        // Check usage limits
        $userUsageCount = OfferRedemption::where('user_id', $user->id)
            ->where('partner_offer_id', $offer->id)
            ->count();

        if ($userUsageCount >= $offer->usage_limit_per_user) {
            return response()->json(['message' => 'Usage limit exceeded'], 400);
        }

        // Create redemption
        $redemption = OfferRedemption::create([
            'user_id' => $user->id,
            'partner_offer_id' => $offer->id,
        ]);

        // Increment usage count
        $offer->increment('current_usage_count');

        return response()->json([
            'redemption' => $redemption,
            'offer' => $offer->load('partnerBusiness'),
            'user' => [
                'name' => $user->name,
                'membership_status' => 'Ενεργός', // You might want to check actual status
                'membership_id' => 'SW24-' . str_pad($user->id, 4, '0', STR_PAD_LEFT),
            ]
        ]);
    }

    // Mark redemption as used
    public function useRedemption(Request $request, OfferRedemption $redemption)
    {
        if ($redemption->status === 'used') {
            return response()->json(['message' => 'Redemption already used'], 400);
        }

        if ($redemption->status === 'expired' || $redemption->expires_at < now()) {
            return response()->json(['message' => 'Redemption expired'], 400);
        }

        $redemption->update([
            'status' => 'used',
            'used_at' => now(),
        ]);

        return response()->json(['message' => 'Redemption marked as used']);
    }

    // Get user's redemption history
    public function getUserRedemptions(Request $request)
    {
        $user = $request->user();
        
        $redemptions = OfferRedemption::where('user_id', $user->id)
            ->with(['partnerOffer.partnerBusiness'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($redemptions);
    }

    // Admin methods
    public function adminGetPartners()
    {
        $partners = PartnerBusiness::with('offers')->orderBy('name')->get();
        return response()->json($partners);
    }

    public function adminCreatePartner(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'logo_url' => 'nullable|string',
            'address' => 'nullable|string',
            'contact_phone' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Map phone to contact_phone for compatibility
        if (isset($request->phone)) {
            $validated['contact_phone'] = $request->phone;
            unset($validated['phone']);
        }

        $partner = PartnerBusiness::create($validated);
        return response()->json($partner, 201);
    }

    public function adminUpdatePartner(Request $request, PartnerBusiness $partner)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'logo_url' => 'nullable|string',
            'address' => 'nullable|string',
            'contact_phone' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Map phone to contact_phone for compatibility
        if (isset($request->phone)) {
            $validated['contact_phone'] = $request->phone;
            unset($validated['phone']);
        }

        $partner->update($validated);
        return response()->json($partner);
    }

    public function adminDeletePartner(PartnerBusiness $partner)
    {
        $partner->delete();
        return response()->json(['message' => 'Partner deleted successfully']);
    }

    public function adminGetOffers()
    {
        $offers = PartnerOffer::with('partnerBusiness')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json($offers);
    }

    public function adminCreateOffer(Request $request)
    {
        $validated = $request->validate([
            'partner_business_id' => 'required|exists:partner_businesses,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'discount_percentage' => 'required|integer|min:0|max:100',
            'promo_code' => 'required|string|unique:partner_offers',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after:valid_from',
            'usage_limit' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        // Add required fields that might be missing
        $validated['type'] = 'percentage';
        $validated['discount_value'] = $validated['discount_percentage'];
        $validated['total_usage_limit'] = $validated['usage_limit'] ?? 0;
        $validated['current_usage_count'] = 0;
        $validated['usage_limit_per_user'] = $validated['usage_limit'] ?? 1;
        
        // Ensure usage_limit is never null (set to 0 if not provided)
        $validated['usage_limit'] = $validated['usage_limit'] ?? 0;

        $offer = PartnerOffer::create($validated);
        return response()->json($offer, 201);
    }

    public function adminUpdateOffer(Request $request, PartnerOffer $offer)
    {
        $validated = $request->validate([
            'partner_business_id' => 'sometimes|exists:partner_businesses,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'discount_percentage' => 'sometimes|integer|min:0|max:100',
            'promo_code' => 'sometimes|string|unique:partner_offers,promo_code,' . $offer->id,
            'valid_from' => 'sometimes|date',
            'valid_until' => 'sometimes|date|after:valid_from',
            'usage_limit' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        // Update related fields when discount_percentage changes
        if (isset($validated['discount_percentage'])) {
            $validated['discount_value'] = $validated['discount_percentage'];
        }
        
        if (isset($validated['usage_limit'])) {
            $validated['total_usage_limit'] = $validated['usage_limit'];
            $validated['usage_limit_per_user'] = $validated['usage_limit'] ?? 1;
        }

        $offer->update($validated);
        return response()->json($offer);
    }

    public function adminDeleteOffer(PartnerOffer $offer)
    {
        $offer->delete();
        return response()->json(['message' => 'Offer deleted successfully']);
    }

    public function adminGetRedemptions()
    {
        $redemptions = OfferRedemption::with(['user', 'partnerOffer.partnerBusiness'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($redemptions);
    }
}
