<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReferralController extends Controller
{
    /**
     * Validate a referral code or member name
     */
    public function validateReferral(Request $request)
    {
        $request->validate([
            'referral_type' => 'required|in:member,code',
            'referral_value' => 'required|string'
        ]);

        $referralType = $request->referral_type;
        $referralValue = trim($request->referral_value);

        if ($referralType === 'member') {
            // Use the service to find the referrer
            $member = ReferralService::findReferrer($referralValue);

            if ($member) {
                return response()->json([
                    'valid' => true,
                    'referrer_id' => $member->id,
                    'referrer_name' => $member->name,
                    'message' => "Referral validated: {$member->name}"
                ]);
            }

            // If no exact match, try to find potential matches
            $potentialMatches = ReferralService::findPotentialReferrers($referralValue);
            
            if ($potentialMatches->isNotEmpty()) {
                return response()->json([
                    'valid' => false,
                    'potential_matches' => $potentialMatches,
                    'message' => 'No exact match found. Did you mean one of these members?'
                ], 404);
            }

            return response()->json([
                'valid' => false,
                'message' => 'Member not found. Please check the name or email.'
            ], 404);
        }

        // For referral codes (future implementation)
        // This could be expanded to check special promotional codes
        $validCodes = [
            'SWEAT2024' => 'Special 2024 Promotion',
            'SUMMER24' => 'Summer 2024 Campaign',
            'WELCOME' => 'Welcome Offer'
        ];

        if (isset($validCodes[strtoupper($referralValue)])) {
            return response()->json([
                'valid' => true,
                'code' => strtoupper($referralValue),
                'description' => $validCodes[strtoupper($referralValue)],
                'message' => 'Valid promotional code'
            ]);
        }

        return response()->json([
            'valid' => false,
            'message' => 'Invalid referral code'
        ], 404);
    }

    /**
     * Get referral statistics for the authenticated user
     */
    public function myReferrals(Request $request)
    {
        $user = $request->user();
        
        $referrals = $user->referredUsers()
            ->select('id', 'name', 'email', 'created_at', 'status', 'referral_validated')
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = $user->getReferralStats();

        return response()->json([
            'stats' => $stats,
            'referrals' => $referrals,
            'referral_link' => url('/register?ref=' . $user->id),
            'referral_code' => ReferralService::generateReferralCode($user->id)
        ]);
    }

    /**
     * Get top referrers (admin only)
     */
    public function topReferrers(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $topReferrers = User::withCount(['referredUsers' => function ($query) {
                $query->where('referral_validated', true);
            }])
            ->having('referred_users_count', '>', 0)
            ->orderBy('referred_users_count', 'desc')
            ->limit(10)
            ->get(['id', 'name', 'email']);

        return response()->json([
            'top_referrers' => $topReferrers
        ]);
    }

    /**
     * Get referral source statistics (admin only)
     */
    public function sourceStatistics(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $stats = User::select('found_us_via', DB::raw('count(*) as total'))
            ->whereNotNull('found_us_via')
            ->groupBy('found_us_via')
            ->orderBy('total', 'desc')
            ->get()
            ->map(function ($item) {
                $user = new User(['found_us_via' => $item->found_us_via]);
                return [
                    'source' => $item->found_us_via,
                    'display_name' => $user->how_found_us_display,
                    'total' => $item->total
                ];
            });

        $socialStats = User::select('social_platform', DB::raw('count(*) as total'))
            ->whereNotNull('social_platform')
            ->groupBy('social_platform')
            ->orderBy('total', 'desc')
            ->get();

        return response()->json([
            'source_statistics' => $stats,
            'social_platforms' => $socialStats,
            'total_referred' => User::whereNotNull('referrer_id')->count(),
            'total_validated' => User::where('referral_validated', true)->count()
        ]);
    }
}