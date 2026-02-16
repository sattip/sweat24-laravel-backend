<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\UserPackageController;

/*
|--------------------------------------------------------------------------
| Package Routes
|--------------------------------------------------------------------------
*/

// Public package routes
Route::prefix('v1')->group(function () {
    Route::get('packages', [PackageController::class, 'index']);
    Route::get('packages/{package}', [PackageController::class, 'show']);
});

// Protected package routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Packages (Admin and Trainer management)
    Route::apiResource('packages', PackageController::class)->except(['index', 'show'])->middleware('role:admin,trainer');

    // User Packages (Package Lifecycle Management)
    Route::prefix('user-packages')->group(function () {
        Route::get('/', [UserPackageController::class, 'index']);
        Route::get('/statistics', [UserPackageController::class, 'statistics']);
        Route::get('/expiring-report', [UserPackageController::class, 'expiringReport']);
        Route::get('/user/{userId}', [UserPackageController::class, 'userPackages']);
        Route::get('/user/{userId}/partial-payments', [UserPackageController::class, 'userPartialPayments']);
        Route::get('/{userPackage}', [UserPackageController::class, 'show']);
        Route::post('/', [UserPackageController::class, 'store']);
        Route::put('/{userPackage}', [UserPackageController::class, 'update']);
        Route::post('/{userPackage}/freeze', [UserPackageController::class, 'freeze']);
        Route::post('/{userPackage}/unfreeze', [UserPackageController::class, 'unfreeze']);
        Route::post('/{userPackage}/renew', [UserPackageController::class, 'renew']);
        Route::post('/{userPackage}/send-notification', [UserPackageController::class, 'sendExpiryNotification']);
    });

    // Mobile App - Get authenticated user's partial payment summary
    Route::get('/my-partial-payments', [UserPackageController::class, 'myPartialPayments']);

    // Mobile App - Get authenticated user's package history (expired, cancelled, completed)
    Route::get('/my-packages/history', [UserPackageController::class, 'myPackagesHistory']);

    // Mobile App - Get authenticated user's active packages
    Route::get('/my-active-packages', [UserPackageController::class, 'myActivePackages']);

    // Custom Packages route alias (points to user-packages endpoint)
    Route::get('custom-packages/user/{userId}', [UserPackageController::class, 'userPackages']);
});
