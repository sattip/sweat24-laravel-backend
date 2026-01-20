<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1/auth')->group(function () {
    // Login routes - strict rate limiting to prevent brute force
    Route::middleware('throttle:auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/admin/login', [AuthController::class, 'adminLogin']);
        Route::post('/login-simple', [AuthController::class, 'loginSimple']);
    });

    // Registration routes - rate limited
    Route::middleware('throttle:registration')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/register-with-consent', [AuthController::class, 'registerWithConsent']);
    });

    Route::match(['get', 'post'], '/check-age', [AuthController::class, 'checkAge']);

    // Password reset routes - strict rate limiting
    Route::middleware('throttle:password-reset')->group(function () {
        Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail']);
        Route::post('/reset-password', [ForgotPasswordController::class, 'reset']);
        Route::post('/validate-reset-token', [ForgotPasswordController::class, 'validateToken']);
    });

    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');

    // Web session authentication endpoint
    Route::get('/session', [AuthController::class, 'session'])->middleware('web');
});
