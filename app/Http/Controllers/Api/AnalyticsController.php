<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Booking;
use App\Models\GymClass;
use App\Models\UserPackage;
use App\Models\ClassEvaluation;
use App\Models\TrialAppointment;
use App\Models\ChurnFeedback;
use App\Models\Service;
use App\Models\Store;
use App\Models\Instructor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    /**
     * 1. Marketing Analytics - Where did customers find us
     */
    public function marketingSource(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->subMonths(6)->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $storeId = $request->input('store_id');

        $query = User::whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->whereNotNull('found_us_via');

        // Filter by store if provided (via bookings)
        if ($storeId) {
            $userIds = Booking::where('store_id', $storeId)->pluck('user_id')->unique();
            $query->whereIn('id', $userIds);
        }

        $sources = $query->groupBy('found_us_via')
            ->selectRaw('found_us_via, COUNT(*) as count')
            ->orderByDesc('count')
            ->get();

        $total = $sources->sum('count');

        $sourceLabels = [
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'google' => 'Google',
            'friend' => 'Σύσταση από φίλο',
            'member' => 'Σύσταση από μέλος',
            'website' => 'Website',
            'walk_in' => 'Επίσκεψη στο γυμναστήριο',
            'flyer' => 'Φυλλάδιο',
            'event' => 'Εκδήλωση',
            'other' => 'Άλλο',
        ];

        $data = $sources->map(function ($item) use ($total, $sourceLabels) {
            return [
                'source' => $item->found_us_via,
                'label' => $sourceLabels[$item->found_us_via] ?? $item->found_us_via,
                'count' => $item->count,
                'percentage' => $total > 0 ? round(($item->count / $total) * 100, 1) : 0,
            ];
        });

        // Monthly trend
        $monthlyTrend = User::whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->whereNotNull('found_us_via')
            ->selectRaw("strftime('%Y-%m', created_at) as month, found_us_via, COUNT(*) as count")
            ->groupBy('month', 'found_us_via')
            ->orderBy('month')
            ->get()
            ->groupBy('month');

        return response()->json([
            'success' => true,
            'data' => $data,
            'total' => $total,
            'monthly_trend' => $monthlyTrend,
            'period' => ['start' => $startDate, 'end' => $endDate],
        ]);
    }

    /**
     * 2. Attendance Statistics - by service, day, week, month
     */
    public function attendanceStats(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->subMonths(1)->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $storeId = $request->input('store_id');
        $serviceId = $request->input('service_id');
        $groupBy = $request->input('group_by', 'day'); // day, week, month

        $query = Booking::whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->whereIn('status', ['confirmed', 'completed']);

        if ($storeId) {
            $query->where('store_id', $storeId);
        }
        if ($serviceId) {
            $query->where('service_id', $serviceId);
        }

        // Get attendance rates
        $totalBookings = (clone $query)->count();
        $attended = (clone $query)->where('attended', true)->count();
        $notAttended = (clone $query)->where('attended', false)->count();
        $cancelled = Booking::whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->where('status', 'cancelled')
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->when($serviceId, fn($q) => $q->where('service_id', $serviceId))
            ->count();

        // Group by time period
        $dateFormat = match ($groupBy) {
            'week' => "strftime('%Y-%W', date)",
            'month' => "strftime('%Y-%m', date)",
            default => "strftime('%Y-%m-%d', date)",
        };

        $timeline = Booking::whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->whereIn('status', ['confirmed', 'completed', 'cancelled'])
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->when($serviceId, fn($q) => $q->where('service_id', $serviceId))
            ->selectRaw("
                {$dateFormat} as period,
                COUNT(*) as total,
                SUM(CASE WHEN attended = 1 THEN 1 ELSE 0 END) as attended,
                SUM(CASE WHEN attended = 0 AND status != 'cancelled' THEN 1 ELSE 0 END) as no_show,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
            ")
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        // By service breakdown
        $byService = Booking::whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->whereIn('status', ['confirmed', 'completed'])
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->with('service:id,name')
            ->selectRaw("
                service_id,
                COUNT(*) as total,
                SUM(CASE WHEN attended = 1 THEN 1 ELSE 0 END) as attended
            ")
            ->groupBy('service_id')
            ->get()
            ->map(function ($item) {
                return [
                    'service_id' => $item->service_id,
                    'service_name' => $item->service?->name ?? 'Άγνωστη υπηρεσία',
                    'total' => $item->total,
                    'attended' => $item->attended,
                    'attendance_rate' => $item->total > 0 ? round(($item->attended / $item->total) * 100, 1) : 0,
                ];
            });

        return response()->json([
            'success' => true,
            'summary' => [
                'total_bookings' => $totalBookings,
                'attended' => $attended,
                'not_attended' => $notAttended,
                'cancelled' => $cancelled,
                'attendance_rate' => $totalBookings > 0 ? round(($attended / $totalBookings) * 100, 1) : 0,
            ],
            'timeline' => $timeline,
            'by_service' => $byService,
            'period' => ['start' => $startDate, 'end' => $endDate],
        ]);
    }

    /**
     * 3. Group Class Capacity - Occupancy rates, Pilates vs Regular
     */
    public function classCapacity(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->subMonths(1)->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $storeId = $request->input('store_id');
        $classType = $request->input('class_type'); // pilates, group, etc.
        $timeSlotStart = $request->input('time_start'); // e.g., "09:00"
        $timeSlotEnd = $request->input('time_end'); // e.g., "12:00"
        $groupBy = $request->input('group_by', 'week'); // week, month, year

        $query = GymClass::whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->where('status', '!=', 'cancelled');

        if ($classType) {
            $query->where('type', $classType);
        }
        if ($timeSlotStart) {
            $query->whereTime('time', '>=', $timeSlotStart);
        }
        if ($timeSlotEnd) {
            $query->whereTime('time', '<=', $timeSlotEnd);
        }

        // Overall occupancy
        $classes = $query->get();
        $totalCapacity = $classes->sum('max_participants');
        $totalBooked = $classes->sum('current_participants');
        $overallOccupancy = $totalCapacity > 0 ? round(($totalBooked / $totalCapacity) * 100, 1) : 0;

        // By class type (Pilates vs Regular)
        $byType = GymClass::whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->where('status', '!=', 'cancelled')
            ->selectRaw("
                type,
                COUNT(*) as total_classes,
                SUM(max_participants) as total_capacity,
                SUM(current_participants) as total_booked
            ")
            ->groupBy('type')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->type,
                    'total_classes' => $item->total_classes,
                    'total_capacity' => $item->total_capacity,
                    'total_booked' => $item->total_booked,
                    'occupancy_rate' => $item->total_capacity > 0
                        ? round(($item->total_booked / $item->total_capacity) * 100, 1) : 0,
                    'avg_participants' => $item->total_classes > 0
                        ? round($item->total_booked / $item->total_classes, 1) : 0,
                ];
            });

        // By time slot
        $byTimeSlot = GymClass::whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->where('status', '!=', 'cancelled')
            ->selectRaw("
                CASE
                    WHEN time(time) < '10:00' THEN 'Πρωί (< 10:00)'
                    WHEN time(time) < '14:00' THEN 'Μεσημέρι (10:00-14:00)'
                    WHEN time(time) < '18:00' THEN 'Απόγευμα (14:00-18:00)'
                    ELSE 'Βράδυ (> 18:00)'
                END as time_slot,
                COUNT(*) as total_classes,
                SUM(max_participants) as total_capacity,
                SUM(current_participants) as total_booked
            ")
            ->groupBy('time_slot')
            ->get();

        // Cancellation with charge rate
        $totalCancellations = Booking::whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->where('status', 'cancelled')
            ->count();
        $cancellationsWithCharge = Booking::whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->where('status', 'cancelled')
            ->where('absence_with_charge', true)
            ->count();

        // Timeline by group_by
        $dateFormat = match ($groupBy) {
            'month' => "strftime('%Y-%m', date)",
            'year' => "strftime('%Y', date)",
            default => "strftime('%Y-%W', date)",
        };

        $timeline = GymClass::whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->where('status', '!=', 'cancelled')
            ->when($classType, fn($q) => $q->where('type', $classType))
            ->selectRaw("
                {$dateFormat} as period,
                COUNT(*) as total_classes,
                SUM(max_participants) as total_capacity,
                SUM(current_participants) as total_booked
            ")
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return response()->json([
            'success' => true,
            'summary' => [
                'total_classes' => $classes->count(),
                'total_capacity' => $totalCapacity,
                'total_booked' => $totalBooked,
                'overall_occupancy' => $overallOccupancy,
            ],
            'by_type' => $byType,
            'by_time_slot' => $byTimeSlot,
            'cancellations' => [
                'total' => $totalCancellations,
                'with_charge' => $cancellationsWithCharge,
                'charge_rate' => $totalCancellations > 0
                    ? round(($cancellationsWithCharge / $totalCancellations) * 100, 1) : 0,
            ],
            'timeline' => $timeline,
            'period' => ['start' => $startDate, 'end' => $endDate],
        ]);
    }

    /**
     * 4. Package Usage Statistics
     */
    public function packageUsage(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->subMonths(3)->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $storeId = $request->input('store_id');
        $packageType = $request->input('package_type'); // personal, group

        $query = UserPackage::whereDate('assigned_date', '>=', $startDate)
            ->whereDate('assigned_date', '<=', $endDate);

        // Get usage statistics
        $packages = $query->with('package:id,name,sessions,class_type')->get();

        $totalPackages = $packages->count();
        $totalSessions = $packages->sum('total_sessions');
        $usedSessions = $packages->sum(function ($pkg) {
            return $pkg->total_sessions - $pkg->remaining_sessions;
        });
        $avgUsageRate = $totalSessions > 0 ? round(($usedSessions / $totalSessions) * 100, 1) : 0;

        // By package type (using class_type field)
        $byType = $packages->groupBy(fn($pkg) => $pkg->package?->class_type ?? 'Γενικό')
            ->map(function ($group, $type) {
                $total = $group->sum('total_sessions');
                $used = $group->sum(fn($pkg) => $pkg->total_sessions - $pkg->remaining_sessions);
                return [
                    'type' => $type,
                    'package_count' => $group->count(),
                    'total_sessions' => $total,
                    'used_sessions' => $used,
                    'usage_rate' => $total > 0 ? round(($used / $total) * 100, 1) : 0,
                ];
            })
            ->values();

        // Fully used vs partially used
        $fullyUsed = $packages->where('remaining_sessions', 0)->count();
        $partiallyUsed = $packages->where('remaining_sessions', '>', 0)
            ->where('remaining_sessions', '<', fn($q) => $q->sum('total_sessions'))
            ->count();
        $notUsed = $packages->filter(fn($pkg) =>
            $pkg->total_sessions == $pkg->remaining_sessions
        )->count();

        return response()->json([
            'success' => true,
            'summary' => [
                'total_packages' => $totalPackages,
                'total_sessions' => $totalSessions,
                'used_sessions' => $usedSessions,
                'avg_usage_rate' => $avgUsageRate,
            ],
            'usage_breakdown' => [
                'fully_used' => $fullyUsed,
                'partially_used' => $partiallyUsed,
                'not_used' => $notUsed,
            ],
            'by_type' => $byType,
            'period' => ['start' => $startDate, 'end' => $endDate],
        ]);
    }

    /**
     * 4b. Demographics - Gender & Age Statistics
     */
    public function demographics(Request $request): JsonResponse
    {
        $storeId = $request->input('store_id');
        $ageMin = $request->input('age_min', 8);
        $ageMax = $request->input('age_max', 90);

        $query = User::where('role', 'member');

        if ($storeId) {
            $userIds = Booking::where('store_id', $storeId)->pluck('user_id')->unique();
            $query->whereIn('id', $userIds);
        }

        // Gender distribution
        $genderStats = (clone $query)
            ->whereNotNull('gender')
            ->groupBy('gender')
            ->selectRaw('gender, COUNT(*) as count')
            ->get();

        $totalWithGender = $genderStats->sum('count');
        $genderData = $genderStats->map(function ($item) use ($totalWithGender) {
            $labels = [
                'male' => 'Άνδρες',
                'female' => 'Γυναίκες',
                'other' => 'Άλλο',
                'prefer_not_to_say' => 'Δεν απαντώ',
            ];
            return [
                'gender' => $item->gender,
                'label' => $labels[$item->gender] ?? $item->gender,
                'count' => $item->count,
                'percentage' => $totalWithGender > 0 ? round(($item->count / $totalWithGender) * 100, 1) : 0,
            ];
        });

        // Age distribution
        $ageGroups = [
            ['min' => $ageMin, 'max' => 17, 'label' => "{$ageMin}-17"],
            ['min' => 18, 'max' => 25, 'label' => '18-25'],
            ['min' => 26, 'max' => 35, 'label' => '26-35'],
            ['min' => 36, 'max' => 45, 'label' => '36-45'],
            ['min' => 46, 'max' => 55, 'label' => '46-55'],
            ['min' => 56, 'max' => 65, 'label' => '56-65'],
            ['min' => 66, 'max' => $ageMax, 'label' => "66-{$ageMax}"],
        ];

        $ageData = [];
        foreach ($ageGroups as $group) {
            $count = (clone $query)
                ->whereNotNull('date_of_birth')
                ->whereRaw("(strftime('%Y', 'now') - strftime('%Y', date_of_birth)) BETWEEN ? AND ?",
                    [$group['min'], $group['max']])
                ->count();
            $ageData[] = [
                'range' => $group['label'],
                'min' => $group['min'],
                'max' => $group['max'],
                'count' => $count,
            ];
        }

        $totalWithAge = array_sum(array_column($ageData, 'count'));
        $ageData = array_map(function ($item) use ($totalWithAge) {
            $item['percentage'] = $totalWithAge > 0 ? round(($item['count'] / $totalWithAge) * 100, 1) : 0;
            return $item;
        }, $ageData);

        return response()->json([
            'success' => true,
            'gender' => [
                'data' => $genderData,
                'total' => $totalWithGender,
            ],
            'age' => [
                'data' => $ageData,
                'total' => $totalWithAge,
                'filters' => ['min' => $ageMin, 'max' => $ageMax],
            ],
        ]);
    }

    /**
     * 5. Service Type Distribution - EMS, Pilates, Personal, Group, Functional
     */
    public function serviceDistribution(Request $request): JsonResponse
    {
        $storeId = $request->input('store_id');

        // Active users per service type
        $serviceTypes = Service::where('is_active', true)->get();

        $distribution = [];
        foreach ($serviceTypes as $service) {
            $userCount = UserPackage::whereHas('package', function ($q) use ($service) {
                    $q->where('service_id', $service->id);
                })
                ->where('status', 'active')
                ->distinct('user_id')
                ->count('user_id');

            $distribution[] = [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'active_users' => $userCount,
            ];
        }

        // Sort by user count
        usort($distribution, fn($a, $b) => $b['active_users'] - $a['active_users']);

        $totalActiveUsers = array_sum(array_column($distribution, 'active_users'));
        $distribution = array_map(function ($item) use ($totalActiveUsers) {
            $item['percentage'] = $totalActiveUsers > 0
                ? round(($item['active_users'] / $totalActiveUsers) * 100, 1) : 0;
            return $item;
        }, $distribution);

        // Users combining services
        $usersWithMultipleServices = UserPackage::where('status', 'active')
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(DISTINCT package_id) > 1')
            ->get()
            ->count();

        return response()->json([
            'success' => true,
            'distribution' => $distribution,
            'total_active_users' => $totalActiveUsers,
            'users_combining_services' => $usersWithMultipleServices,
        ]);
    }

    /**
     * 5b. Trainer Ratings Analytics
     */
    public function trainerRatings(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->subMonths(3)->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $instructorId = $request->input('instructor_id');
        $serviceId = $request->input('service_id');

        $query = ClassEvaluation::where('is_submitted', true)
            ->whereDate('submitted_at', '>=', $startDate)
            ->whereDate('submitted_at', '<=', $endDate);

        // Overall averages
        $overallStats = (clone $query)->selectRaw("
            AVG(overall_rating) as avg_overall,
            AVG(instructor_rating) as avg_instructor,
            AVG(facility_rating) as avg_facility,
            COUNT(*) as total_reviews,
            SUM(CASE WHEN would_recommend = 1 THEN 1 ELSE 0 END) as would_recommend_count
        ")->first();

        // By instructor
        $byInstructor = ClassEvaluation::where('is_submitted', true)
            ->whereDate('submitted_at', '>=', $startDate)
            ->whereDate('submitted_at', '<=', $endDate)
            ->join('gym_classes', 'class_evaluations.gym_class_id', '=', 'gym_classes.id')
            ->join('instructors', 'gym_classes.instructor_id', '=', 'instructors.id')
            ->when($instructorId, fn($q) => $q->where('instructors.id', $instructorId))
            ->selectRaw("
                instructors.id as instructor_id,
                instructors.name as instructor_name,
                AVG(class_evaluations.overall_rating) as avg_overall,
                AVG(class_evaluations.instructor_rating) as avg_instructor,
                AVG(class_evaluations.facility_rating) as avg_facility,
                COUNT(*) as total_reviews
            ")
            ->groupBy('instructors.id', 'instructors.name')
            ->orderByDesc('avg_overall')
            ->get();

        // By service
        $byService = ClassEvaluation::where('is_submitted', true)
            ->whereDate('submitted_at', '>=', $startDate)
            ->whereDate('submitted_at', '<=', $endDate)
            ->join('gym_classes', 'class_evaluations.gym_class_id', '=', 'gym_classes.id')
            ->join('services', 'gym_classes.service_id', '=', 'services.id')
            ->when($serviceId, fn($q) => $q->where('services.id', $serviceId))
            ->selectRaw("
                services.id as service_id,
                services.name as service_name,
                AVG(class_evaluations.overall_rating) as avg_overall,
                AVG(class_evaluations.instructor_rating) as avg_instructor,
                AVG(class_evaluations.facility_rating) as avg_facility,
                COUNT(*) as total_reviews
            ")
            ->groupBy('services.id', 'services.name')
            ->orderByDesc('avg_overall')
            ->get();

        // Common feedback tags
        $allTags = ClassEvaluation::where('is_submitted', true)
            ->whereDate('submitted_at', '>=', $startDate)
            ->whereDate('submitted_at', '<=', $endDate)
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->countBy()
            ->sortDesc()
            ->take(10);

        return response()->json([
            'success' => true,
            'overall' => [
                'avg_overall' => round($overallStats->avg_overall ?? 0, 2),
                'avg_instructor' => round($overallStats->avg_instructor ?? 0, 2),
                'avg_facility' => round($overallStats->avg_facility ?? 0, 2),
                'total_reviews' => $overallStats->total_reviews ?? 0,
                'recommendation_rate' => $overallStats->total_reviews > 0
                    ? round(($overallStats->would_recommend_count / $overallStats->total_reviews) * 100, 1) : 0,
            ],
            'by_instructor' => $byInstructor,
            'by_service' => $byService,
            'common_tags' => $allTags,
            'period' => ['start' => $startDate, 'end' => $endDate],
        ]);
    }

    /**
     * 6. Churn & Retention Analytics (enhanced)
     */
    public function retentionAnalytics(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->subMonths(6)->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $storeId = $request->input('store_id');

        // Package expiry and renewal rates
        $expiredPackages = UserPackage::whereDate('expiry_date', '>=', $startDate)
            ->whereDate('expiry_date', '<=', $endDate)
            ->count();

        $renewedPackages = UserPackage::whereDate('renewed_at', '>=', $startDate)
            ->whereDate('renewed_at', '<=', $endDate)
            ->count();

        $renewalRate = $expiredPackages > 0 ? round(($renewedPackages / $expiredPackages) * 100, 1) : 0;

        // Churn from feedback
        $churnFeedback = ChurnFeedback::whereDate('expired_at', '>=', $startDate)
            ->whereDate('expired_at', '<=', $endDate)
            ->get();

        $totalFeedback = $churnFeedback->count();
        $churned = $churnFeedback->where('status', 'churn')->count();
        $paused = $churnFeedback->where('status', 'pause')->count();
        $renewed = $churnFeedback->where('status', 'renewed')->count();

        $churnRate = $totalFeedback > 0 ? round(($churned / $totalFeedback) * 100, 1) : 0;
        $retentionRate = 100 - $churnRate;

        // Monthly retention trend
        $monthlyTrend = UserPackage::whereDate('expiry_date', '>=', $startDate)
            ->whereDate('expiry_date', '<=', $endDate)
            ->selectRaw("
                strftime('%Y-%m', expiry_date) as month,
                COUNT(*) as expired,
                SUM(CASE WHEN renewed_at IS NOT NULL THEN 1 ELSE 0 END) as renewed
            ")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => $item->month,
                    'expired' => $item->expired,
                    'renewed' => $item->renewed,
                    'renewal_rate' => $item->expired > 0
                        ? round(($item->renewed / $item->expired) * 100, 1) : 0,
                ];
            });

        return response()->json([
            'success' => true,
            'summary' => [
                'expired_packages' => $expiredPackages,
                'renewed_packages' => $renewedPackages,
                'renewal_rate' => $renewalRate,
                'churn_rate' => $churnRate,
                'retention_rate' => $retentionRate,
            ],
            'feedback_breakdown' => [
                'total' => $totalFeedback,
                'churned' => $churned,
                'paused' => $paused,
                'renewed' => $renewed,
            ],
            'monthly_trend' => $monthlyTrend,
            'period' => ['start' => $startDate, 'end' => $endDate],
        ]);
    }

    /**
     * 7. Trial Conversion Analytics
     */
    public function trialConversion(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->subMonths(3)->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $storeId = $request->input('store_id');
        $serviceId = $request->input('service_id');

        $query = TrialAppointment::whereDate('appointment_date', '>=', $startDate)
            ->whereDate('appointment_date', '<=', $endDate);

        if ($serviceId) {
            $query->where('service_id', $serviceId);
        }

        $totalTrials = (clone $query)->count();
        $completedTrials = (clone $query)->where('status', 'completed')->count();
        $cancelledTrials = (clone $query)->where('status', 'cancelled')->count();

        // Get user IDs who had trials
        $trialUserIds = (clone $query)->where('status', 'completed')->pluck('user_id');

        // Count how many converted to packages
        $convertedUsers = UserPackage::whereIn('user_id', $trialUserIds)
            ->whereDate('assigned_date', '>=', $startDate)
            ->distinct('user_id')
            ->count('user_id');

        $conversionRate = $completedTrials > 0 ? round(($convertedUsers / $completedTrials) * 100, 1) : 0;

        // By service
        $byService = TrialAppointment::whereDate('appointment_date', '>=', $startDate)
            ->whereDate('appointment_date', '<=', $endDate)
            ->with('service:id,name')
            ->selectRaw("
                service_id,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
            ")
            ->groupBy('service_id')
            ->get()
            ->map(function ($item) {
                return [
                    'service_id' => $item->service_id,
                    'service_name' => $item->service?->name ?? 'Άγνωστη υπηρεσία',
                    'total' => $item->total,
                    'completed' => $item->completed,
                    'cancelled' => $item->cancelled,
                    'completion_rate' => $item->total > 0
                        ? round(($item->completed / $item->total) * 100, 1) : 0,
                ];
            });

        // Monthly trend
        $monthlyTrend = TrialAppointment::whereDate('appointment_date', '>=', $startDate)
            ->whereDate('appointment_date', '<=', $endDate)
            ->selectRaw("
                strftime('%Y-%m', appointment_date) as month,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
            ")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json([
            'success' => true,
            'summary' => [
                'total_trials' => $totalTrials,
                'completed' => $completedTrials,
                'cancelled' => $cancelledTrials,
                'converted_to_members' => $convertedUsers,
                'conversion_rate' => $conversionRate,
            ],
            'by_service' => $byService,
            'monthly_trend' => $monthlyTrend,
            'period' => ['start' => $startDate, 'end' => $endDate],
        ]);
    }

    /**
     * 8. Location-based Analytics - Lagonisi vs Vari
     */
    public function locationAnalytics(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->subMonths(3)->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        // Get stores
        $stores = Store::where('is_active', true)->get();

        $locationData = [];
        foreach ($stores as $store) {
            // Users who booked at this store
            $userIds = Booking::where('store_id', $store->id)
                ->whereDate('date', '>=', $startDate)
                ->whereDate('date', '<=', $endDate)
                ->pluck('user_id')
                ->unique();

            $userCount = $userIds->count();

            // Service distribution at this store
            $serviceDistribution = Booking::where('store_id', $store->id)
                ->whereDate('date', '>=', $startDate)
                ->whereDate('date', '<=', $endDate)
                ->with('service:id,name')
                ->selectRaw('service_id, COUNT(DISTINCT user_id) as user_count')
                ->groupBy('service_id')
                ->get()
                ->map(fn($item) => [
                    'service_id' => $item->service_id,
                    'service_name' => $item->service?->name ?? 'Άγνωστη',
                    'user_count' => $item->user_count,
                ]);

            $locationData[] = [
                'store_id' => $store->id,
                'store_name' => $store->name,
                'total_users' => $userCount,
                'services' => $serviceDistribution,
            ];
        }

        // Users who visited multiple stores
        $multiStoreUsers = DB::table('bookings')
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(DISTINCT store_id) > 1')
            ->count();

        // Total unique users
        $totalUsers = Booking::whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->distinct('user_id')
            ->count('user_id');

        return response()->json([
            'success' => true,
            'stores' => $locationData,
            'multi_store_users' => $multiStoreUsers,
            'total_unique_users' => $totalUsers,
            'multi_store_percentage' => $totalUsers > 0
                ? round(($multiStoreUsers / $totalUsers) * 100, 1) : 0,
            'period' => ['start' => $startDate, 'end' => $endDate],
        ]);
    }

    /**
     * Dashboard Summary - All key metrics
     */
    public function dashboard(Request $request): JsonResponse
    {
        $storeId = $request->input('store_id');
        $period = $request->input('period', '30'); // days

        $startDate = now()->subDays((int) $period)->toDateString();
        $endDate = now()->toDateString();

        // Quick stats
        $activeMembers = User::where('role', 'member')
            ->where('status', 'active')
            ->count();

        $newMembers = User::where('role', 'member')
            ->whereDate('created_at', '>=', $startDate)
            ->count();

        $totalBookings = Booking::whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->count();

        $attendanceRate = Booking::whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->whereIn('status', ['confirmed', 'completed'])
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN attended = 1 THEN 1 ELSE 0 END) as attended
            ')
            ->first();

        $avgOccupancy = GymClass::whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->where('status', '!=', 'cancelled')
            ->selectRaw('
                SUM(current_participants) as booked,
                SUM(max_participants) as capacity
            ')
            ->first();

        $avgRating = ClassEvaluation::where('is_submitted', true)
            ->whereDate('submitted_at', '>=', $startDate)
            ->avg('overall_rating');

        $trialConversions = TrialAppointment::whereDate('appointment_date', '>=', $startDate)
            ->whereDate('appointment_date', '<=', $endDate)
            ->where('status', 'completed')
            ->count();

        return response()->json([
            'success' => true,
            'dashboard' => [
                'active_members' => $activeMembers,
                'new_members' => $newMembers,
                'total_bookings' => $totalBookings,
                'attendance_rate' => $attendanceRate->total > 0
                    ? round(($attendanceRate->attended / $attendanceRate->total) * 100, 1) : 0,
                'avg_occupancy' => $avgOccupancy->capacity > 0
                    ? round(($avgOccupancy->booked / $avgOccupancy->capacity) * 100, 1) : 0,
                'avg_rating' => round($avgRating ?? 0, 2),
                'trial_conversions' => $trialConversions,
            ],
            'period' => ['start' => $startDate, 'end' => $endDate, 'days' => $period],
        ]);
    }
}
