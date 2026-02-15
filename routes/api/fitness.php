<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MedicalHistoryController;

/*
|--------------------------------------------------------------------------
| Fitness Management System Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
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
        Route::get('/', [\App\Http\Controllers\Api\ExercisesController::class, 'index']);
        Route::get('/muscle-groups', [\App\Http\Controllers\Api\ExercisesController::class, 'muscleGroups']);
        Route::get('/categories', [\App\Http\Controllers\Api\ExercisesController::class, 'categories']);
        Route::get('/equipment', [\App\Http\Controllers\Api\ExercisesController::class, 'equipment']);
        Route::get('/{id}', [\App\Http\Controllers\Api\ExercisesController::class, 'show']);

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

    // Progress Photos and Measurements Routes (Admin and Trainer)
    Route::middleware(['role:admin,trainer'])->group(function () {
        Route::get('admin/users/{userId}/progress-photos', [\App\Http\Controllers\Api\ProgressPhotoController::class, 'getUserPhotos']);
        Route::get('admin/users/{userId}/measurements', [\App\Http\Controllers\Api\BodyMeasurementController::class, 'getUserMeasurements']);
        Route::get('admin/users/{userId}/measurements/latest', [\App\Http\Controllers\Api\BodyMeasurementController::class, 'getUserLatestMeasurement']);
    });

    // Admin Progress Photos Routes (Delete only - admin only)
    Route::middleware(['role:admin'])->group(function () {
        Route::delete('admin/progress-photos/{id}', [\App\Http\Controllers\Api\ProgressPhotoController::class, 'adminDestroy']);
    });
});

// Wellness Score Routes
Route::prefix('v1/wellness')->group(function () {
    Route::get('/today', [\App\Http\Controllers\Api\WellnessScoreController::class, 'getToday']);
    Route::post('/submit', [\App\Http\Controllers\Api\WellnessScoreController::class, 'submit']);
    Route::get('/history', [\App\Http\Controllers\Api\WellnessScoreController::class, 'getHistory']);
    Route::get('/thresholds', [\App\Http\Controllers\Api\WellnessScoreController::class, 'getThresholds']);
});

// Admin Wellness Management (Protected)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('v1/admin/wellness')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\WellnessScoreController::class, 'adminIndex']);
    Route::get('/missing', [\App\Http\Controllers\Api\WellnessScoreController::class, 'getMissingSubmissions']);
    Route::get('/alerts', [\App\Http\Controllers\Api\WellnessScoreController::class, 'getUsersWithAlerts']);
    Route::get('/user/{userId}', [\App\Http\Controllers\Api\WellnessScoreController::class, 'getUserWellness']);
    Route::get('/thresholds', [\App\Http\Controllers\Api\WellnessScoreController::class, 'getThresholdsAdmin']);
    Route::put('/thresholds/{threshold}', [\App\Http\Controllers\Api\WellnessScoreController::class, 'updateThreshold']);
    Route::get('/analytics', [\App\Http\Controllers\Api\WellnessScoreController::class, 'analytics']);
});
