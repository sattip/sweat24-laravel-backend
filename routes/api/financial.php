<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentInstallmentController;
use App\Http\Controllers\CashRegisterEntryController;
use App\Http\Controllers\BusinessExpenseController;

/*
|--------------------------------------------------------------------------
| Financial Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Financial Features (Admin and Trainer)
    Route::middleware(['role:admin,trainer'])->group(function () {
        Route::apiResource('payment-installments', PaymentInstallmentController::class);
        Route::apiResource('cash-register', CashRegisterEntryController::class);
        Route::apiResource('business-expenses', BusinessExpenseController::class);
    });

    // Limited financial access for trainers (one week history)
    Route::middleware(['role:admin,trainer'])->group(function () {
        Route::get('cash-register/limited', [CashRegisterEntryController::class, 'limitedIndex']);
    });

    // Cash register session management (open/close)
    Route::middleware(['role:admin,trainer'])->group(function () {
        Route::get('cash-register-sessions/status', [CashRegisterEntryController::class, 'sessionStatus']);
        Route::post('cash-register-sessions/open', [CashRegisterEntryController::class, 'openSession']);
        Route::post('cash-register-sessions/{session}/close', [CashRegisterEntryController::class, 'closeSession']);
        Route::get('cash-register-sessions/history', [CashRegisterEntryController::class, 'sessionHistory']);
    });
});

// Financial Reports (Protected)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('v1/admin/financial-reports')->group(function () {
    Route::get('dashboard', [\App\Http\Controllers\FinancialReportsController::class, 'dashboard']);
    Route::get('total-revenue', [\App\Http\Controllers\FinancialReportsController::class, 'totalRevenue']);
    Route::get('revenue-per-customer', [\App\Http\Controllers\FinancialReportsController::class, 'revenuePerCustomer']);
    Route::get('revenue-per-service', [\App\Http\Controllers\FinancialReportsController::class, 'revenuePerService']);
    Route::get('revenue-per-store', [\App\Http\Controllers\FinancialReportsController::class, 'revenuePerStore']);
    Route::get('top-customers', [\App\Http\Controllers\FinancialReportsController::class, 'topCustomers']);
    Route::get('package-statistics', [\App\Http\Controllers\FinancialReportsController::class, 'packageStatistics']);
    Route::get('product-statistics', [\App\Http\Controllers\FinancialReportsController::class, 'productStatistics']);
    Route::get('expense-analysis', [\App\Http\Controllers\FinancialReportsController::class, 'expenseAnalysis']);
    Route::get('revenue-trends', [\App\Http\Controllers\FinancialReportsController::class, 'revenueTrends']);
    Route::get('payment-methods', [\App\Http\Controllers\FinancialReportsController::class, 'paymentMethods']);
    Route::get('profitability-analysis', [\App\Http\Controllers\FinancialReportsController::class, 'profitabilityAnalysis']);
    Route::get('customer-conversion', [\App\Http\Controllers\FinancialReportsController::class, 'customerConversion']);
    Route::get('customer-ltv', [\App\Http\Controllers\FinancialReportsController::class, 'customerLTV']);
    Route::get('retention-analysis', [\App\Http\Controllers\FinancialReportsController::class, 'retentionAnalysis']);
});

// Payroll Agreements (Admin only)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('v1/admin/payroll-agreements')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\PayrollAgreementController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Api\PayrollAgreementController::class, 'store']);
    Route::get('/instructor/{instructorId}/summary', [\App\Http\Controllers\Api\PayrollAgreementController::class, 'summary']);
    Route::put('/{payrollAgreement}', [\App\Http\Controllers\Api\PayrollAgreementController::class, 'update']);
    Route::post('/{payrollAgreement}/toggle-active', [\App\Http\Controllers\Api\PayrollAgreementController::class, 'toggleActive']);
    Route::delete('/{payrollAgreement}', [\App\Http\Controllers\Api\PayrollAgreementController::class, 'destroy']);
});
