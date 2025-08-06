<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReferralAdminController extends Controller
{
    public function index()
    {
        // Get overall statistics
        $totalMembers = User::where('role', 'member')->count();
        $totalReferrals = User::whereNotNull('referrer_id')->count();
        $validatedReferrals = User::where('referral_validated', true)->count();
        $socialMediaRegistrations = User::whereIn('found_us_via', ['facebook', 'instagram'])->count();
        
        $conversionRate = $totalMembers > 0 
            ? round(($validatedReferrals / $totalMembers) * 100, 1) 
            : 0;

        // Get source statistics
        $sourceStats = User::select('found_us_via', DB::raw('count(*) as total'))
            ->whereNotNull('found_us_via')
            ->groupBy('found_us_via')
            ->orderBy('total', 'desc')
            ->get()
            ->map(function ($item) {
                $user = new User(['found_us_via' => $item->found_us_via]);
                $item->display_name = $user->how_found_us_display;
                return $item;
            });

        // Get top referrers
        $topReferrers = User::withCount([
                'referredUsers',
                'referredUsers as validated_referrals_count' => function ($query) {
                    $query->where('referral_validated', true);
                }
            ])
            ->having('referred_users_count', '>', 0)
            ->orderBy('referred_users_count', 'desc')
            ->limit(5)
            ->get();

        // Get recent registrations with referral info
        $recentRegistrations = User::with('referrer')
            ->where('role', 'member')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('admin.referrals.index', compact(
            'totalMembers',
            'totalReferrals',
            'validatedReferrals',
            'socialMediaRegistrations',
            'conversionRate',
            'sourceStats',
            'topReferrers',
            'recentRegistrations'
        ));
    }

    public function exportReferralData(Request $request)
    {
        $query = User::with('referrer')
            ->where('role', 'member');

        // Apply filters
        if ($request->has('source')) {
            $query->where('found_us_via', $request->source);
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="referral_data_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function() use ($query) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($file, ["Name", "Email", "Registration Date", "Source", "Social Platform", "Referrer", "Referral Validated", "Status"]);

            // Process users in chunks to keep memory usage low
            $query->chunk(500, function ($users) use ($file) {
                foreach ($users as $user) {
                    fputcsv($file, [
                        $user->name,
                        $user->email,
                        $user->created_at->format('Y-m-d'),
                        $user->found_us_via ?? 'Not specified',
                        $user->social_platform ?? '-',
                        $user->referrer ? $user->referrer->name : ($user->referral_code_or_name ?? '-'),
                        $user->referral_validated ? 'Yes' : 'No',
                        $user->status
                    ]);
                }
            });
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}