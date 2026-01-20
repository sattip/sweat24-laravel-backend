<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\OrderController;

/*
|--------------------------------------------------------------------------
| Miscellaneous Routes
|--------------------------------------------------------------------------
*/

// Public questionnaire routes
Route::prefix('v1')->group(function () {
    Route::get('questionnaires/active', [\App\Http\Controllers\Api\QuestionnaireController::class, 'active']);

    // Public store product routes
    Route::get('store/products', [\App\Http\Controllers\StoreProductController::class, 'index']);
    Route::get('store/products/id/{id}', [\App\Http\Controllers\StoreProductController::class, 'showById']);
    Route::get('store/products/{slug}', [\App\Http\Controllers\StoreProductController::class, 'show']);

    // Public order routes (for checkout without authentication)
    Route::post('orders', [OrderController::class, 'store']);

    // Order history endpoint (accessible with user_id parameter or auth token)
    Route::get('orders/history', [OrderController::class, 'orderHistory']);

    // Public dashboard stats (basic info for logged-in users)
    Route::get('dashboard/stats', function () {
        return response()->json([
            'bookings_today' => \App\Models\Booking::whereDate('created_at', today())->count(),
            'total_users' => \App\Models\User::count(),
            'active_classes' => \App\Models\GymClass::whereDate('date', '>=', today())->count(),
            'upcoming_classes' => \App\Models\GymClass::whereDate('date', '>=', today())->take(5)->get()
        ]);
    });

    // Public dashboard activities (recent activity logs)
    Route::get('dashboard/activities', function () {
        $activities = \App\Models\ActivityLog::with(['user:id,name,email'])
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
            'total_count' => \App\Models\ActivityLog::count()
        ]);
    });
});

// Protected routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Order Management Routes (authenticated access)
    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/{order}', [OrderController::class, 'show']);
    Route::get('orders/user/history', [OrderController::class, 'userOrders']);

    // Admin Order Management
    Route::middleware(['role:admin'])->group(function () {
        Route::put('orders/{order}/status', [OrderController::class, 'updateStatus']);
    });

    // Dashboard stats (role-based)
    Route::get('dashboard/stats', function () {
        $user = auth()->user();
        $isTrainer = $user && $user->role === 'trainer';

        // Base stats (for all users)
        $stats = [
            'total_members' => \App\Models\User::count(),
            'active_members' => \App\Models\User::where('status', 'active')->count(),
            'monthly_revenue' => \App\Models\CashRegisterEntry::where('type', 'income')
                ->whereMonth('created_at', now()->month)
                ->sum('amount'),
            'pending_payments' => \App\Models\PaymentInstallment::where('status', 'pending')->count(),
            'overdue_payments' => \App\Models\PaymentInstallment::where('status', 'overdue')->count(),
        ];

        if ($isTrainer) {
            $instructor = \App\Models\Instructor::where('email', $user->email)->first();
            $trainerId = $instructor ? $instructor->id : null;

            $stats['my_booking_requests'] = $trainerId
                ? \App\Models\BookingRequest::where('trainer_id', $trainerId)
                    ->where('status', 'pending')
                    ->count()
                : 0;

            $stats['customers_to_renew'] = $trainerId
                ? \App\Models\User::whereHas('userPackages', function($q) {
                    $q->where('payment_status', '!=', 'fully_paid')
                        ->where('status', 'active');
                })->whereHas('bookings', function($q) use ($trainerId) {
                    $q->whereDate('date', today())
                        ->where('instructor_id', $trainerId);
                })->count()
                : 0;

            $stats['today_tasks'] = \App\Models\Task::where('assigned_to', $user->id)
                ->whereDate('due_date', today())
                ->where('status', '!=', 'completed')
                ->count();

            $stats['unread_messages'] = \App\Models\Message::where('recipient_id', $user->id)
                ->whereNull('read_at')
                ->count();
        } else {
            $stats['dormant_members'] = \App\Models\User::where('status', 'active')
                ->whereHas('userPackages', function($q) {
                    $q->where('status', 'active');
                })
                ->where(function($q) {
                    $q->whereDoesntHave('bookings', function($bq) {
                        $bq->where('date', '>=', now()->subDays(30));
                    });
                })
                ->count();

            $stats['inactive_customers'] = \App\Models\User::where('role', 'user')
                ->whereDoesntHave('userPackages', function($q) {
                    $q->where('status', 'active');
                })
                ->count();
        }

        return response()->json($stats);
    });
});

// Chat routes for client app (with auth)
Route::prefix('v1/chat')->middleware('auth:sanctum')->group(function () {
    Route::get('/conversation', [ChatController::class, 'getConversation']);
    Route::post('/messages', [ChatController::class, 'sendMessage']);
    Route::put('/conversations/{conversation}/read', [ChatController::class, 'markAsRead']);
});

// Custom broadcasting auth endpoint for Sanctum
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::post('broadcasting/auth', [\App\Http\Controllers\Api\BroadcastAuthController::class, 'authenticate']);
});

// Development/Debug endpoints (available in development mode only)
Route::prefix('v1/debug')->middleware(['auth:sanctum', 'debug'])->group(function () {
    Route::post('notifications/simulate-receive', [App\Http\Controllers\DebugController::class, 'simulateReceiveNotification']);
    Route::delete('notifications/clear-all', [App\Http\Controllers\DebugController::class, 'clearAllNotifications']);
    Route::put('notifications/mark-all-read', [App\Http\Controllers\DebugController::class, 'markAllAsRead']);
    Route::put('notifications/mark-all-unread', [App\Http\Controllers\DebugController::class, 'markAllAsUnread']);
    Route::get('notifications/bell-state', [App\Http\Controllers\DebugController::class, 'getNotificationBellState']);
    Route::get('system/status', [App\Http\Controllers\DebugController::class, 'getSystemStatus']);
});
