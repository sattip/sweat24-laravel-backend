<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\SpecializedServiceController;
use App\Http\Controllers\AppointmentRequestController;

/*
|--------------------------------------------------------------------------
| Service Routes
|--------------------------------------------------------------------------
*/

// Public services routes
Route::prefix('v1')->group(function () {
    Route::get('services', [ServiceController::class, 'index']);
    Route::get('services/{service}', [ServiceController::class, 'show']);

    // Public specialized services routes
    Route::get('specialized-services', [SpecializedServiceController::class, 'index']);
    Route::get('specialized-services/{specializedService}', [SpecializedServiceController::class, 'show']);
    Route::post('appointment-requests', [AppointmentRequestController::class, 'store']);
    Route::get('appointment-requests', [AppointmentRequestController::class, 'index']);

    // Public stores routes
    Route::get('stores', [\App\Http\Controllers\Api\StoresController::class, 'index']);
    Route::get('stores/{store}', [\App\Http\Controllers\Api\StoresController::class, 'show']);
});

// Protected service routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('services/{service}/trial-info', [ServiceController::class, 'getTrialInfo']);

    // Services Management (Admin/Trainer)
    Route::apiResource('services', ServiceController::class)->except(['show', 'index']);
    Route::post('services/{service}/toggle-active', [ServiceController::class, 'toggleActive']);

    // Specialized Services Management (Admin/Trainer)
    Route::apiResource('specialized-services', SpecializedServiceController::class)->except(['show', 'index']);
    Route::get('admin/specialized-services', [SpecializedServiceController::class, 'adminIndex']);
    Route::apiResource('appointment-requests', AppointmentRequestController::class)->except(['store', 'index']);
});
