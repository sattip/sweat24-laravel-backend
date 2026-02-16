<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\TimeTrackingController;
use App\Http\Controllers\ImageUploadController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TeamChatController;
use App\Http\Controllers\Api\ContactMessageController;
use App\Http\Controllers\AdminChatController;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

// Public evaluation routes (anonymous access)
Route::prefix('v1/evaluations')->group(function () {
    Route::get('/{token}', [EvaluationController::class, 'getByToken']);
    Route::post('/{token}/submit', [EvaluationController::class, 'submit']);
});

// Public events routes
Route::prefix('v1')->group(function () {
    Route::get('events', [EventController::class, 'index']);
});

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
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

    // Shift Checklists (opening/closing)
    Route::middleware(['role:admin,trainer'])->group(function () {
        Route::get('shift-checklists', [\App\Http\Controllers\ShiftChecklistController::class, 'index']);
        Route::post('shift-checklists', [\App\Http\Controllers\ShiftChecklistController::class, 'store']);
        Route::get('shift-checklists/check-required', [\App\Http\Controllers\ShiftChecklistController::class, 'checkRequired']);
        Route::get('shift-checklists/statistics', [\App\Http\Controllers\ShiftChecklistController::class, 'statistics']);
        Route::get('shift-checklists/user', [\App\Http\Controllers\ShiftChecklistController::class, 'userChecklists']);
        Route::get('shift-checklists/{shiftChecklist}', [\App\Http\Controllers\ShiftChecklistController::class, 'show']);
    });

    // Admin Store Product Management
    Route::middleware(['role:admin'])->group(function () {
        Route::get('admin/store/products', [\App\Http\Controllers\StoreProductController::class, 'adminIndex']);
        Route::post('admin/store/products', [\App\Http\Controllers\StoreProductController::class, 'store']);
        Route::put('admin/store/products/{id}', [\App\Http\Controllers\StoreProductController::class, 'update']);
        Route::delete('admin/store/products/{id}', [\App\Http\Controllers\StoreProductController::class, 'destroy']);
        Route::post('admin/store/upload-image', [ImageUploadController::class, 'uploadProductImage']);
    });

    // Admin only routes
    Route::middleware(['role:admin'])->group(function () {
        // Admin Events Management
        Route::get('admin/events', [EventController::class, 'adminIndex']);
        Route::get('admin/event-rsvps', [EventController::class, 'adminGetAllRsvps']);

        Route::post('events', [EventController::class, 'store']);
        Route::put('events/{event}', [EventController::class, 'update']);
        Route::delete('events/{event}', [EventController::class, 'destroy']);

        // Admin Contact Messages Management
        Route::get('admin/contact-messages', [ContactMessageController::class, 'index']);
        Route::get('admin/contact-messages/stats', [ContactMessageController::class, 'stats']);
        Route::get('admin/contact-messages/{contactMessage}', [ContactMessageController::class, 'show']);
        Route::put('admin/contact-messages/{contactMessage}', [ContactMessageController::class, 'update']);
        Route::post('admin/contact-messages/{contactMessage}/reply', [ContactMessageController::class, 'reply']);
        Route::post('admin/contact-messages/{contactMessage}/archive', [ContactMessageController::class, 'archive']);
        Route::delete('admin/contact-messages/{contactMessage}', [ContactMessageController::class, 'destroy']);

        // Priority Booking Settings (Admin only)
        Route::get('admin/priority-booking-settings', [\App\Http\Controllers\Api\PriorityBookingSettingsController::class, 'index']);
        Route::put('admin/priority-booking-settings', [\App\Http\Controllers\Api\PriorityBookingSettingsController::class, 'update']);
    });

    // Evaluation routes (authenticated)
    Route::middleware(['role:admin,trainer'])->group(function () {
        Route::post('classes/{class}/evaluations/create', [EvaluationController::class, 'createEvaluationForCompletedClass']);
        Route::get('classes/{class}/evaluations/stats', [EvaluationController::class, 'classStats']);
        Route::get('instructors/{instructor}/evaluations/stats', [EvaluationController::class, 'instructorStats']);
        Route::get('evaluations/pending-count', [EvaluationController::class, 'pendingCount']);
    });

    // Admin Chat Management routes (Admin and Trainer)
    Route::prefix('admin/chat')->middleware(['role:admin,trainer'])->group(function () {
        Route::get('/conversations', [AdminChatController::class, 'getConversations']);
        Route::post('/conversations', [AdminChatController::class, 'createConversation']);
        Route::post('/messages', [AdminChatController::class, 'sendMessage']);
        Route::put('/conversations/{conversation}/read', [AdminChatController::class, 'markAsRead']);
        Route::put('/conversations/{conversation}/status', [AdminChatController::class, 'updateStatus']);
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
});

// Admin Loyalty/Info Management (Protected)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('v1/admin')->group(function () {
    // New Member Info Management (for clients)
    Route::post('new-member-info', [\App\Http\Controllers\Api\NewMemberInfoController::class, 'store']);
    Route::put('new-member-info/{id}', [\App\Http\Controllers\Api\NewMemberInfoController::class, 'update']);
    Route::delete('new-member-info/{id}', [\App\Http\Controllers\Api\NewMemberInfoController::class, 'destroy']);

    // Employee Manual Management (for staff)
    Route::post('employee-manual', [\App\Http\Controllers\Api\EmployeeManualController::class, 'store']);
    Route::put('employee-manual/{id}', [\App\Http\Controllers\Api\EmployeeManualController::class, 'update']);
    Route::delete('employee-manual/{id}', [\App\Http\Controllers\Api\EmployeeManualController::class, 'destroy']);
});

// Work Sessions / Time Tracking Routes
Route::middleware(['auth:sanctum'])->prefix('v1/work-sessions')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\WorkSessionController::class, 'index']);
    Route::get('/current', [\App\Http\Controllers\Api\WorkSessionController::class, 'getCurrentSession']);
    Route::get('/today', [\App\Http\Controllers\Api\WorkSessionController::class, 'todaySummary']);
    Route::post('/clock-in', [\App\Http\Controllers\Api\WorkSessionController::class, 'clockIn']);
    Route::post('/clock-out', [\App\Http\Controllers\Api\WorkSessionController::class, 'clockOut']);
    Route::put('/{id}', [\App\Http\Controllers\Api\WorkSessionController::class, 'update']);
    Route::delete('/{id}', [\App\Http\Controllers\Api\WorkSessionController::class, 'destroy']);
});

// Admin Work Session Management (Protected - admin only)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('v1/admin/work-sessions')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\WorkSessionController::class, 'adminIndex']);
    Route::get('/summary', [\App\Http\Controllers\Api\WorkSessionController::class, 'adminSummary']);
    Route::get('/trainer/{userId}', [\App\Http\Controllers\Api\WorkSessionController::class, 'adminTrainerDetail']);
    Route::put('/{id}', [\App\Http\Controllers\Api\WorkSessionController::class, 'adminUpdate']);
    Route::delete('/{id}', [\App\Http\Controllers\Api\WorkSessionController::class, 'adminDestroy']);
});

// Churn Feedback Routes
Route::prefix('v1/churn-feedback')->group(function () {
    Route::get('/pending', [\App\Http\Controllers\Api\ChurnFeedbackController::class, 'getPendingSurvey']);
    Route::post('/quick-response', [\App\Http\Controllers\Api\ChurnFeedbackController::class, 'submitQuickResponse']);
    Route::post('/mini-survey', [\App\Http\Controllers\Api\ChurnFeedbackController::class, 'submitMiniSurvey']);
    Route::post('/opt-out', [\App\Http\Controllers\Api\ChurnFeedbackController::class, 'optOut']);
});

// Admin Churn Feedback Management (Protected)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('v1/admin/churn-feedback')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\ChurnFeedbackController::class, 'index']);
    Route::post('/trigger', [\App\Http\Controllers\Api\ChurnFeedbackController::class, 'triggerForUser']);
    Route::get('/{churnFeedback}', [\App\Http\Controllers\Api\ChurnFeedbackController::class, 'show']);
    Route::get('/analytics/summary', [\App\Http\Controllers\Api\ChurnFeedbackController::class, 'analytics']);
});

// Admin Statistics & Analytics (Protected)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('v1/admin/statistics')->group(function () {
    Route::get('dashboard', [\App\Http\Controllers\Api\StatisticsController::class, 'dashboard']);
    Route::get('booking-types', [\App\Http\Controllers\Api\StatisticsController::class, 'bookingTypes']);
    Route::get('monthly-trends', [\App\Http\Controllers\Api\StatisticsController::class, 'monthlyTrends']);
    Route::get('loyalty-program', [\App\Http\Controllers\Api\StatisticsController::class, 'loyaltyProgram']);
    Route::get('referral-program', [\App\Http\Controllers\Api\StatisticsController::class, 'referralProgram']);
    Route::get('export', [\App\Http\Controllers\Api\StatisticsController::class, 'export']);
});

// Comprehensive Analytics Dashboard (Protected)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('v1/admin/analytics')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Api\AnalyticsController::class, 'dashboard']);
    Route::get('/marketing', [\App\Http\Controllers\Api\AnalyticsController::class, 'marketingSource']);
    Route::get('/attendance', [\App\Http\Controllers\Api\AnalyticsController::class, 'attendanceStats']);
    Route::get('/capacity', [\App\Http\Controllers\Api\AnalyticsController::class, 'classCapacity']);
    Route::get('/packages', [\App\Http\Controllers\Api\AnalyticsController::class, 'packageUsage']);
    Route::get('/demographics', [\App\Http\Controllers\Api\AnalyticsController::class, 'demographics']);
    Route::get('/services', [\App\Http\Controllers\Api\AnalyticsController::class, 'serviceDistribution']);
    Route::get('/ratings', [\App\Http\Controllers\Api\AnalyticsController::class, 'trainerRatings']);
    Route::get('/retention', [\App\Http\Controllers\Api\AnalyticsController::class, 'retentionAnalytics']);
    Route::get('/trials', [\App\Http\Controllers\Api\AnalyticsController::class, 'trialConversion']);
    Route::get('/locations', [\App\Http\Controllers\Api\AnalyticsController::class, 'locationAnalytics']);
});
