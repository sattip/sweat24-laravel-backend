<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClientProfileController;
use App\Http\Controllers\Api\RegistrationController;
use App\Http\Controllers\AdminController;

/*
|--------------------------------------------------------------------------
| User & Profile Routes
|--------------------------------------------------------------------------
*/

// Two-Phase Registration routes (public)
Route::prefix('v1/registration')->group(function () {
    Route::post('/initial', [RegistrationController::class, 'initialRegistration']);
    Route::post('/accept-terms', [RegistrationController::class, 'acceptTerms']);
    Route::post('/complete', [RegistrationController::class, 'completeRegistration']);
    Route::get('/status', [RegistrationController::class, 'getRegistrationStatus']);
});

// Admin-only registration management routes
Route::prefix('v1/admin')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/users/{id}/approve', [RegistrationController::class, 'approveUser']);
    Route::post('/users/{id}/reject', [RegistrationController::class, 'rejectUser']);
});

// Admin Panel specific routes
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/users/{id}/approve', [AdminController::class, 'approveUser']);
    Route::post('/users/{id}/reject', [AdminController::class, 'rejectUser']);
    Route::get('/users/{userId}/full-profile', [AdminController::class, 'getUserFullProfile']);
    Route::get('/users/{userId}/packages', [\App\Http\Controllers\UserPackageController::class, 'userPackages']);
});

// Protected user routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Users/Members
    Route::apiResource('users', UserController::class);
    Route::get('users/search/by-phone', [UserController::class, 'searchByPhone']);
    Route::get('users/{id}/referral-info', [UserController::class, 'getUserReferralInfo']);

    // Client Profile Routes (for members to manage their own profile)
    Route::prefix('profile')->group(function () {
        Route::get('/', [ClientProfileController::class, 'show']);
        Route::put('/', [ClientProfileController::class, 'update']);
        Route::put('/password', [ClientProfileController::class, 'updatePassword']);
        Route::post('/avatar', [ClientProfileController::class, 'uploadAvatar']);
        Route::get('/notification-preferences', [ClientProfileController::class, 'getNotificationPreferences']);
        Route::put('/notification-preferences', [ClientProfileController::class, 'updateNotificationPreferences']);
        Route::get('/privacy-settings', [ClientProfileController::class, 'getPrivacySettings']);
        Route::put('/privacy-settings', [ClientProfileController::class, 'updatePrivacySettings']);
        Route::get('/booking-history', [ClientProfileController::class, 'bookingHistory']);
        Route::put('/bookings/{booking}/notes', [ClientProfileController::class, 'updateBookingNotes']);
        Route::post('/deactivation-request', [ClientProfileController::class, 'requestDeactivation']);
    });

    // Signature routes
    Route::post('signatures', [\App\Http\Controllers\SignatureController::class, 'store']);
    Route::get('signatures/{id}', [\App\Http\Controllers\SignatureController::class, 'show']);
    Route::get('users/{userId}/signatures', [\App\Http\Controllers\SignatureController::class, 'userSignatures']);
    Route::get('users/{id}/signatures', [\App\Http\Controllers\SignatureController::class, 'getUserSignatures']);

    Route::middleware(['role:admin'])->group(function () {
        Route::get('signatures', [\App\Http\Controllers\SignatureController::class, 'index']);

        // Admin can assign packages to users
        Route::post('admin/users/{user}/assign-package', [\App\Http\Controllers\Admin\UserPackageController::class, 'assign']);
        Route::delete('admin/users/{userId}/packages/{userPackageId}', [\App\Http\Controllers\Admin\UserPackageController::class, 'destroy']);
    });
});
