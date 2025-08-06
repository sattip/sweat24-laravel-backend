<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientProfileController;

Route::get('/', function () {
    return view('welcome');
});

// Simple login for web authentication
Route::get('/login', function () {
    if (auth()->check()) {
        return redirect('/dashboard');
    }
    return view('auth.login');
})->name('login');

Route::post('/login', function (\Illuminate\Http\Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (auth()->attempt($credentials, true)) {
        $request->session()->regenerate();
        return redirect()->intended('/dashboard');
    }

    return back()->withErrors([
        'email' => 'The provided credentials do not match our records.',
    ])->onlyInput('email');
})->name('login.submit');

Route::post('/logout', function (\Illuminate\Http\Request $request) {
    auth()->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
})->name('logout');

Route::get('/dashboard', function () {
    if (!auth()->check()) {
        return redirect('/login');
    }
    return view('dashboard', ['user' => auth()->user()]);
})->name('dashboard');

// Test route for signatures (remove in production)
Route::get('/test-signatures', function () {
    $signatures = \App\Models\Signature::with('user')->get();
    return response()->json([
        'count' => $signatures->count(),
        'signatures' => $signatures
    ]);
});

// Admin Routes
Route::prefix('admin')->group(function () {
    // Login routes
    Route::get('/login', function () {
        if (auth()->check() && auth()->user()->membership_type === 'Admin') {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.login');
    })->name('admin.login');
    
    Route::post('/login', [AuthController::class, 'adminLogin'])->name('admin.login.submit');
    
    // Protected admin routes
    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
        Route::post('/logout', [AuthController::class, 'logout'])->name('admin.logout');
        
        // Admin user management
        Route::prefix('users')->name('admin.users.')->group(function () {
            Route::get('/', [AdminController::class, 'index'])->name('index');
            Route::get('/create', [AdminController::class, 'create'])->name('create');
            Route::post('/', [AdminController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [AdminController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AdminController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminController::class, 'destroy'])->name('destroy');
        });
        
        // Member management
        Route::prefix('members')->name('admin.members.')->group(function () {
            Route::get('/', [App\Http\Controllers\MemberController::class, 'index'])->name('index');
            Route::get('/export-ems', [App\Http\Controllers\MemberController::class, 'exportEmsData'])->name('export.ems');
            Route::get('/ems-statistics', [App\Http\Controllers\MemberController::class, 'emsStatistics'])->name('ems.statistics');
        });
        
        // Trainer management
        Route::prefix('trainers')->name('admin.trainers.')->group(function () {
            Route::get('/', [App\Http\Controllers\TrainerController::class, 'index'])->name('index');
        });
        
        // Class management
        Route::prefix('classes')->name('admin.classes.')->group(function () {
            Route::get('/', [App\Http\Controllers\ClassController::class, 'index'])->name('index');
        });
        
        // Booking management
        Route::prefix('bookings')->name('admin.bookings.')->group(function () {
            Route::get('/', [App\Http\Controllers\BookingManagementController::class, 'index'])->name('index');
        });
        
        // Settings management
        Route::prefix('settings')->name('admin.settings.')->group(function () {
            Route::get('/', [App\Http\Controllers\SettingsController::class, 'index'])->name('index');
        });
        
        // Referral Analytics
        Route::prefix('referrals')->name('admin.referrals.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\ReferralAdminController::class, 'index'])->name('index');
            Route::get('/export', [App\Http\Controllers\Admin\ReferralAdminController::class, 'exportReferralData'])->name('export');
        });
        
        // Package management
        Route::prefix('packages')->name('admin.packages.')->group(function () {
            Route::get('/', [App\Http\Controllers\AdminPackageController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\AdminPackageController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\AdminPackageController::class, 'store'])->name('store');
            Route::get('/expiring', [App\Http\Controllers\AdminPackageController::class, 'expiringReport'])->name('expiring');
            Route::get('/{userPackage}', [App\Http\Controllers\AdminPackageController::class, 'show'])->name('show');
            Route::put('/{userPackage}', [App\Http\Controllers\AdminPackageController::class, 'update'])->name('update');
            Route::post('/{userPackage}/freeze', [App\Http\Controllers\AdminPackageController::class, 'freeze'])->name('freeze');
            Route::post('/{userPackage}/unfreeze', [App\Http\Controllers\AdminPackageController::class, 'unfreeze'])->name('unfreeze');
            Route::get('/{userPackage}/renew', [App\Http\Controllers\AdminPackageController::class, 'showRenewal'])->name('renew.show');
            Route::post('/{userPackage}/renew', [App\Http\Controllers\AdminPackageController::class, 'renew'])->name('renew');
            Route::post('/{userPackage}/notify', [App\Http\Controllers\AdminPackageController::class, 'sendNotification'])->name('notify');
            
            // Bulk operations
            Route::prefix('bulk')->name('bulk.')->group(function () {
                Route::get('/', [App\Http\Controllers\BulkPackageController::class, 'index'])->name('index');
                Route::post('/preview-extension', [App\Http\Controllers\BulkPackageController::class, 'previewExtension'])->name('preview-extension');
                Route::post('/execute-extension', [App\Http\Controllers\BulkPackageController::class, 'executeExtension'])->name('execute-extension');
                Route::post('/preview-pricing', [App\Http\Controllers\BulkPackageController::class, 'previewPricingAdjustment'])->name('preview-pricing');
                Route::post('/execute-pricing', [App\Http\Controllers\BulkPackageController::class, 'executePricingAdjustment'])->name('execute-pricing');
                Route::get('/filtered-packages', [App\Http\Controllers\BulkPackageController::class, 'getFilteredPackages'])->name('filtered-packages');
                Route::get('/operation/{bulkOperationId}/status', [App\Http\Controllers\BulkPackageController::class, 'getOperationStatus'])->name('operation-status');
                Route::post('/operation/{operation}/cancel', [App\Http\Controllers\BulkPackageController::class, 'cancelOperation'])->name('cancel-operation');
                Route::get('/history', [App\Http\Controllers\BulkPackageController::class, 'history'])->name('history');
                Route::get('/operation/{operation}', [App\Http\Controllers\BulkPackageController::class, 'showOperation'])->name('show-operation');
            });
        });
        
        // Activity management
        Route::prefix('activity')->name('admin.activity.')->group(function () {
            Route::get('/', [App\Http\Controllers\ActivityController::class, 'index'])->name('index');
            Route::get('/realtime', [App\Http\Controllers\ActivityController::class, 'realtime'])->name('realtime');
            Route::get('/export', [App\Http\Controllers\ActivityController::class, 'export'])->name('export');
            Route::get('/stream', [App\Http\Controllers\ActivityStreamController::class, 'stream'])->name('stream');
        });
        
        // Notification testing (development only)
        Route::prefix('notifications/test')->name('admin.notifications.test.')->group(function () {
            Route::get('/', [App\Http\Controllers\NotificationTestController::class, 'index'])->name('index');
            Route::post('/in-app', [App\Http\Controllers\NotificationTestController::class, 'sendTestInApp'])->name('in-app');
            Route::post('/email', [App\Http\Controllers\NotificationTestController::class, 'sendTestEmail'])->name('email');
            Route::post('/sms', [App\Http\Controllers\NotificationTestController::class, 'sendTestSMS'])->name('sms');
            Route::post('/bulk', [App\Http\Controllers\NotificationTestController::class, 'sendBulkNotification'])->name('bulk');
            Route::post('/targeted', [App\Http\Controllers\NotificationTestController::class, 'sendTargetedNotification'])->name('targeted');
            Route::get('/users', [App\Http\Controllers\NotificationTestController::class, 'getUsers'])->name('users');
            Route::delete('/clear', [App\Http\Controllers\NotificationTestController::class, 'clearTestNotifications'])->name('clear');
        });
        
        // Development tools / Settings testing
        Route::prefix('settings/test')->name('admin.settings.test.')->group(function () {
            Route::get('/', [App\Http\Controllers\DevToolsController::class, 'index'])->name('index');
            Route::post('/sample-data', [App\Http\Controllers\DevToolsController::class, 'generateSampleData'])->name('sample-data');
            Route::delete('/notifications', [App\Http\Controllers\DevToolsController::class, 'clearNotifications'])->name('clear-notifications');
            Route::post('/reset-users', [App\Http\Controllers\DevToolsController::class, 'resetUserStates'])->name('reset-users');
            Route::post('/package-expiry', [App\Http\Controllers\DevToolsController::class, 'triggerPackageExpiry'])->name('package-expiry');
            Route::get('/stats', [App\Http\Controllers\DevToolsController::class, 'getStats'])->name('stats');
        });
    });
});

// Client/Member Routes
Route::prefix('client')->middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', function () {
        if (!auth()->user() || !auth()->user()->isMember()) {
            return redirect('/');
        }
        return view('client.dashboard');
    })->name('client.dashboard');
});
