<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\DashboardController;
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
    Route::get('dashboard/stats', [DashboardController::class, 'publicStats']);

    // Public dashboard activities (recent activity logs)
    Route::get('dashboard/activities', [DashboardController::class, 'activities']);
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
    Route::get('dashboard/stats', [DashboardController::class, 'stats']);
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
