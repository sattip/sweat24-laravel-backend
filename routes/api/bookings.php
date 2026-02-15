<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\WaitlistController;
use App\Http\Controllers\CancellationPolicyController;
use App\Http\Controllers\BookingRequestController;

/*
|--------------------------------------------------------------------------
| Booking Routes
|--------------------------------------------------------------------------
*/

// Protected booking routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Bookings (authenticated routes)
    Route::get('bookings/history', [BookingController::class, 'history']);
    Route::apiResource('bookings', BookingController::class)->except(['index', 'store']);

    Route::post('bookings/{booking}/check-in', [BookingController::class, 'checkIn']);
    Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel']);
    Route::post('bookings/{booking}/mark-absent', [BookingController::class, 'markAbsent']);
    Route::get('bookings/{booking}/policy-check', [CancellationPolicyController::class, 'testPolicy']);
    Route::post('bookings/{booking}/reschedule', [CancellationPolicyController::class, 'requestReschedule']);

    // Booking Requests (authenticated routes)
    Route::get('booking-requests/my-requests', [BookingRequestController::class, 'userRequests']);
    Route::get('booking-requests/{bookingRequest}', [BookingRequestController::class, 'show']);
    Route::post('booking-requests/{bookingRequest}/cancel', [BookingRequestController::class, 'cancel']);
    Route::post('booking-requests', [BookingRequestController::class, 'store']);

    // Waitlist
    Route::get('my-waitlists', [WaitlistController::class, 'myWaitlists']);
    Route::post('classes/{class}/waitlist/join', [WaitlistController::class, 'join']);
    Route::delete('classes/{class}/waitlist/leave', [WaitlistController::class, 'leave']);
    Route::post('classes/{class}/waitlist/decline', [WaitlistController::class, 'decline']);
    Route::get('classes/{class}/waitlist/status', [WaitlistController::class, 'status']);
    Route::get('classes/{class}/waitlist', [WaitlistController::class, 'index'])->middleware('role:admin,trainer');

    // Cancellation Policy routes
    Route::get('cancellation-policies', [CancellationPolicyController::class, 'index']);
    Route::get('reschedules/history', [CancellationPolicyController::class, 'userRescheduleHistory']);

    // Cancellation Policies Admin Routes
    Route::middleware(['role:admin'])->group(function () {
        Route::post('cancellation-policies', [CancellationPolicyController::class, 'store']);
        Route::get('cancellation-policies/{cancellationPolicy}', [CancellationPolicyController::class, 'show']);
        Route::put('cancellation-policies/{cancellationPolicy}', [CancellationPolicyController::class, 'update']);
        Route::delete('cancellation-policies/{cancellationPolicy}', [CancellationPolicyController::class, 'destroy']);
        Route::patch('cancellation-policies/{cancellationPolicy}/toggle', [CancellationPolicyController::class, 'toggleStatus']);
        Route::get('cancellation-policies/statistics', [CancellationPolicyController::class, 'getStatistics']);
        Route::get('cancellation-policies/configuration-options', [CancellationPolicyController::class, 'getConfigurationOptions']);

        // Reschedule Admin Routes
        Route::get('reschedules/admin', [CancellationPolicyController::class, 'adminRescheduleRequests']);
        Route::put('reschedules/{reschedule}/process', [CancellationPolicyController::class, 'processReschedule']);
    });

    // Booking Request Management (Admin and Trainer)
    Route::middleware(['role:admin,trainer'])->group(function () {
        Route::get('admin/booking-requests', [BookingRequestController::class, 'index']);
        Route::get('admin/booking-requests-calendar', [BookingRequestController::class, 'getCalendarView']);
        Route::get('admin/booking-requests/statistics', [BookingRequestController::class, 'statistics']);
        Route::post('admin/booking-requests/{bookingRequest}/confirm', [BookingRequestController::class, 'confirm']);
        Route::post('admin/booking-requests/{bookingRequest}/reject', [BookingRequestController::class, 'reject']);
        Route::post('admin/booking-requests/{bookingRequest}/complete', [BookingRequestController::class, 'markCompleted']);
    });
});

// Protected booking routes (main booking endpoints)
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('bookings/history', [BookingController::class, 'history'])->name('bookings.history');
    Route::post('bookings', [BookingController::class, 'store'])->name('bookings.store');
    Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
});

// Public booking request routes
Route::prefix('v1')->group(function () {
    Route::post('booking-requests', [BookingRequestController::class, 'store']);
    Route::get('booking-requests/instructors', [BookingRequestController::class, 'getAvailableInstructors']);
});

// Admin cancellation policy test data endpoints (development only, protected)
Route::prefix('v1/admin/cancellation-policies')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('test-data', [CancellationPolicyController::class, 'seedTestData']);
    Route::delete('test-data', [CancellationPolicyController::class, 'clearTestData']);
});
