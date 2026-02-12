<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationFilterController;
use App\Http\Controllers\OwnerNotificationController;

/*
|--------------------------------------------------------------------------
| Notification Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Notification routes
    Route::get('notifications/user', [NotificationController::class, 'userNotifications']);
    Route::post('notifications/{recipient}/read', [NotificationController::class, 'markAsRead']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    Route::middleware(['role:admin,trainer'])->group(function () {
        Route::apiResource('notifications', NotificationController::class);
        Route::post('notifications/{notification}/send', [NotificationController::class, 'send']);
        Route::post('notifications/preview-recipients', [NotificationController::class, 'previewRecipients']);
        Route::get('notifications/statistics', [NotificationController::class, 'statistics']);
        Route::get('notifications/types', [NotificationController::class, 'getTypes']);

        // Notification filters
        Route::apiResource('notification-filters', NotificationFilterController::class);
        Route::get('notification-filters/{filter}/preview', [NotificationFilterController::class, 'previewRecipients']);
        Route::get('notification-filters/criteria/options', [NotificationFilterController::class, 'criteriaOptions']);
    });

    // Owner Notifications routes (Admin and Trainer)
    Route::prefix('owner-notifications')->middleware(['role:admin,trainer'])->group(function () {
        Route::get('/', [OwnerNotificationController::class, 'index']);
        Route::post('/{notification}/read', [OwnerNotificationController::class, 'markAsRead']);
        Route::post('/read-all', [OwnerNotificationController::class, 'markAllAsRead']);
        Route::delete('/{notification}', [OwnerNotificationController::class, 'delete']);
    });
});
