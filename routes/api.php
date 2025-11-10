<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\GymClassController;
use App\Http\Controllers\PaymentInstallmentController;
use App\Http\Controllers\CashRegisterEntryController;
use App\Http\Controllers\BusinessExpenseController;
use App\Http\Controllers\TimeTrackingController;
use App\Http\Controllers\WaitlistController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\CancellationPolicyController;
use App\Http\Controllers\SignatureController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationFilterController;
use App\Http\Controllers\UserPackageController;
use App\Http\Controllers\ClientProfileController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\AdminChatController;
use App\Http\Controllers\OwnerNotificationController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\ImageUploadController;
use App\Http\Controllers\SpecializedServiceController;
use App\Http\Controllers\AppointmentRequestController;
use App\Http\Controllers\BookingRequestController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\Api\ReferralController as ApiReferralController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\Api\RegistrationController;
use App\Http\Controllers\Api\MedicalHistoryController;
use App\Http\Controllers\Api\PointsSettingsController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TeamChatController;
use App\Http\Controllers\Api\ServiceController;

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

// Admin Panel specific routes (simplified path as requested)
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/users/{id}/approve', [AdminController::class, 'approveUser']);
    Route::post('/users/{id}/reject', [AdminController::class, 'rejectUser']);
    Route::get('/users/{userId}/full-profile', [AdminController::class, 'getUserFullProfile']);
});

// Authentication routes (public)
Route::prefix('v1/auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/register-with-consent', [AuthController::class, 'registerWithConsent']);
    Route::match(['get', 'post'], '/check-age', [AuthController::class, 'checkAge']);
    
    // Password reset routes
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail']);
    Route::post('/reset-password', [ForgotPasswordController::class, 'reset']);
    Route::post('/validate-reset-token', [ForgotPasswordController::class, 'validateToken']);
    
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
    
    // Web session authentication endpoint
    Route::get('/session', function () {
        if (auth()->check()) {
            return response()->json([
                'authenticated' => true,
                'user' => auth()->user()
            ]);
        } else {
            return response()->json([
                'authenticated' => false,
                'user' => null
            ]);
        }
    })->middleware('web');
    
    // Simple login endpoint for client app
    Route::post('/login-simple', function(\Illuminate\Http\Request $request) {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        
        if (auth()->attempt($credentials, true)) {
            return response()->json([
                'success' => true,
                'authenticated' => true,
                'user' => auth()->user()
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials'
        ], 401);
    });
});

// Public evaluation routes (anonymous access)
Route::prefix('v1/evaluations')->group(function () {
    Route::get('/{token}', [EvaluationController::class, 'getByToken']);
    Route::post('/{token}/submit', [EvaluationController::class, 'submit']);
});

// Public Referral Validation Routes
Route::prefix('v1/referrals')->group(function () {
    Route::post('validate', [ApiReferralController::class, 'validateReferral']);
});

// Public classes routes (for browsing without authentication)
Route::prefix('v1')->group(function () {
    Route::get('classes', [GymClassController::class, 'index']);
    Route::get('classes/{class}', [GymClassController::class, 'show']);
    
    // Public trainer routes
    Route::get('trainers', [\App\Http\Controllers\TrainerController::class, 'apiIndex']);
    Route::get('trainers/{id}', [\App\Http\Controllers\TrainerController::class, 'apiShow']);
    
    // Public package routes
    Route::get('packages', [PackageController::class, 'index']);
    Route::get('packages/{package}', [PackageController::class, 'show']);

    // Public stores routes
    Route::get('stores', [\App\Http\Controllers\Api\StoresController::class, 'index']);
    Route::get('stores/{store}', [\App\Http\Controllers\Api\StoresController::class, 'show']);

    // Public class types routes
    Route::get('class-types', [\App\Http\Controllers\Api\ClassTypesController::class, 'index']);

    // Public fitness classes routes
    Route::get('fitness-classes', [\App\Http\Controllers\Api\FitnessClassesController::class, 'index']);
    Route::get('fitness-classes/{id}', [\App\Http\Controllers\Api\FitnessClassesController::class, 'show']);

    // Public questionnaire routes
    Route::get('questionnaires/active', [\App\Http\Controllers\Api\QuestionnaireController::class, 'active']);

    // Public store product routes
    Route::get('store/products', [\App\Http\Controllers\StoreProductController::class, 'index']);
    Route::get('store/products/id/{id}', [\App\Http\Controllers\StoreProductController::class, 'showById']);
    Route::get('store/products/{slug}', [\App\Http\Controllers\StoreProductController::class, 'show']);
    
    // Public order routes (for checkout without authentication)
    Route::post('orders', [\App\Http\Controllers\OrderController::class, 'store']);

    // Order history endpoint (accessible with user_id parameter or auth token)
    Route::get('orders/history', [\App\Http\Controllers\OrderController::class, 'orderHistory']);

    // Public services routes
    Route::get('services', [ServiceController::class, 'index']);
    Route::get('services/{service}', [ServiceController::class, 'show']);
    Route::get('services/{service}/trial-info', [ServiceController::class, 'getTrialInfo'])->middleware('auth:sanctum');

    // Public specialized services routes
    Route::get('specialized-services', [SpecializedServiceController::class, 'index']);
    Route::get('specialized-services/{specializedService}', [SpecializedServiceController::class, 'show']);
    Route::post('appointment-requests', [AppointmentRequestController::class, 'store']);
    Route::get('appointment-requests', [AppointmentRequestController::class, 'index']); // Public listing for admin panel
    
    // Public booking request routes (EMS/Personal)
    Route::post('booking-requests', [BookingRequestController::class, 'store']);
    Route::get('booking-requests/instructors', [BookingRequestController::class, 'getAvailableInstructors']);

    // Public partner businesses routes
    Route::get('partners', [PartnerController::class, 'index']);
    
    // Public events routes
    Route::get('events', [EventController::class, 'index']);
    
    // Public dashboard stats (basic info for logged-in users)
    Route::get('dashboard/stats', function () {
        return response()->json([
            'bookings_today' => \App\Models\Booking::whereDate('created_at', today())->count(),
            'total_users' => \App\Models\User::count(),
            'active_classes' => \App\Models\GymClass::whereDate('date', '>=', today())->count(),
            'upcoming_classes' => \App\Models\GymClass::whereDate('date', '>=', today())->take(5)->get()
        ]);
    });

    // Public dashboard activities (recent activity logs)
    Route::get('dashboard/activities', function () {
        $activities = \App\Models\ActivityLog::with(['user:id,name,email'])
            ->select(['id', 'user_id', 'activity_type', 'action', 'created_at', 'properties'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'user' => $activity->user ? [
                        'id' => $activity->user->id,
                        'name' => $activity->user->name
                    ] : null,
                    'activity_type' => $activity->activity_type,
                    'action' => $activity->action,
                    'created_at' => $activity->created_at,
                    'properties' => $activity->properties
                ];
            });

        return response()->json([
            'activities' => $activities,
            'total_count' => \App\Models\ActivityLog::count()
        ]);
    });
});

// Remove temporary public booking routes - will add at end

// Protected routes (require authentication)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Users/Members
    Route::apiResource('users', UserController::class);
    Route::get('users/search/by-phone', [UserController::class, 'searchByPhone']);
    Route::get('users/{id}/referral-info', [UserController::class, 'getUserReferralInfo']);

    // Packages (Admin management)
    Route::apiResource('packages', PackageController::class)->except(['index', 'show'])->middleware('role:admin');

    // Bookings (authenticated routes)
    // Specific routes must be defined BEFORE apiResource to avoid conflicts
    Route::get('bookings/history', [BookingController::class, 'history']);
    Route::apiResource('bookings', BookingController::class)->except(['index', 'store']);
    
    // Booking Requests (authenticated routes)
    Route::get('booking-requests/my-requests', [BookingRequestController::class, 'userRequests']);
    Route::get('booking-requests/{bookingRequest}', [BookingRequestController::class, 'show']);
    Route::post('booking-requests/{bookingRequest}/cancel', [BookingRequestController::class, 'cancel']);
    Route::post('booking-requests', [BookingRequestController::class, 'store']); // Also allow authenticated submission
    
    // User Packages (Package Lifecycle Management)
    Route::prefix('user-packages')->group(function () {
        Route::get('/', [UserPackageController::class, 'index']);
        Route::get('/statistics', [UserPackageController::class, 'statistics']);
        Route::get('/expiring-report', [UserPackageController::class, 'expiringReport']);
        Route::get('/user/{userId}', [UserPackageController::class, 'userPackages']);
        Route::get('/{userPackage}', [UserPackageController::class, 'show']);
        Route::post('/', [UserPackageController::class, 'store']);
        Route::put('/{userPackage}', [UserPackageController::class, 'update']);
        Route::post('/{userPackage}/freeze', [UserPackageController::class, 'freeze']);
        Route::post('/{userPackage}/unfreeze', [UserPackageController::class, 'unfreeze']);
        Route::post('/{userPackage}/renew', [UserPackageController::class, 'renew']);
        Route::post('/{userPackage}/send-notification', [UserPackageController::class, 'sendExpiryNotification']);
    });
    
    Route::post('bookings/{booking}/check-in', [BookingController::class, 'checkIn']);
    Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel']);
    Route::get('bookings/{booking}/policy-check', [CancellationPolicyController::class, 'testPolicy']);
    Route::post('bookings/{booking}/reschedule', [CancellationPolicyController::class, 'requestReschedule']);
    
    // Instructors/Trainers
    Route::apiResource('instructors', InstructorController::class);

    // Services Management (Admin/Trainer)
    Route::apiResource('services', ServiceController::class)->except(['show', 'index']);
    Route::post('services/{service}/toggle-active', [ServiceController::class, 'toggleActive']);

    // Specialized Services Management (Admin/Trainer)
    Route::apiResource('specialized-services', SpecializedServiceController::class)->except(['show', 'index']);
    Route::get('admin/specialized-services', [SpecializedServiceController::class, 'adminIndex']);
    Route::apiResource('appointment-requests', AppointmentRequestController::class)->except(['store', 'index']); // index moved to public routes
    
    // Booking Request routes moved up in this file
    
    // Referral routes
    Route::get('referral/data', [ReferralController::class, 'getUserReferralData']);
    Route::post('referral/redeem/{reward}', [ReferralController::class, 'redeemReward']);
    Route::post('referral/process', [ReferralController::class, 'processReferral']);
    
    // How Found Us Referral Routes
    Route::get('referrals/my-referrals', [ApiReferralController::class, 'myReferrals']);
    
    // Partner business routes
    Route::post('partners/offers/{offer}/redeem', [PartnerController::class, 'generateRedemptionCode']);
    Route::post('partners/redemptions/{redemption}/use', [PartnerController::class, 'useRedemption']);
    Route::get('partners/redemptions', [PartnerController::class, 'getUserRedemptions']);
    
    // Event routes
    Route::post('events/{event}/rsvp', [EventController::class, 'rsvp']);
    Route::get('events/rsvps', [EventController::class, 'getUserRSVPs']);
    
    // Classes (authenticated routes)
    Route::post('classes', [GymClassController::class, 'store']);
    Route::put('classes/{class}', [GymClassController::class, 'update']);
    Route::delete('classes/{class}', [GymClassController::class, 'destroy']);

    // Recurring class management (Admin only)
    Route::get('admin/classes/{class}/recurring/info', [GymClassController::class, 'getRecurringInfo'])->middleware('role:admin');
    Route::get('admin/classes/{class}/recurring/preview', [GymClassController::class, 'previewRecurringDeletion'])->middleware('role:admin');
    Route::delete('admin/classes/{class}/recurring', [GymClassController::class, 'deleteRecurring'])->middleware('role:admin');

    // Fitness Classes (authenticated routes for admin/trainer)
    Route::middleware(['role:admin,trainer'])->group(function () {
        Route::post('fitness-classes', [\App\Http\Controllers\Api\FitnessClassesController::class, 'store']);
        Route::put('fitness-classes/{id}', [\App\Http\Controllers\Api\FitnessClassesController::class, 'update']);
        Route::delete('fitness-classes/{id}', [\App\Http\Controllers\Api\FitnessClassesController::class, 'destroy']);
    });

    // Questionnaires (authenticated routes for admin/trainer)
    Route::middleware(['role:admin,trainer'])->group(function () {
        Route::apiResource('questionnaires', \App\Http\Controllers\Api\QuestionnaireController::class);
        Route::post('questionnaires/{questionnaire}/toggle-active', [\App\Http\Controllers\Api\QuestionnaireController::class, 'toggleActive']);

        // Questionnaire Responses
        Route::apiResource('questionnaire-responses', \App\Http\Controllers\Api\QuestionnaireResponseController::class);
        Route::get('questionnaires/{questionnaire}/responses', [\App\Http\Controllers\Api\QuestionnaireResponseController::class, 'getQuestionnaireResponses']);
    });

    // Waitlist
    Route::post('classes/{class}/waitlist/join', [WaitlistController::class, 'join']);
    Route::delete('classes/{class}/waitlist/leave', [WaitlistController::class, 'leave']);
    Route::get('classes/{class}/waitlist/status', [WaitlistController::class, 'status']);
    Route::get('classes/{class}/waitlist', [WaitlistController::class, 'index'])->middleware('role:admin');
    
    // Financial Features (Admin only)
    Route::middleware(['role:admin'])->group(function () {
        Route::apiResource('payment-installments', PaymentInstallmentController::class);
        Route::apiResource('cash-register', CashRegisterEntryController::class);
        Route::apiResource('business-expenses', BusinessExpenseController::class);
    });
    
    // Limited financial access for trainers (one week history)
    Route::middleware(['role:admin,trainer'])->group(function () {
        Route::get('cash-register/limited', [CashRegisterEntryController::class, 'limitedIndex']);
    });
    
    // Time Tracking for Trainers
    Route::middleware(['role:trainer,admin'])->group(function () {
        Route::post('time-tracking/start', [TimeTrackingController::class, 'startSession']);
        Route::post('time-tracking/end', [TimeTrackingController::class, 'endSession']);
        Route::get('time-tracking/current', [TimeTrackingController::class, 'currentSession']);
        Route::get('time-tracking/history', [TimeTrackingController::class, 'history']);
    });
    
    // Admin time tracking management
    Route::middleware(['role:admin'])->group(function () {
        Route::get('time-tracking/admin', [TimeTrackingController::class, 'adminIndex']);
        Route::put('time-tracking/admin/{entry}', [TimeTrackingController::class, 'adminUpdate']);
    });
    
    // Admin Store Product Management
    Route::middleware(['role:admin'])->group(function () {
        Route::get('admin/store/products', [\App\Http\Controllers\StoreProductController::class, 'adminIndex']);
        Route::post('admin/store/products', [\App\Http\Controllers\StoreProductController::class, 'store']);
        Route::put('admin/store/products/{id}', [\App\Http\Controllers\StoreProductController::class, 'update']);
        Route::delete('admin/store/products/{id}', [\App\Http\Controllers\StoreProductController::class, 'destroy']);
        Route::post('admin/store/upload-image', [ImageUploadController::class, 'uploadProductImage']);
        
        // Admin Events Management
        Route::get('admin/events', [EventController::class, 'adminIndex']);
        Route::get('admin/event-rsvps', [EventController::class, 'adminGetAllRsvps']);
        
        // Points Settings (Admin only)
        Route::prefix('points')->group(function () {
            Route::get('settings', [PointsSettingsController::class, 'getSettings']);
            Route::put('settings', [PointsSettingsController::class, 'updateSettings']);
            Route::post('settings/reset', [PointsSettingsController::class, 'resetSettings']);
            Route::get('user', [PointsSettingsController::class, 'getUserPoints']);
            Route::post('users', [PointsSettingsController::class, 'getUsersPoints']);
        });
        
        // Points Rewards Admin Management (with admin prefix) - removed from here
        
        Route::post('events', [EventController::class, 'store']);
        Route::put('events/{event}', [EventController::class, 'update']);
        Route::delete('events/{event}', [EventController::class, 'destroy']);
        
        // Admin Referral Management
        Route::get('admin/referral-codes', [ReferralController::class, 'adminGetCodes']);
        Route::get('admin/referral-rewards', [ReferralController::class, 'adminGetRewards']);
        Route::post('admin/referral-rewards', [ReferralController::class, 'adminCreateReward']);
        Route::put('admin/referral-rewards/{reward}', [ReferralController::class, 'adminUpdateReward']);
        Route::delete('admin/referral-rewards/{reward}', [ReferralController::class, 'adminDeleteReward']);
        Route::get('admin/referrals', [ReferralController::class, 'adminGetReferrals']);

        // How Found Us Admin Routes
        Route::get('admin/referrals/top-referrers', [ApiReferralController::class, 'topReferrers']);
        Route::get('admin/referrals/source-statistics', [ApiReferralController::class, 'sourceStatistics']);
        
        // Assign package to user
        Route::post('admin/users/{user}/assign-package', [\App\Http\Controllers\Admin\UserPackageController::class, 'assign']);
        // Hard delete a user package for a specific user (admin only)
        Route::delete('admin/users/{userId}/packages/{userPackageId}', [\App\Http\Controllers\Admin\UserPackageController::class, 'destroy']);
        
        // Admin Progress Photos Routes
        Route::get('admin/users/{userId}/progress-photos', [\App\Http\Controllers\Api\ProgressPhotoController::class, 'getUserPhotos']);
        Route::delete('admin/progress-photos/{id}', [\App\Http\Controllers\Api\ProgressPhotoController::class, 'adminDestroy']);
        
        // Admin Body Measurements Routes
        Route::get('admin/users/{userId}/measurements', [\App\Http\Controllers\Api\BodyMeasurementController::class, 'getUserMeasurements']);
        Route::get('admin/users/{userId}/measurements/latest', [\App\Http\Controllers\Api\BodyMeasurementController::class, 'getUserLatestMeasurement']);

        // Admin Partner Management
        Route::get('admin/partners', [PartnerController::class, 'adminGetPartners']);
        Route::post('admin/partners', [PartnerController::class, 'adminCreatePartner']);
        Route::put('admin/partners/{partner}', [PartnerController::class, 'adminUpdatePartner']);
        Route::delete('admin/partners/{partner}', [PartnerController::class, 'adminDeletePartner']);
        Route::get('admin/partner-offers', [PartnerController::class, 'adminGetOffers']);
        Route::post('admin/partner-offers', [PartnerController::class, 'adminCreateOffer']);
        Route::put('admin/partner-offers/{offer}', [PartnerController::class, 'adminUpdateOffer']);
        Route::delete('admin/partner-offers/{offer}', [PartnerController::class, 'adminDeleteOffer']);
        Route::get('admin/partner-redemptions', [PartnerController::class, 'adminGetRedemptions']);
        
        // Admin Booking Request Management (EMS/Personal)
        Route::get('admin/booking-requests', [BookingRequestController::class, 'index']);
        Route::get('admin/booking-requests-calendar', [BookingRequestController::class, 'getCalendarView']);
        Route::get('admin/booking-requests/statistics', [BookingRequestController::class, 'statistics']);
        Route::post('admin/booking-requests/{bookingRequest}/confirm', [BookingRequestController::class, 'confirm']);
        Route::post('admin/booking-requests/{bookingRequest}/reject', [BookingRequestController::class, 'reject']);
        Route::post('admin/booking-requests/{bookingRequest}/complete', [BookingRequestController::class, 'markCompleted']);
    });
    
    // Referral Program Routes (authenticated access)
    Route::get('referral/data', [ReferralController::class, 'getUserReferralData']);
    Route::post('referral/redeem/{reward}', [ReferralController::class, 'redeemReward']);
    
    // Partner Offers Routes (authenticated access)
    Route::get('partner-offers/available', [PartnerController::class, 'getAvailableOffers']);
    Route::post('partner-offers/{offer}/redeem', [PartnerController::class, 'redeemOffer']);
    Route::post('partners/offers/{offer}/redeem', [PartnerController::class, 'generateRedemptionCode']);
    
    // Order Management Routes (authenticated access)
    Route::get('orders', [\App\Http\Controllers\OrderController::class, 'index']);
    Route::get('orders/{order}', [\App\Http\Controllers\OrderController::class, 'show']);
    Route::get('orders/user/history', [\App\Http\Controllers\OrderController::class, 'userOrders']);
    
    // Admin Order Management
    Route::middleware(['role:admin'])->group(function () {
        Route::put('orders/{order}/status', [\App\Http\Controllers\OrderController::class, 'updateStatus']);
    });
    
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
    
    // Evaluation routes (authenticated)
    Route::middleware(['role:admin,trainer'])->group(function () {
        Route::post('classes/{class}/evaluations/create', [EvaluationController::class, 'createEvaluationForCompletedClass']);
        Route::get('classes/{class}/evaluations/stats', [EvaluationController::class, 'classStats']);
        Route::get('instructors/{instructor}/evaluations/stats', [EvaluationController::class, 'instructorStats']);
        Route::get('evaluations/pending-count', [EvaluationController::class, 'pendingCount']);
    });

    // Referral Management (Admin & Trainer)
    Route::middleware(['role:admin,trainer'])->group(function () {
        Route::get('admin/referrals/phone-based', [UserController::class, 'getPhoneBasedReferrals']);
    });

    // Priority Booking Settings (Admin only)
    Route::middleware(['role:admin'])->group(function () {
        Route::get('admin/priority-booking-settings', [\App\Http\Controllers\Api\PriorityBookingSettingsController::class, 'index']);
        Route::put('admin/priority-booking-settings', [\App\Http\Controllers\Api\PriorityBookingSettingsController::class, 'update']);
    });
    
    // Cancellation Policy routes
    Route::get('cancellation-policies', [CancellationPolicyController::class, 'index']);
    Route::get('reschedules/history', [CancellationPolicyController::class, 'userRescheduleHistory']);
    
    Route::middleware(['role:admin'])->group(function () {
        // Cancellation Policies Admin Routes
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
    
    // Signature routes
    Route::post('signatures', [SignatureController::class, 'store']);
    Route::get('signatures/{id}', [SignatureController::class, 'show']);
    Route::get('users/{userId}/signatures', [SignatureController::class, 'userSignatures']);
    Route::get('users/{id}/signatures', [SignatureController::class, 'getUserSignatures']); // Admin Panel endpoint
    
    Route::middleware(['role:admin'])->group(function () {
        Route::get('signatures', [SignatureController::class, 'index']);
    });
    
    // Notification routes
    Route::get('notifications/user', [NotificationController::class, 'userNotifications']);
    Route::post('notifications/{recipient}/read', [NotificationController::class, 'markAsRead']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    
    Route::middleware(['role:admin'])->group(function () {
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
    
    // Progress Photos Routes
    Route::prefix('progress-photos')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\ProgressPhotoController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\ProgressPhotoController::class, 'store']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\ProgressPhotoController::class, 'destroy']);
    });
    
    // Body Measurements Routes
    Route::prefix('measurements')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\BodyMeasurementController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\BodyMeasurementController::class, 'store']);
        Route::get('/latest', [\App\Http\Controllers\Api\BodyMeasurementController::class, 'latest']);
        Route::get('/comparison', [\App\Http\Controllers\Api\BodyMeasurementController::class, 'comparison']);
        Route::get('/{id}', [\App\Http\Controllers\Api\BodyMeasurementController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\Api\BodyMeasurementController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\BodyMeasurementController::class, 'destroy']);
    });
    
    // Medical History Routes
    Route::prefix('medical-history')->group(function () {
        Route::get('/ems-contraindications', [MedicalHistoryController::class, 'getEmsContraindications']);
        Route::post('/', [MedicalHistoryController::class, 'submitMedicalHistory']);
        Route::get('/', [MedicalHistoryController::class, 'getMedicalHistory']);
        Route::get('/{userId}', [MedicalHistoryController::class, 'getMedicalHistory'])->where('userId', '[0-9]+')->middleware('role:admin,trainer');
        Route::put('/', [MedicalHistoryController::class, 'updateMedicalHistory']);
    });
    
    
    // Admin Chat Management routes
    Route::prefix('admin/chat')->middleware(['role:admin'])->group(function () {
        Route::get('/conversations', [AdminChatController::class, 'getConversations']);
        Route::post('/conversations', [AdminChatController::class, 'createConversation']);
        Route::post('/messages', [AdminChatController::class, 'sendMessage']);
        Route::put('/conversations/{conversation}/read', [AdminChatController::class, 'markAsRead']);
        Route::put('/conversations/{conversation}/status', [AdminChatController::class, 'updateStatus']);
    });
    
    // Owner Notifications routes
    Route::prefix('owner-notifications')->middleware(['role:admin'])->group(function () {
        Route::get('/', [OwnerNotificationController::class, 'index']);
        Route::post('/{notification}/read', [OwnerNotificationController::class, 'markAsRead']);
        Route::post('/read-all', [OwnerNotificationController::class, 'markAllAsRead']);
        Route::delete('/{notification}', [OwnerNotificationController::class, 'delete']);
    });

    // Task Management routes (Admin and Trainer only)
    Route::prefix('tasks')->middleware(['role:admin,trainer'])->group(function () {
        Route::get('/', [TaskController::class, 'index']);
        Route::post('/', [TaskController::class, 'store']);
        Route::get('/stats', [TaskController::class, 'stats']);
        Route::get('/assignable-users', [TaskController::class, 'getAssignableUsers']);
        Route::get('/my-tasks', [TaskController::class, 'getMyTasks']);
        Route::get('/my-pending-tasks', [TaskController::class, 'getMyPendingTasks']);
        Route::get('/high-priority', [TaskController::class, 'getHighPriorityTasks']);
        Route::get('/{task}', [TaskController::class, 'show']);
        Route::put('/{task}', [TaskController::class, 'update']);
        Route::delete('/{task}', [TaskController::class, 'destroy']);
        Route::post('/{task}/mark-completed', [TaskController::class, 'markCompleted']);
        Route::post('/{task}/acknowledge', [TaskController::class, 'acknowledgeTask']);
    });

    // Alternative task routes with hyphens (for convenience)
    Route::middleware(['role:admin,trainer'])->group(function () {
        Route::get('tasks-stats', [TaskController::class, 'stats']);
        Route::get('tasks-assignable-users', [TaskController::class, 'getAssignableUsers']);
        Route::get('tasks-my-tasks', [TaskController::class, 'getMyTasks']);
        Route::get('tasks-my-pending-tasks', [TaskController::class, 'getMyPendingTasks']);
        Route::get('tasks-high-priority', [TaskController::class, 'getHighPriorityTasks']);
    });

    // Task routes without prefix (for frontend compatibility)
    Route::middleware(['role:admin,trainer'])->group(function () {
        Route::get('my-pending-tasks', [TaskController::class, 'getMyPendingTasks']);
        Route::get('assignable-users', [TaskController::class, 'getAssignableUsers']);
    });

    // Team Chat routes (Admin and Trainer only)
    Route::prefix('team-chat')->middleware(['role:admin,trainer'])->group(function () {
        Route::get('messages', [TeamChatController::class, 'getMessages']);
        Route::post('messages', [TeamChatController::class, 'sendMessage']);
        Route::get('online-users', [TeamChatController::class, 'getOnlineUsers']);
        Route::get('stats', [TeamChatController::class, 'getStats']);
        Route::delete('messages/{message}', [TeamChatController::class, 'deleteMessage']);
    });

    // Development/Debug endpoints (available in development mode only)
    Route::prefix('debug')->middleware(['auth:sanctum', 'debug'])->group(function () {
        Route::post('notifications/simulate-receive', [App\Http\Controllers\DebugController::class, 'simulateReceiveNotification']);
        Route::delete('notifications/clear-all', [App\Http\Controllers\DebugController::class, 'clearAllNotifications']);
        Route::put('notifications/mark-all-read', [App\Http\Controllers\DebugController::class, 'markAllAsRead']);
        Route::put('notifications/mark-all-unread', [App\Http\Controllers\DebugController::class, 'markAllAsUnread']);
        Route::get('notifications/bell-state', [App\Http\Controllers\DebugController::class, 'getNotificationBellState']);
        Route::get('system/status', [App\Http\Controllers\DebugController::class, 'getSystemStatus']);
    });
});

// ============ MOBILE POINTS API ROUTES ============

// Public Points API Routes
Route::prefix('v1/points')->group(function () {
    // User points (accessible by admin panel without auth)
    Route::get('/user', [\App\Http\Controllers\Api\Mobile\PointsController::class, 'getUserPoints']);
    Route::get('/history', [\App\Http\Controllers\Api\Mobile\PointsController::class, 'getPointsHistory']);
    Route::get('/stats', [\App\Http\Controllers\Api\Mobile\PointsController::class, 'getPointsStats']);
    
    // Rewards (public access for mobile app)
    Route::get('/rewards/affordable', [\App\Http\Controllers\Api\Mobile\PointsController::class, 'getAffordableRewards']);
    Route::get('/rewards', [\App\Http\Controllers\Api\Mobile\PointsController::class, 'getAllRewards']);
    Route::post('/rewards/{id}/redeem', [\App\Http\Controllers\Api\Mobile\PointsController::class, 'redeemReward']);
    
    // User redemptions
    Route::get('/redemptions', [\App\Http\Controllers\Api\Mobile\PointsController::class, 'getUserRedemptions']);
    
    // Test endpoint
    Route::get('/test-user', function(\Illuminate\Http\Request $request) {
        $userId = $request->query('user_id', 79);
        $userPoints = \App\Models\UserPoints::where('user_id', $userId)->first();
        return response()->json([
            'success' => true,
            'test' => true,
            'data' => [
                'user_id' => $userId,
                'points_balance' => $userPoints ? $userPoints->points_balance : 0
            ]
        ]);
    });
});

// Mobile Points API Routes (Protected)
Route::middleware(['auth:sanctum'])->prefix('v1/points')->group(function () {
    // User points history and stats
    Route::get('/history', [\App\Http\Controllers\Api\Mobile\PointsController::class, 'getPointsHistory']);
    Route::get('/stats', [\App\Http\Controllers\Api\Mobile\PointsController::class, 'getPointsStats']);
    
    // Rewards
    Route::get('/rewards/affordable', [\App\Http\Controllers\Api\Mobile\PointsController::class, 'getAffordableRewards']);
    Route::get('/rewards', [\App\Http\Controllers\Api\Mobile\PointsController::class, 'getAllRewards']);
    Route::post('/rewards/{id}/redeem', [\App\Http\Controllers\Api\Mobile\PointsController::class, 'redeemReward']);
    
    // User redemptions
    Route::get('/redemptions', [\App\Http\Controllers\Api\Mobile\PointsController::class, 'getUserRedemptions']);
});

// Simple test endpoint
Route::post('v1/bookings/simple', function(\Illuminate\Http\Request $request) {
    return response()->json([
        'success' => true,
        'message' => 'Simple booking endpoint works!',
        'data' => $request->all()
    ]);
});



// Debug authentication endpoint
Route::get('v1/debug/auth', function(\Illuminate\Http\Request $request) {
    $bearerToken = $request->bearerToken();
    $headers = $request->headers->all();
    
    try {
        $guard = auth('sanctum');
        $user = $guard->user();
        
        return response()->json([
            'bearer_token' => $bearerToken,
            'authorization_header' => $headers['authorization'] ?? null,
            'user' => $user,
            'guard_check' => $guard->check(),
            'auth_check' => auth()->check(),
            'request_user' => $request->user(),
        ]);
    } catch (Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'bearer_token' => $bearerToken,
            'authorization_header' => $headers['authorization'] ?? null,
        ]);
    }
});

// Public booking routes (must be after protected routes to avoid middleware conflicts)
Route::prefix('v1')->group(function () {
    Route::get('bookings', [BookingController::class, 'index'])->name('public.bookings.index');
    Route::get('bookings/history', [BookingController::class, 'testHistory'])->name('public.bookings.history');
    Route::post('bookings', [BookingController::class, 'store'])->name('public.bookings.store');
    Route::get('bookings/test', [BookingController::class, 'testHistory'])->name('public.bookings.test.history');
    Route::post('bookings/test', [BookingController::class, 'test'])->name('public.bookings.test');
    Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('public.bookings.cancel');
});

// Direct route outside all middleware
Route::get('/test-history', [BookingController::class, 'testHistory']);

// Add policy endpoint under v1 prefix for client app
Route::prefix('v1')->group(function () {
    Route::get('test-policy/{booking_id}', [CancellationPolicyController::class, 'testPolicy'])->name('public.test.policy');
    
    // Test endpoints for cancellation policies (development only)
    Route::prefix('admin/cancellation-policies')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('test-data', [CancellationPolicyController::class, 'seedTestData']);
        Route::delete('test-data', [CancellationPolicyController::class, 'clearTestData']);
    });
});

Route::get('/test-policy/{bookingId}', [CancellationPolicyController::class, 'testPolicy']);
Route::get('/test-order-notification', [TestController::class, 'createTestOrderNotification']);

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

// ============ LOYALTY SYSTEM ROUTES ============

// Admin Loyalty Management (Protected)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('v1/admin')->group(function () {
    // Loyalty Rewards CRUD
    Route::apiResource('loyalty-rewards', \App\Http\Controllers\Api\LoyaltyRewardController::class);
    Route::post('loyalty-rewards/{loyaltyReward}/toggle-status', [\App\Http\Controllers\Api\LoyaltyRewardController::class, 'toggleStatus']);
    Route::get('loyalty-rewards/{loyaltyReward}/redemptions', [\App\Http\Controllers\Api\LoyaltyRewardController::class, 'redemptions']);
    
    // Loyalty Redemptions Management (MISSING ENDPOINT)
    Route::get('loyalty/redemptions', [\App\Http\Controllers\Api\LoyaltyController::class, 'adminGetRedemptions']);
    Route::get('loyalty-redemptions', [\App\Http\Controllers\Api\LoyaltyController::class, 'adminGetRedemptions']); // Alternative endpoint for admin panel
    
    // Loyalty Statistics
    Route::get('loyalty/stats', [\App\Http\Controllers\Api\LoyaltyController::class, 'stats']);
});

// User Loyalty Routes (Protected)
Route::middleware(['auth:sanctum'])->prefix('v1/loyalty')->group(function () {
    // Dashboard & Balance
    Route::get('dashboard', [\App\Http\Controllers\Api\LoyaltyController::class, 'dashboard']);
    Route::get('points/history', [\App\Http\Controllers\Api\LoyaltyController::class, 'pointsHistory']);
    
    // Available Rewards & Redemption
    Route::get('rewards/available', [\App\Http\Controllers\Api\LoyaltyController::class, 'availableRewards']);
    Route::post('rewards/{loyaltyReward}/redeem', [\App\Http\Controllers\Api\LoyaltyController::class, 'redeemReward']);
    
    // My Redemptions
    Route::get('redemptions', [\App\Http\Controllers\Api\LoyaltyController::class, 'myRedemptions']);
    Route::get('redemptions/{redemptionCode}/check', [\App\Http\Controllers\Api\LoyaltyController::class, 'checkRedemption']);
});

// ============ REFERRAL SYSTEM ROUTES ============

// Admin Referral Management (Protected)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('v1/admin')->group(function () {
    // Referral Reward Tiers CRUD
    Route::apiResource('referral-reward-tiers', \App\Http\Controllers\Api\ReferralRewardTierController::class);
    Route::post('referral-reward-tiers/{referralRewardTier}/toggle-status', [\App\Http\Controllers\Api\ReferralRewardTierController::class, 'toggleStatus']);
    
    // Enhanced Referral Management (extending existing)
    Route::get('referral-stats', [ReferralController::class, 'adminGetStats']);
});

// ============ PUBLIC TEST ENDPOINTS (για debugging) ============
Route::prefix('v1/test')->group(function () {
    // Public test endpoint για referral tiers
    Route::get('referral-tiers', function() {
        $tiers = \App\Models\ReferralRewardTier::all();
        return response()->json([
            'success' => true,
            'count' => $tiers->count(),
            'data' => $tiers,
            'message' => 'Public test endpoint - no auth required'
        ]);
    });
    
    // Public test endpoint για loyalty rewards
    Route::get('loyalty-rewards', function() {
        $rewards = \App\Models\LoyaltyReward::all();
        return response()->json([
            'success' => true,
            'count' => $rewards->count(),
            'data' => $rewards,
            'message' => 'Public test endpoint - no auth required'
        ]);
    });
    
    // Public test για admin authentication
    Route::get('admin-auth', function(\Illuminate\Http\Request $request) {
        $token = $request->bearerToken();
        $user = null;
        $isAdmin = false;
        
        if ($token) {
            try {
                $user = \Laravel\Sanctum\PersonalAccessToken::findToken($token)?->tokenable;
                $isAdmin = $user && $user->hasRole('admin');
            } catch (Exception $e) {
                // Token invalid
            }
        }
        
        return response()->json([
            'has_bearer_token' => !empty($token),
            'token_preview' => $token ? substr($token, 0, 10) . '...' : null,
            'user_found' => !empty($user),
            'is_admin' => $isAdmin,
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'headers' => $request->headers->all()
        ]);
    });
});

// User Referral Routes (extending existing)
Route::middleware(['auth:sanctum'])->prefix('v1/referrals')->group(function () {
    // Enhanced referral dashboard
    Route::get('dashboard', [ReferralController::class, 'enhancedDashboard']);
});

// Public Referral Routes (available to all)
Route::prefix('v1/referrals')->group(function () {
    // Available tiers can be public as they don't contain sensitive info
    Route::get('available-tiers', [ReferralController::class, 'getAvailableTiers']);
    
    // Test endpoint για debugging (να αφαιρεθεί σε production)
    Route::get('test-dashboard/{userId}', function($userId) {
        $user = \App\Models\User::find($userId);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        
        $referralCode = \App\Models\ReferralCode::firstOrCreate(['user_id' => $user->id], ['user_id' => $user->id]);
        $totalReferrals = \App\Models\Referral::where('referrer_id', $user->id)->where('status', 'confirmed')->count();
        $nextTier = \App\Models\ReferralRewardTier::where('referrals_required', '>', $totalReferrals)
            ->where('is_active', true)
            ->orderBy('referrals_required', 'asc')
            ->first();
            
        return response()->json([
            'success' => true,
            'data' => [
                'user_name' => $user->name,
                'referral_code' => $referralCode->code,
                'referral_link' => "https://sweat24.obs.com.gr/invite/" . $referralCode->code,
                'total_referrals' => $totalReferrals,
                'next_tier' => $nextTier ? [
                    'name' => $nextTier->name,
                    'referrals_required' => $nextTier->referrals_required,
                    'reward_name' => $nextTier->reward_description ?? $nextTier->name,
                ] : null,
                'earned_rewards' => [],
                'referred_friends' => [],
                'tiers_count' => \App\Models\ReferralRewardTier::where('is_active', true)->count(),
            ]
        ]);
    });
});

// ============ ENHANCED STATISTICS ROUTES ============

// Admin Statistics (Protected)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('v1/admin/statistics')->group(function () {
    // Dashboard overview
    Route::get('dashboard', [\App\Http\Controllers\Api\StatisticsController::class, 'dashboard']);
    
    // Booking type statistics
    Route::get('booking-types', [\App\Http\Controllers\Api\StatisticsController::class, 'bookingTypes']);
    Route::get('monthly-trends', [\App\Http\Controllers\Api\StatisticsController::class, 'monthlyTrends']);
    
    // Reward system statistics
    Route::get('loyalty-program', [\App\Http\Controllers\Api\StatisticsController::class, 'loyaltyProgram']);
    Route::get('referral-program', [\App\Http\Controllers\Api\StatisticsController::class, 'referralProgram']);
    
    // Export functionality
    Route::get('export', [\App\Http\Controllers\Api\StatisticsController::class, 'export']);
});
// Debug endpoint to see exactly what the admin panel is sending
Route::any('/debug/admin-requests', function(Request $request) {
    return response()->json([
        'method' => $request->method(),
        'url' => $request->fullUrl(),
        'headers' => $request->headers->all(),
        'body' => $request->all(),
        'bearer_token' => $request->bearerToken(),
        'user' => $request->user() ? [
            'id' => $request->user()->id,
            'email' => $request->user()->email,
            'role' => $request->user()->role
        ] : null,
        'timestamp' => now()
    ]);
});

Route::prefix('v1')->group(function () {
    Route::get('debug/pusher', function(\Illuminate\Http\Request $request) {
        $message = $request->query('message', 'Hello from Laravel!');
        event(new \App\Events\TestPusherEvent($message));
        return response()->json(['ok' => true, 'sent' => $message]);
    });
});

// ============ PUBLIC ADMIN POINTS REWARDS ROUTES ============
// These routes are public to allow admin panel access without Sanctum authentication
Route::prefix('v1/admin/points')->group(function () {
    Route::apiResource('rewards', \App\Http\Controllers\Api\PointsRewardsController::class);
});
