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
use App\Http\Controllers\Api\ContactMessageController;

// Two-Phase Registration routes (public)
Route::prefix('v1/registration')->group(function () {
    Route::post('/initial', [RegistrationController::class, 'initialRegistration']);
    Route::post('/accept-terms', [RegistrationController::class, 'acceptTerms']);
    Route::post('/complete', [RegistrationController::class, 'completeRegistration']);
    Route::get('/status', [RegistrationController::class, 'getRegistrationStatus']);
});

// Admin-only registration management routes
Route::prefix('v1/admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('/users/{id}/approve', [RegistrationController::class, 'approveUser']);
    Route::post('/users/{id}/reject', [RegistrationController::class, 'rejectUser']);

    // User package payment update
    Route::patch('/users/{userId}/packages/{userPackageId}/payment', [\App\Http\Controllers\Admin\UserPackageController::class, 'updatePayment']);
});

// Admin Panel specific routes (simplified path as requested)
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/users/{id}/approve', [AdminController::class, 'approveUser']);
    Route::post('/users/{id}/reject', [AdminController::class, 'rejectUser']);
    Route::get('/users/{userId}/full-profile', [AdminController::class, 'getUserFullProfile']);
    Route::get('/users/{userId}/packages', [UserPackageController::class, 'userPackages']);
});

// Authentication routes (public)
Route::prefix('v1/auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/admin/login', [AuthController::class, 'adminLogin']);
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
    Route::get('fitness-classes/types/all', [\App\Http\Controllers\Api\FitnessClassesController::class, 'getTypes']);
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
    
    // Dashboard stats and activities moved to authenticated routes (see below)
});

// Remove temporary public booking routes - will add at end

// Protected routes (require authentication)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Users/Members
    Route::apiResource('users', UserController::class);
    Route::get('users/search/by-phone', [UserController::class, 'searchByPhone']);
    Route::get('users/{id}/referral-info', [UserController::class, 'getUserReferralInfo']);

    // Packages (Admin and Trainer management)
    Route::apiResource('packages', PackageController::class)->except(['index', 'show'])->middleware('role:admin,trainer');

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
        Route::get('/user/{userId}/partial-payments', [UserPackageController::class, 'userPartialPayments']);
        Route::get('/{userPackage}', [UserPackageController::class, 'show']);
        Route::post('/', [UserPackageController::class, 'store']);
        Route::put('/{userPackage}', [UserPackageController::class, 'update']);
        Route::post('/{userPackage}/freeze', [UserPackageController::class, 'freeze']);
        Route::post('/{userPackage}/unfreeze', [UserPackageController::class, 'unfreeze']);
        Route::post('/{userPackage}/renew', [UserPackageController::class, 'renew']);
        Route::post('/{userPackage}/send-notification', [UserPackageController::class, 'sendExpiryNotification']);
        Route::post("/{userPackage}/toggle-pause", [UserPackageController::class, "togglePause"]);
    });

    // Mobile App - Get authenticated user's partial payment summary
    Route::get('/my-partial-payments', [UserPackageController::class, 'myPartialPayments']);

    // Mobile App - Get authenticated user's package history (expired, cancelled, completed)
    Route::get('/my-packages/history', [UserPackageController::class, 'myPackagesHistory']);

    // Mobile App - Get authenticated user's active packages
    Route::get('/my-active-packages', [UserPackageController::class, 'myActivePackages']);

    // Custom Packages route alias (points to user-packages endpoint)
    Route::get('custom-packages/user/{userId}', [UserPackageController::class, 'userPackages']);
    
    Route::post('bookings/{booking}/check-in', [BookingController::class, 'checkIn']);
    Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel']);
    Route::post('bookings/{booking}/mark-absent', [BookingController::class, 'markAbsent']);
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

    // New Member Info routes (for clients)
    Route::get('new-member-info', [\App\Http\Controllers\Api\NewMemberInfoController::class, 'index']);
    Route::get('new-member-info/category/{category}', [\App\Http\Controllers\Api\NewMemberInfoController::class, 'getByCategory']);
    Route::get('new-member-info/{id}', [\App\Http\Controllers\Api\NewMemberInfoController::class, 'show']);

    // Employee Manual routes (for staff)
    Route::get('employee-manual', [\App\Http\Controllers\Api\EmployeeManualController::class, 'index']);
    Route::get('employee-manual/category/{category}', [\App\Http\Controllers\Api\EmployeeManualController::class, 'getByCategory']);
    Route::get('employee-manual/{id}', [\App\Http\Controllers\Api\EmployeeManualController::class, 'show']);

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
        Route::get('fitness-classes/{id}/participants', [\App\Http\Controllers\Api\FitnessClassesController::class, 'getParticipants']);
        Route::post('fitness-classes/{id}/attendance', [\App\Http\Controllers\Api\FitnessClassesController::class, 'markAttendance']);
        Route::post('fitness-classes/{id}/cancel', [\App\Http\Controllers\Api\FitnessClassesController::class, 'cancel']);
    });

    // Questionnaires (authenticated routes for admin/trainer)
    Route::middleware(['role:admin,trainer'])->group(function () {
        Route::apiResource('questionnaires', \App\Http\Controllers\Api\QuestionnaireController::class);
        Route::post('questionnaires/{questionnaire}/toggle-active', [\App\Http\Controllers\Api\QuestionnaireController::class, 'toggleActive']);

        // Questionnaire Responses (admin/trainer management)
        Route::get('questionnaire-responses', [\App\Http\Controllers\Api\QuestionnaireResponseController::class, 'index']);
        Route::get('questionnaire-responses/{questionnaireResponse}', [\App\Http\Controllers\Api\QuestionnaireResponseController::class, 'show']);
        Route::put('questionnaire-responses/{questionnaireResponse}', [\App\Http\Controllers\Api\QuestionnaireResponseController::class, 'update']);
        Route::delete('questionnaire-responses/{questionnaireResponse}', [\App\Http\Controllers\Api\QuestionnaireResponseController::class, 'destroy']);
        Route::get('questionnaires/{questionnaire}/responses', [\App\Http\Controllers\Api\QuestionnaireResponseController::class, 'getQuestionnaireResponses']);
    });

    // Questionnaire Response submission (any authenticated user can submit)
    Route::post('questionnaire-responses', [\App\Http\Controllers\Api\QuestionnaireResponseController::class, 'store']);

    // Contact Messages (any authenticated user can submit)
    Route::post('contact-messages', [ContactMessageController::class, 'store']);

    // Waitlist
    Route::get('my-waitlists', [WaitlistController::class, 'myWaitlists']);
    Route::post('classes/{class}/waitlist/join', [WaitlistController::class, 'join']);
    Route::delete('classes/{class}/waitlist/leave', [WaitlistController::class, 'leave']);
    Route::post('classes/{class}/waitlist/decline', [WaitlistController::class, 'decline']);
    Route::get('classes/{class}/waitlist/status', [WaitlistController::class, 'status']);
    Route::get('classes/{class}/waitlist', [WaitlistController::class, 'index'])->middleware('role:admin,trainer');
    Route::get("waitlists/summary", [WaitlistController::class, "summary"])->middleware("role:admin,trainer");
    
    // Financial Features (Admin and Trainer)
    Route::middleware(['role:admin,trainer'])->group(function () {
        Route::apiResource('payment-installments', PaymentInstallmentController::class);
        Route::post("payment-installments/{paymentInstallment}/pay", [PaymentInstallmentController::class, "markAsPaid"]);
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

    // Shift Checklists (opening/closing)
    Route::middleware(['role:admin,trainer'])->group(function () {
        Route::get('shift-checklists', [\App\Http\Controllers\ShiftChecklistController::class, 'index']);
        Route::post('shift-checklists', [\App\Http\Controllers\ShiftChecklistController::class, 'store']);
        Route::get('shift-checklists/check-required', [\App\Http\Controllers\ShiftChecklistController::class, 'checkRequired']);
        Route::get('shift-checklists/statistics', [\App\Http\Controllers\ShiftChecklistController::class, 'statistics']);
        Route::get('shift-checklists/user', [\App\Http\Controllers\ShiftChecklistController::class, 'userChecklists']);
        Route::get('shift-checklists/{shiftChecklist}', [\App\Http\Controllers\ShiftChecklistController::class, 'show']);
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

    });

    // Class Types Management (Admin and Trainer)
    Route::middleware(['role:admin,trainer'])->group(function () {
        Route::get('admin/class-types', [\App\Http\Controllers\Api\ClassTypesController::class, 'index']);
        Route::post('admin/class-types', [\App\Http\Controllers\Api\ClassTypesController::class, 'store']);
        Route::get('admin/class-types/{id}', [\App\Http\Controllers\Api\ClassTypesController::class, 'show']);
        Route::post('admin/class-types/{id}', [\App\Http\Controllers\Api\ClassTypesController::class, 'update']);
        Route::delete('admin/class-types/{id}', [\App\Http\Controllers\Api\ClassTypesController::class, 'destroy']);
        Route::post('admin/class-types/reorder', [\App\Http\Controllers\Api\ClassTypesController::class, 'reorder']);

        // Locations Management
        Route::get('admin/locations', [\App\Http\Controllers\Api\LocationsController::class, 'index']);
        Route::post('admin/locations', [\App\Http\Controllers\Api\LocationsController::class, 'store']);
        Route::get('admin/locations/{id}', [\App\Http\Controllers\Api\LocationsController::class, 'show']);
        Route::post('admin/locations/{id}', [\App\Http\Controllers\Api\LocationsController::class, 'update']);
        Route::delete('admin/locations/{id}', [\App\Http\Controllers\Api\LocationsController::class, 'destroy']);
    });

    // Admin only routes
    Route::middleware(['role:admin'])->group(function () {
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
        
        // Admin Progress Photos Routes (Delete only - admin only)
        Route::delete('admin/progress-photos/{id}', [\App\Http\Controllers\Api\ProgressPhotoController::class, 'adminDestroy']);
    });

    // Progress Photos and Measurements Routes (Admin and Trainer)
    Route::middleware(['role:admin,trainer'])->group(function () {
        Route::get('admin/users/{userId}/progress-photos', [\App\Http\Controllers\Api\ProgressPhotoController::class, 'getUserPhotos']);
        Route::get('admin/users/{userId}/measurements', [\App\Http\Controllers\Api\BodyMeasurementController::class, 'getUserMeasurements']);
        Route::get('admin/users/{userId}/measurements/latest', [\App\Http\Controllers\Api\BodyMeasurementController::class, 'getUserLatestMeasurement']);
    });

    // Continue Admin only routes
    Route::middleware(['role:admin'])->group(function () {

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
        
        // Admin Contact Messages Management
        Route::get('admin/contact-messages', [ContactMessageController::class, 'index']);
        Route::get('admin/contact-messages/stats', [ContactMessageController::class, 'stats']);
        Route::get('admin/contact-messages/{contactMessage}', [ContactMessageController::class, 'show']);
        Route::put('admin/contact-messages/{contactMessage}', [ContactMessageController::class, 'update']);
        Route::post('admin/contact-messages/{contactMessage}/reply', [ContactMessageController::class, 'reply']);
        Route::post('admin/contact-messages/{contactMessage}/archive', [ContactMessageController::class, 'archive']);
        Route::delete('admin/contact-messages/{contactMessage}', [ContactMessageController::class, 'destroy']);
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
    
    // Dashboard stats (role-based) - delegated to DashboardController
    Route::get('dashboard/stats', [\App\Http\Controllers\Api\DashboardController::class, 'stats']);
    
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
        Route::post('/doctor-certificate', [MedicalHistoryController::class, 'uploadDoctorCertificate']);
        Route::delete('/doctor-certificate', [MedicalHistoryController::class, 'deleteDoctorCertificate']);
    });

    // ============ FITNESS MANAGEMENT SYSTEM ROUTES ============

    // Fitness Levels Routes (Admin & Trainer)
    Route::prefix('fitness-levels')->middleware(['role:admin,trainer'])->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\FitnessLevelsController::class, 'index']);
        Route::get('/user/{userId}', [\App\Http\Controllers\Api\FitnessLevelsController::class, 'index']);
        Route::get('/user/{userId}/current', [\App\Http\Controllers\Api\FitnessLevelsController::class, 'current']);
        Route::get('/user/{userId}/history', [\App\Http\Controllers\Api\FitnessLevelsController::class, 'history']);
        Route::post('/', [\App\Http\Controllers\Api\FitnessLevelsController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\Api\FitnessLevelsController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\Api\FitnessLevelsController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\FitnessLevelsController::class, 'destroy']);
    });

    // Performance Tests Routes (Admin & Trainer)
    Route::prefix('performance-tests')->middleware(['role:admin,trainer'])->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\PerformanceTestsController::class, 'index']);
        Route::get('/user/{userId}', [\App\Http\Controllers\Api\PerformanceTestsController::class, 'index']);
        Route::get('/user/{userId}/progress/{exerciseName}', [\App\Http\Controllers\Api\PerformanceTestsController::class, 'progress']);
        Route::get('/user/{userId}/analytics', [\App\Http\Controllers\Api\PerformanceTestsController::class, 'analytics']);
        Route::post('/', [\App\Http\Controllers\Api\PerformanceTestsController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\Api\PerformanceTestsController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\Api\PerformanceTestsController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\PerformanceTestsController::class, 'destroy']);
    });

    // Exercise Library Routes
    Route::prefix('exercises')->group(function () {
        // Public routes for viewing exercises
        Route::get('/', [\App\Http\Controllers\Api\ExercisesController::class, 'index']);
        Route::get('/muscle-groups', [\App\Http\Controllers\Api\ExercisesController::class, 'muscleGroups']);
        Route::get('/categories', [\App\Http\Controllers\Api\ExercisesController::class, 'categories']);
        Route::get('/equipment', [\App\Http\Controllers\Api\ExercisesController::class, 'equipment']);
        Route::get('/{id}', [\App\Http\Controllers\Api\ExercisesController::class, 'show']);

        // Admin/Trainer routes for managing exercises
        Route::middleware(['role:admin,trainer'])->group(function () {
            Route::post('/', [\App\Http\Controllers\Api\ExercisesController::class, 'store']);
            Route::put('/{id}', [\App\Http\Controllers\Api\ExercisesController::class, 'update']);
            Route::delete('/{id}', [\App\Http\Controllers\Api\ExercisesController::class, 'destroy']);
        });
    });

    // Exercise Muscle Groups Routes
    Route::prefix('exercise-muscle-groups')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\ExerciseMuscleGroupController::class, 'index']);
        Route::get('/active', [\App\Http\Controllers\Api\ExerciseMuscleGroupController::class, 'active']);

        // Admin/Trainer routes for managing muscle groups
        Route::middleware(['role:admin,trainer'])->group(function () {
            Route::post('/', [\App\Http\Controllers\Api\ExerciseMuscleGroupController::class, 'store']);
            Route::put('/{muscleGroup}', [\App\Http\Controllers\Api\ExerciseMuscleGroupController::class, 'update']);
            Route::delete('/{muscleGroup}', [\App\Http\Controllers\Api\ExerciseMuscleGroupController::class, 'destroy']);
        });
    });

    // Exercise Categories Routes
    Route::prefix('exercise-categories')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\ExerciseCategoryController::class, 'index']);
        Route::get('/active', [\App\Http\Controllers\Api\ExerciseCategoryController::class, 'active']);

        // Admin/Trainer routes for managing categories
        Route::middleware(['role:admin,trainer'])->group(function () {
            Route::post('/', [\App\Http\Controllers\Api\ExerciseCategoryController::class, 'store']);
            Route::put('/{category}', [\App\Http\Controllers\Api\ExerciseCategoryController::class, 'update']);
            Route::delete('/{category}', [\App\Http\Controllers\Api\ExerciseCategoryController::class, 'destroy']);
        });
    });

    // Exercise Equipment Routes
    Route::prefix('exercise-equipment')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\ExerciseEquipmentController::class, 'index']);
        Route::get('/active', [\App\Http\Controllers\Api\ExerciseEquipmentController::class, 'active']);

        // Admin/Trainer routes for managing equipment
        Route::middleware(['role:admin,trainer'])->group(function () {
            Route::post('/', [\App\Http\Controllers\Api\ExerciseEquipmentController::class, 'store']);
            Route::get('/{equipment}', [\App\Http\Controllers\Api\ExerciseEquipmentController::class, 'show']);
            Route::put('/{equipment}', [\App\Http\Controllers\Api\ExerciseEquipmentController::class, 'update']);
            Route::delete('/{equipment}', [\App\Http\Controllers\Api\ExerciseEquipmentController::class, 'destroy']);
        });
    });

    // Training Sessions Routes (Admin & Trainer)
    Route::prefix('training-sessions')->middleware(['role:admin,trainer'])->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\TrainingSessionsController::class, 'index']);
        Route::get('/user/{userId}', [\App\Http\Controllers\Api\TrainingSessionsController::class, 'index']);
        Route::get('/user/{userId}/analytics', [\App\Http\Controllers\Api\TrainingSessionsController::class, 'analytics']);
        Route::get('/user/{userId}/enhanced-analytics', [\App\Http\Controllers\Api\TrainingSessionsController::class, 'enhancedAnalytics']);
        Route::get('/user/{userId}/alerts', [\App\Http\Controllers\Api\TrainingSessionsController::class, 'alerts']);
        Route::post('/', [\App\Http\Controllers\Api\TrainingSessionsController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\Api\TrainingSessionsController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\Api\TrainingSessionsController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\TrainingSessionsController::class, 'destroy']);
    });

    // Body Measurements Routes (Admin & Trainer)
    Route::prefix('body-measurements')->middleware(['role:admin,trainer'])->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\BodyMeasurementsController::class, 'index']);
        Route::get('/user/{userId}', [\App\Http\Controllers\Api\BodyMeasurementsController::class, 'index']);
        Route::get('/user/{userId}/latest', [\App\Http\Controllers\Api\BodyMeasurementsController::class, 'latest']);
        Route::get('/user/{userId}/trends', [\App\Http\Controllers\Api\BodyMeasurementsController::class, 'trends']);
        Route::post('/user/{userId}/compare', [\App\Http\Controllers\Api\BodyMeasurementsController::class, 'compare']);
        Route::post('/', [\App\Http\Controllers\Api\BodyMeasurementsController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\Api\BodyMeasurementsController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\Api\BodyMeasurementsController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\BodyMeasurementsController::class, 'destroy']);
    });


    // Admin Chat Management routes (Admin and Trainer)
    Route::prefix('admin/chat')->middleware(['role:admin,trainer'])->group(function () {
        Route::get('/conversations', [AdminChatController::class, 'getConversations']);
        Route::post('/conversations', [AdminChatController::class, 'createConversation']);
        Route::post('/messages', [AdminChatController::class, 'sendMessage']);
        Route::put('/conversations/{conversation}/read', [AdminChatController::class, 'markAsRead']);
        Route::put('/conversations/{conversation}/status', [AdminChatController::class, 'updateStatus']);
    });

    // Owner Notifications routes (Admin and Trainer)
    Route::prefix('owner-notifications')->middleware(['role:admin,trainer'])->group(function () {
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
        Route::post('upload', [TeamChatController::class, 'uploadFile']);
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

// Points API Routes - moved to authenticated block below (were incorrectly public)

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

// Debug/test endpoints removed for security

// Booking routes - secured with optional auth (allows both authenticated and user_id param)
Route::prefix('v1')->group(function () {
    Route::get('bookings', [BookingController::class, 'index'])->name('public.bookings.index');
    Route::post('bookings', [BookingController::class, 'store'])->name('public.bookings.store');
    Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('public.bookings.cancel');
});

// Add policy endpoint under v1 prefix for client app
Route::prefix('v1')->group(function () {
    Route::get('test-policy/{booking_id}', [CancellationPolicyController::class, 'testPolicy'])->name('public.test.policy');
    
    // Test endpoints for cancellation policies (development only)
    Route::prefix('admin/cancellation-policies')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('test-data', [CancellationPolicyController::class, 'seedTestData']);
        Route::delete('test-data', [CancellationPolicyController::class, 'clearTestData']);
    });
});

// Test routes removed for security

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
    // New Member Info Management (for clients)
    Route::post('new-member-info', [\App\Http\Controllers\Api\NewMemberInfoController::class, 'store']);
    Route::put('new-member-info/{id}', [\App\Http\Controllers\Api\NewMemberInfoController::class, 'update']);
    Route::delete('new-member-info/{id}', [\App\Http\Controllers\Api\NewMemberInfoController::class, 'destroy']);

    // Employee Manual Management (for staff)
    Route::post('employee-manual', [\App\Http\Controllers\Api\EmployeeManualController::class, 'store']);
    Route::put('employee-manual/{id}', [\App\Http\Controllers\Api\EmployeeManualController::class, 'update']);
    Route::delete('employee-manual/{id}', [\App\Http\Controllers\Api\EmployeeManualController::class, 'destroy']);

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

// ============ WORK SESSIONS / TIME TRACKING ROUTES ============

// Trainer Work Sessions (Protected - for trainers and admins)
Route::middleware(['auth:sanctum'])->prefix('v1/work-sessions')->group(function () {
    // Get current user's sessions
    Route::get('/', [\App\Http\Controllers\Api\WorkSessionController::class, 'index']);
    Route::get('/current', [\App\Http\Controllers\Api\WorkSessionController::class, 'getCurrentSession']);
    Route::get('/today', [\App\Http\Controllers\Api\WorkSessionController::class, 'todaySummary']);

    // Clock in/out
    Route::post('/clock-in', [\App\Http\Controllers\Api\WorkSessionController::class, 'clockIn']);
    Route::post('/clock-out', [\App\Http\Controllers\Api\WorkSessionController::class, 'clockOut']);

    // Edit own sessions
    Route::put('/{id}', [\App\Http\Controllers\Api\WorkSessionController::class, 'update']);
    Route::delete('/{id}', [\App\Http\Controllers\Api\WorkSessionController::class, 'destroy']);
});

// Admin Work Session Management (Protected - admin only)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('v1/admin/work-sessions')->group(function () {
    // View all trainer sessions
    Route::get('/', [\App\Http\Controllers\Api\WorkSessionController::class, 'adminIndex']);
    Route::get('/summary', [\App\Http\Controllers\Api\WorkSessionController::class, 'adminSummary']);
    Route::get('/trainer/{userId}', [\App\Http\Controllers\Api\WorkSessionController::class, 'adminTrainerDetail']);

    // Admin can edit/delete any session
    Route::put('/{id}', [\App\Http\Controllers\Api\WorkSessionController::class, 'adminUpdate']);
    Route::delete('/{id}', [\App\Http\Controllers\Api\WorkSessionController::class, 'adminDestroy']);
});

// Public test endpoints removed for security

// User Referral Routes (extending existing)
Route::middleware(['auth:sanctum'])->prefix('v1/referrals')->group(function () {
    // Enhanced referral dashboard
    Route::get('dashboard', [ReferralController::class, 'enhancedDashboard']);
});

// Public Referral Routes (available to all)
Route::prefix('v1/referrals')->group(function () {
    // Available tiers can be public as they don't contain sensitive info
    Route::get('available-tiers', [ReferralController::class, 'getAvailableTiers']);
    
    // Test dashboard endpoint removed for security
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
// Debug endpoints removed for security

// Admin Points Rewards Routes (secured)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('v1/admin/points')->group(function () {
    Route::apiResource('rewards', \App\Http\Controllers\Api\PointsRewardsController::class);
});

// ============ PAYROLL AGREEMENTS ROUTES ============

// Admin Payroll Agreements Management (Protected)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('v1/admin/payroll-agreements')->group(function () {
    // List all agreements
    Route::get('/', [\App\Http\Controllers\Api\PayrollAgreementController::class, 'index']);

    // Create new agreement
    Route::post('/', [\App\Http\Controllers\Api\PayrollAgreementController::class, 'store']);

    // Get summary for instructor
    Route::get('/instructor/{instructorId}/summary', [\App\Http\Controllers\Api\PayrollAgreementController::class, 'summary']);

    // Update agreement
    Route::put('/{payrollAgreement}', [\App\Http\Controllers\Api\PayrollAgreementController::class, 'update']);

    // Toggle active status
    Route::post('/{payrollAgreement}/toggle-active', [\App\Http\Controllers\Api\PayrollAgreementController::class, 'toggleActive']);

    // Delete agreement
    Route::delete('/{payrollAgreement}', [\App\Http\Controllers\Api\PayrollAgreementController::class, 'destroy']);
});

// ============ CHURN FEEDBACK ROUTES ============

// Mobile App churn feedback routes (secured)
Route::middleware(['auth:sanctum'])->prefix('v1/churn-feedback')->group(function () {
    Route::get('/pending', [\App\Http\Controllers\Api\ChurnFeedbackController::class, 'getPendingSurvey']);
    Route::post('/quick-response', [\App\Http\Controllers\Api\ChurnFeedbackController::class, 'submitQuickResponse']);
    Route::post('/mini-survey', [\App\Http\Controllers\Api\ChurnFeedbackController::class, 'submitMiniSurvey']);
    Route::post('/opt-out', [\App\Http\Controllers\Api\ChurnFeedbackController::class, 'optOut']);
});

// Admin Churn Feedback Management (Protected)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('v1/admin/churn-feedback')->group(function () {
    // List all feedback
    Route::get('/', [\App\Http\Controllers\Api\ChurnFeedbackController::class, 'index']);

    // Trigger churn feedback for user manually
    Route::post('/trigger', [\App\Http\Controllers\Api\ChurnFeedbackController::class, 'triggerForUser']);

    // Get single feedback detail
    Route::get('/{churnFeedback}', [\App\Http\Controllers\Api\ChurnFeedbackController::class, 'show']);

    // Analytics & Statistics
    Route::get('/analytics/summary', [\App\Http\Controllers\Api\ChurnFeedbackController::class, 'analytics']);
});

// ============ WELLNESS SCORE ROUTES ============

// Mobile App Wellness Routes (secured)
Route::middleware(['auth:sanctum'])->prefix('v1/wellness')->group(function () {
    Route::get('/today', [\App\Http\Controllers\Api\WellnessScoreController::class, 'getToday']);
    Route::post('/submit', [\App\Http\Controllers\Api\WellnessScoreController::class, 'submit']);
    Route::get('/history', [\App\Http\Controllers\Api\WellnessScoreController::class, 'getHistory']);
    Route::get('/thresholds', [\App\Http\Controllers\Api\WellnessScoreController::class, 'getThresholds']);
});

// Admin Wellness Management (Protected)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('v1/admin/wellness')->group(function () {
    // List all wellness scores with filters
    Route::get('/', [\App\Http\Controllers\Api\WellnessScoreController::class, 'adminIndex']);

    // Get users missing today's submission
    Route::get('/missing', [\App\Http\Controllers\Api\WellnessScoreController::class, 'getMissingSubmissions']);

    // Get users with alerts (orange/red)
    Route::get('/alerts', [\App\Http\Controllers\Api\WellnessScoreController::class, 'getUsersWithAlerts']);

    // Get single user's wellness detail
    Route::get('/user/{userId}', [\App\Http\Controllers\Api\WellnessScoreController::class, 'getUserWellness']);

    // Threshold management
    Route::get('/thresholds', [\App\Http\Controllers\Api\WellnessScoreController::class, 'getThresholdsAdmin']);
    Route::put('/thresholds/{threshold}', [\App\Http\Controllers\Api\WellnessScoreController::class, 'updateThreshold']);

    // Analytics
    Route::get('/analytics', [\App\Http\Controllers\Api\WellnessScoreController::class, 'analytics']);
});

// ============ COMPREHENSIVE ANALYTICS ROUTES ============

// Admin Analytics Dashboard (Protected)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('v1/admin/analytics')->group(function () {
    // Dashboard overview
    Route::get('/dashboard', [\App\Http\Controllers\Api\AnalyticsController::class, 'dashboard']);

    // Marketing source analytics (how customers found us)
    Route::get('/marketing', [\App\Http\Controllers\Api\AnalyticsController::class, 'marketingSource']);

    // Attendance statistics by service/day/week/month
    Route::get('/attendance', [\App\Http\Controllers\Api\AnalyticsController::class, 'attendanceStats']);

    // Group class capacity analytics (occupancy, cancellation rates)
    Route::get('/capacity', [\App\Http\Controllers\Api\AnalyticsController::class, 'classCapacity']);

    // Package usage statistics
    Route::get('/packages', [\App\Http\Controllers\Api\AnalyticsController::class, 'packageUsage']);

    // Demographics analytics (gender, age groups)
    Route::get('/demographics', [\App\Http\Controllers\Api\AnalyticsController::class, 'demographics']);

    // Service type distribution (EMS, Pilates, Personal, Group, Functional)
    Route::get('/services', [\App\Http\Controllers\Api\AnalyticsController::class, 'serviceDistribution']);

    // Trainer ratings analytics
    Route::get('/ratings', [\App\Http\Controllers\Api\AnalyticsController::class, 'trainerRatings']);

    // Churn & retention analytics
    Route::get('/retention', [\App\Http\Controllers\Api\AnalyticsController::class, 'retentionAnalytics']);

    // Trial conversion analytics
    Route::get('/trials', [\App\Http\Controllers\Api\AnalyticsController::class, 'trialConversion']);

    // Location-based analytics (per gym)
    Route::get('/locations', [\App\Http\Controllers\Api\AnalyticsController::class, 'locationAnalytics']);
});
