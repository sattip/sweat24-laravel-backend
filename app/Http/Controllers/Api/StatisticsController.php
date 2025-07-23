<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StatisticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StatisticsController extends Controller
{
    protected $statisticsService;

    public function __construct(StatisticsService $statisticsService)
    {
        $this->statisticsService = $statisticsService;
    }

    /**
     * Get dashboard statistics overview
     */
    public function dashboard(Request $request)
    {
        // Μόνο για admins
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $period = $request->get('period', '30_days');
        $stats = $this->statisticsService->getDashboardStatistics($period);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get booking type statistics
     */
    public function bookingTypes(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $stats = $this->statisticsService->getBookingTypeStatistics($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get monthly booking trends
     */
    public function monthlyTrends(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $months = $request->get('months', 12);
        $trends = $this->statisticsService->getMonthlyBookingTrends($months);

        return response()->json([
            'success' => true,
            'data' => $trends,
        ]);
    }

    /**
     * Get loyalty program statistics
     */
    public function loyaltyProgram()
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $stats = $this->statisticsService->getLoyaltyProgramStatistics();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get referral program statistics
     */
    public function referralProgram()
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $stats = $this->statisticsService->getReferralProgramStatistics();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Export statistics data
     */
    public function export(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $type = $request->get('type', 'dashboard');
        $period = $request->get('period', '30_days');

        $data = match($type) {
            'booking_types' => $this->statisticsService->getBookingTypeStatistics(
                $request->get('start_date'),
                $request->get('end_date')
            ),
            'monthly_trends' => $this->statisticsService->getMonthlyBookingTrends(
                $request->get('months', 12)
            ),
            'loyalty' => $this->statisticsService->getLoyaltyProgramStatistics(),
            'referrals' => $this->statisticsService->getReferralProgramStatistics(),
            default => $this->statisticsService->getDashboardStatistics($period),
        };

        return response()->json([
            'success' => true,
            'data' => $data,
            'export_info' => [
                'type' => $type,
                'period' => $period,
                'generated_at' => now()->toISOString(),
                'generated_by' => Auth::user()->name,
            ],
        ]);
    }
}
