<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltyRedemption;
use App\Models\ReferralReward;
use Illuminate\Support\Facades\DB;

class StatisticsService
{
    /**
     * Στατιστικά κρατήσεων ανά τύπο προπόνησης
     */
    public function getBookingTypeStatistics($startDate = null, $endDate = null)
    {
        $query = Booking::query();

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query->selectRaw('
                booking_type,
                COUNT(*) as total_bookings,
                COUNT(CASE WHEN status = "confirmed" THEN 1 END) as confirmed_bookings,
                COUNT(CASE WHEN status = "cancelled" THEN 1 END) as cancelled_bookings,
                COUNT(CASE WHEN attended = 1 THEN 1 END) as attended_bookings,
                ROUND(AVG(CASE WHEN attended = 1 THEN 1 ELSE 0 END) * 100, 2) as attendance_rate
            ')
            ->groupBy('booking_type')
            ->orderBy('total_bookings', 'desc')
            ->get()
            ->map(function($stat) {
                return [
                    'booking_type' => $stat->booking_type,
                    'display_name' => $this->getBookingTypeDisplayName($stat->booking_type),
                    'total_bookings' => $stat->total_bookings,
                    'confirmed_bookings' => $stat->confirmed_bookings,
                    'cancelled_bookings' => $stat->cancelled_bookings,
                    'attended_bookings' => $stat->attended_bookings,
                    'attendance_rate' => $stat->attendance_rate,
                ];
            });
    }

    /**
     * Μηνιαία στατιστικά κρατήσεων ανά τύπο
     */
    public function getMonthlyBookingTrends($months = 12)
    {
        $results = Booking::selectRaw('
                DATE_FORMAT(created_at, "%Y-%m") as month,
                booking_type,
                COUNT(*) as total_bookings,
                COUNT(CASE WHEN attended = 1 THEN 1 END) as attended_bookings
            ')
            ->where('created_at', '>=', now()->subMonths($months))
            ->groupBy(['month', 'booking_type'])
            ->orderBy('month', 'desc')
            ->get();

        return $results->groupBy('month')->map(function($monthData, $month) {
            $types = $monthData->groupBy('booking_type');
            $monthTotal = $monthData->sum('total_bookings');
            $monthAttended = $monthData->sum('attended_bookings');

            return [
                'month' => $month,
                'total_bookings' => $monthTotal,
                'attended_bookings' => $monthAttended,
                'attendance_rate' => $monthTotal > 0 ? round(($monthAttended / $monthTotal) * 100, 2) : 0,
                'by_type' => $types->map(function($typeData) {
                    $first = $typeData->first();
                    return [
                        'booking_type' => $first->booking_type,
                        'display_name' => $this->getBookingTypeDisplayName($first->booking_type),
                        'total_bookings' => $typeData->sum('total_bookings'),
                        'attended_bookings' => $typeData->sum('attended_bookings'),
                    ];
                })->values(),
            ];
        });
    }

    /**
     * Στατιστικά loyalty προγράμματος
     */
    public function getLoyaltyProgramStatistics()
    {
        return [
            'total_points_issued' => LoyaltyPoint::where('type', 'earned')->sum('amount'),
            'total_points_redeemed' => abs(LoyaltyPoint::where('type', 'redeemed')->sum('amount')),
            'active_points' => LoyaltyPoint::where(function($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->where('type', 'earned')->sum('amount'),
            'expiring_points_30_days' => LoyaltyPoint::where('expires_at', '<=', now()->addDays(30))
                                                    ->where('expires_at', '>', now())
                                                    ->where('type', 'earned')
                                                    ->sum('amount'),
            'users_with_points' => User::whereHas('loyaltyPoints', function($q) {
                $q->where('type', 'earned')
                  ->where(function($query) {
                      $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                  });
            })->count(),
            'total_redemptions' => LoyaltyRedemption::count(),
            'pending_redemptions' => LoyaltyRedemption::where('status', 'pending')->count(),
            'redemptions_by_status' => LoyaltyRedemption::selectRaw('status, COUNT(*) as count')
                                                        ->groupBy('status')
                                                        ->pluck('count', 'status'),
        ];
    }

    /**
     * Στατιστικά referral προγράμματος
     */
    public function getReferralProgramStatistics()
    {
        return [
            'total_referrals' => \App\Models\Referral::where('status', 'confirmed')->count(),
            'total_rewards_earned' => ReferralReward::count(),
            'rewards_by_status' => ReferralReward::selectRaw('status, COUNT(*) as count')
                                                 ->groupBy('status')
                                                 ->pluck('count', 'status'),
            'rewards_by_type' => ReferralReward::selectRaw('type, COUNT(*) as count')
                                              ->groupBy('type')
                                              ->pluck('count', 'type'),
            'top_referrers' => \App\Models\User::withCount(['referralsMade as total_referrals' => function($query) {
                                   $query->where('status', 'confirmed');
                               }])
                               ->having('total_referrals', '>', 0)
                               ->orderBy('total_referrals', 'desc')
                               ->limit(10)
                               ->get(['id', 'name', 'email']),
        ];
    }

    /**
     * Συνολικό dashboard στατιστικών
     */
    public function getDashboardStatistics($period = '30_days')
    {
        $startDate = match($period) {
            '7_days' => now()->subDays(7),
            '30_days' => now()->subDays(30),
            '90_days' => now()->subDays(90),
            '1_year' => now()->subYear(),
            default => now()->subDays(30),
        };

        return [
            'period' => $period,
            'period_start' => $startDate->toDateString(),
            'period_end' => now()->toDateString(),
            'bookings' => $this->getBookingTypeStatistics($startDate, now()),
            'loyalty' => $this->getLoyaltyProgramStatistics(),
            'referrals' => $this->getReferralProgramStatistics(),
            'revenue_impact' => $this->getRevenueImpactStatistics($startDate),
        ];
    }

    /**
     * Στατιστικά επίδρασης στα έσοδα
     */
    private function getRevenueImpactStatistics($startDate)
    {
        return [
            'loyalty_points_cost' => LoyaltyPoint::where('type', 'redeemed')
                                                ->where('created_at', '>=', $startDate)
                                                ->sum('amount'), // Κόστος σε πόντους = ευρώ
            'referral_discounts_given' => ReferralReward::where('type', 'discount')
                                                       ->where('status', 'redeemed')
                                                       ->where('redeemed_at', '>=', $startDate)
                                                       ->sum('value'),
            'new_customers_from_referrals' => \App\Models\Referral::where('status', 'confirmed')
                                                                 ->where('joined_at', '>=', $startDate)
                                                                 ->count(),
        ];
    }

    /**
     * Μετατροπή booking type σε εμφανίσιμο όνομα
     */
    private function getBookingTypeDisplayName($bookingType)
    {
        return match($bookingType) {
            'regular' => 'Κανονική',
            'trial' => 'Δοκιμαστική',
            'loyalty_gift' => 'Δώρο Ανταμοιβής',
            'referral_gift' => 'Δώρο Συστάσεων',
            'free' => 'Δωρεάν',
            'promotional' => 'Προσφορά',
            default => ucfirst($bookingType),
        };
    }
} 