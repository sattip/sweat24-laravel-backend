<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\CashRegisterEntry;
use App\Models\GymClass;
use App\Models\Instructor;
use App\Models\ChatMessage;
use App\Models\PaymentInstallment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get public dashboard stats (basic info).
     */
    public function publicStats(): JsonResponse
    {
        return response()->json([
            'bookings_today' => Booking::whereDate('created_at', today())->count(),
            'total_users' => User::count(),
            'active_classes' => GymClass::whereDate('date', '>=', today())->count(),
            'upcoming_classes' => GymClass::whereDate('date', '>=', today())->take(5)->get()
        ]);
    }

    /**
     * Get recent activity logs.
     */
    public function activities(): JsonResponse
    {
        $activities = ActivityLog::with(['user:id,name,email'])
            ->select(['id', 'user_id', 'activity_type', 'action', 'created_at', 'properties'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'user' => $activity->user ? [
                        'id' => $activity->user->id,
                        'name' => $activity->user->name
                    ] : null,
                    'activity_type' => $activity->activity_type,
                    'action' => $activity->action,
                    'created_at' => $activity->created_at,
                    'properties' => $activity->properties
                ];
            });

        return response()->json([
            'activities' => $activities,
            'total_count' => ActivityLog::count()
        ]);
    }

    /**
     * Get role-based dashboard stats (authenticated users).
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $isTrainer = $user && $user->role === 'trainer';

        // Base stats (for all users)
        $stats = [
            'total_members' => User::count(),
            'active_members' => User::where('status', 'active')->count(),
            'monthly_revenue' => CashRegisterEntry::where('type', 'income')
                ->whereMonth('created_at', now()->month)
                ->sum('amount'),
            'pending_payments' => PaymentInstallment::where('status', 'pending')->count(),
            'overdue_payments' => PaymentInstallment::where('status', 'overdue')->count(),
        ];

        if ($isTrainer) {
            $instructor = Instructor::where('email', $user->email)->first();
            $trainerId = $instructor ? $instructor->id : null;

            $stats['my_booking_requests'] = $trainerId
                ? BookingRequest::where('trainer_id', $trainerId)
                    ->where('status', 'pending')
                    ->count()
                : 0;

            $stats['customers_to_renew'] = $trainerId
                ? User::whereHas('userPackages', function($q) {
                    $q->where('payment_status', '!=', 'fully_paid')
                        ->where('status', 'active');
                })->whereHas('bookings', function($q) use ($trainerId) {
                    $q->whereDate('date', today())
                        ->where('instructor_id', $trainerId);
                })->count()
                : 0;

            $stats['today_tasks'] = Task::where('assigned_to', $user->id)
                ->whereDate('deadline', today())
                ->where('status', '!=', 'completed')
                ->count();

            $stats['unread_messages'] = ChatMessage::whereHas('conversation', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->where('sender_id', '!=', $user->id)
                ->whereNull('read_at')
                ->count();
        } else {
            $stats['dormant_members'] = User::where('status', 'active')
                ->whereHas('userPackages', function($q) {
                    $q->where('status', 'active');
                })
                ->where(function($q) {
                    $q->whereDoesntHave('bookings', function($bq) {
                        $bq->where('date', '>=', now()->subDays(30));
                    });
                })
                ->count();

            $stats['inactive_customers'] = User::where('role', 'user')
                ->whereDoesntHave('userPackages', function($q) {
                    $q->where('status', 'active');
                })
                ->count();
        }

        return response()->json($stats);
    }
}
