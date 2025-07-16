<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityController extends Controller
{
    /**
     * Display the activity dashboard.
     */
    public function index(Request $request)
    {
        // Get filter parameters
        $activityType = $request->get('activity_type');
        $userId = $request->get('user_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $search = $request->get('search');

        // Build query
        $query = ActivityLog::with(['user', 'subject'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($activityType) {
            $query->ofType($activityType);
        }

        if ($userId) {
            $query->byUser($userId);
        }

        if ($dateFrom) {
            $query->where('created_at', '>=', Carbon::parse($dateFrom)->setTimezone(config('app.timezone'))->startOfDay());
        }

        if ($dateTo) {
            $query->where('created_at', '<=', Carbon::parse($dateTo)->setTimezone(config('app.timezone'))->endOfDay());
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Get paginated results
        $activities = $query->paginate(20)->withQueryString();

        // Get statistics
        $stats = $this->getActivityStats($dateFrom, $dateTo);

        // Get users for filter dropdown
        $users = User::orderBy('name')->get(['id', 'name', 'email']);

        // Get activity types for filter
        $activityTypes = [
            ActivityLog::TYPE_REGISTRATION => 'New Registration',
            ActivityLog::TYPE_LOGIN => 'User Login',
            ActivityLog::TYPE_LOGOUT => 'User Logout',
            ActivityLog::TYPE_BOOKING => 'Class Booking',
            ActivityLog::TYPE_BOOKING_CANCELLATION => 'Booking Cancellation',
            ActivityLog::TYPE_PAYMENT => 'Payment',
            ActivityLog::TYPE_PACKAGE_PURCHASE => 'Package Purchase',
            ActivityLog::TYPE_PACKAGE_EXPIRY => 'Package Expired',
            ActivityLog::TYPE_PACKAGE_RENEWAL => 'Package Renewal',
            ActivityLog::TYPE_PACKAGE_FREEZE => 'Package Frozen',
            ActivityLog::TYPE_PACKAGE_UNFREEZE => 'Package Unfrozen',
            ActivityLog::TYPE_CLASS_CREATED => 'Class Created',
            ActivityLog::TYPE_CLASS_UPDATED => 'Class Updated',
            ActivityLog::TYPE_CLASS_CANCELLED => 'Class Cancelled',
            ActivityLog::TYPE_USER_UPDATED => 'User Updated',
            ActivityLog::TYPE_EVALUATION_SUBMITTED => 'Evaluation Submitted',
        ];

        return view('admin.activity.index', compact(
            'activities',
            'stats',
            'users',
            'activityTypes',
            'activityType',
            'userId',
            'dateFrom',
            'dateTo',
            'search'
        ));
    }

    /**
     * Get real-time activities (for polling).
     */
    public function realtime(Request $request)
    {
        $lastId = $request->get('last_id', 0);

        $activities = ActivityLog::with(['user', 'subject'])
            ->where('id', '>', $lastId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'activities' => $activities->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'user' => [
                        'name' => $activity->user->name ?? 'System',
                        'email' => $activity->user->email ?? '',
                    ],
                    'activity_type' => $activity->activity_type,
                    'activity_type_label' => $activity->activity_type_label,
                    'activity_icon' => $activity->activity_icon,
                    'activity_color' => $activity->activity_color,
                    'action' => $activity->action,
                    'properties' => $activity->properties,
                    'created_at' => $activity->created_at->format('Y-m-d H:i:s'),
                    'created_at_human' => $activity->created_at->diffForHumans(),
                ];
            }),
            'last_id' => $activities->first()->id ?? $lastId,
        ]);
    }

    /**
     * Export activity logs.
     */
    public function export(Request $request)
    {
        // Get filter parameters
        $activityType = $request->get('activity_type');
        $userId = $request->get('user_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $format = $request->get('format', 'csv');

        // Build query
        $query = ActivityLog::with(['user', 'subject']);

        // Apply filters
        if ($activityType) {
            $query->ofType($activityType);
        }

        if ($userId) {
            $query->byUser($userId);
        }

        if ($dateFrom) {
            $query->where('created_at', '>=', Carbon::parse($dateFrom)->startOfDay());
        }

        if ($dateTo) {
            $query->where('created_at', '<=', Carbon::parse($dateTo)->endOfDay());
        }

        $activities = $query->orderBy('created_at', 'desc')->get();

        if ($format === 'csv') {
            return $this->exportCsv($activities);
        } else {
            return $this->exportJson($activities);
        }
    }

    /**
     * Get activity statistics.
     */
    private function getActivityStats($dateFrom = null, $dateTo = null)
    {
        $query = ActivityLog::query();

        if ($dateFrom) {
            $query->where('created_at', '>=', Carbon::parse($dateFrom)->startOfDay());
        }

        if ($dateTo) {
            $query->where('created_at', '<=', Carbon::parse($dateTo)->endOfDay());
        }

        // Get activity counts by type
        $activityCounts = $query->select('activity_type', DB::raw('count(*) as count'))
            ->groupBy('activity_type')
            ->pluck('count', 'activity_type')
            ->toArray();

        // Get today's stats
        $todayStats = ActivityLog::whereDate('created_at', Carbon::today())
            ->select('activity_type', DB::raw('count(*) as count'))
            ->groupBy('activity_type')
            ->pluck('count', 'activity_type')
            ->toArray();

        // Get hourly activity for the last 24 hours
        $hourlyActivity = ActivityLog::where('created_at', '>=', Carbon::now()->subHours(24))
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('count(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        // Fill missing hours with zeros
        $hourlyData = [];
        for ($i = 0; $i < 24; $i++) {
            $hourlyData[$i] = $hourlyActivity[$i] ?? 0;
        }

        return [
            'total_activities' => array_sum($activityCounts),
            'activity_counts' => $activityCounts,
            'today_stats' => $todayStats,
            'today_total' => array_sum($todayStats),
            'hourly_activity' => $hourlyData,
        ];
    }

    /**
     * Export activities as CSV.
     */
    private function exportCsv($activities)
    {
        $filename = 'activity_logs_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($activities) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, [
                'Date/Time',
                'User',
                'Activity Type',
                'Action',
                'IP Address',
                'User Agent',
                'Additional Data'
            ]);

            // Add data rows
            foreach ($activities as $activity) {
                fputcsv($file, [
                    $activity->created_at->format('Y-m-d H:i:s'),
                    $activity->user->name ?? 'System',
                    $activity->activity_type_label,
                    $activity->action,
                    $activity->ip_address,
                    $activity->user_agent,
                    json_encode($activity->properties),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export activities as JSON.
     */
    private function exportJson($activities)
    {
        $filename = 'activity_logs_' . date('Y-m-d_H-i-s') . '.json';

        $data = $activities->map(function ($activity) {
            return [
                'id' => $activity->id,
                'date_time' => $activity->created_at->format('Y-m-d H:i:s'),
                'user' => [
                    'id' => $activity->user_id,
                    'name' => $activity->user->name ?? 'System',
                    'email' => $activity->user->email ?? '',
                ],
                'activity_type' => $activity->activity_type,
                'activity_type_label' => $activity->activity_type_label,
                'action' => $activity->action,
                'subject' => [
                    'type' => $activity->model_type,
                    'id' => $activity->model_id,
                ],
                'properties' => $activity->properties,
                'ip_address' => $activity->ip_address,
                'user_agent' => $activity->user_agent,
            ];
        });

        return response()->json($data)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}