<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\GymClassController;
use App\Http\Controllers\PaymentInstallmentController;
use App\Http\Controllers\CashRegisterEntryController;
use App\Http\Controllers\BusinessExpenseController;

// Authentication routes (public)
Route::prefix('v1/auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
});

// Protected routes (require authentication)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Users/Members
    Route::apiResource('users', UserController::class);
    
    // Packages
    Route::apiResource('packages', PackageController::class);
    
    // Bookings
    Route::apiResource('bookings', BookingController::class);
    Route::post('bookings/{booking}/check-in', [BookingController::class, 'checkIn']);
    
    // Instructors/Trainers
    Route::apiResource('instructors', InstructorController::class);
    
    // Classes
    Route::apiResource('classes', GymClassController::class);
    
    // Financial Features
    Route::apiResource('payment-installments', PaymentInstallmentController::class);
    Route::apiResource('cash-register', CashRegisterEntryController::class);
    Route::apiResource('business-expenses', BusinessExpenseController::class);
    
    // Dashboard stats
    Route::get('dashboard/stats', function () {
        return response()->json([
            'total_members' => \App\Models\User::count(),
            'active_members' => \App\Models\User::where('status', 'active')->count(),
            'total_revenue' => \App\Models\CashRegisterEntry::where('type', 'income')->sum('amount'),
            'monthly_revenue' => \App\Models\CashRegisterEntry::where('type', 'income')
                ->whereMonth('created_at', now()->month)
                ->sum('amount'),
            'pending_payments' => \App\Models\PaymentInstallment::where('status', 'pending')->count(),
            'overdue_payments' => \App\Models\PaymentInstallment::where('status', 'overdue')->count(),
        ]);
    });
});