<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GymClassController;
use App\Http\Controllers\InstructorController;

/*
|--------------------------------------------------------------------------
| Class & Instructor Routes
|--------------------------------------------------------------------------
*/

// Public classes routes (for browsing without authentication)
Route::prefix('v1')->group(function () {
    Route::get('classes', [GymClassController::class, 'index']);
    Route::get('classes/{class}', [GymClassController::class, 'show']);

    // Public trainer routes
    Route::get('trainers', [\App\Http\Controllers\TrainerController::class, 'apiIndex']);
    Route::get('trainers/{id}', [\App\Http\Controllers\TrainerController::class, 'apiShow']);

    // Public instructor routes
    Route::get('instructors', [InstructorController::class, 'index']);
    Route::get('instructors/{instructor}', [InstructorController::class, 'show']);

    // Public class types routes
    Route::get('class-types', [\App\Http\Controllers\Api\ClassTypesController::class, 'index']);

    // Public fitness classes routes
    Route::get('fitness-classes', [\App\Http\Controllers\Api\FitnessClassesController::class, 'index']);
    Route::get('fitness-classes/types/all', [\App\Http\Controllers\Api\FitnessClassesController::class, 'getTypes']);
    Route::get('fitness-classes/{id}', [\App\Http\Controllers\Api\FitnessClassesController::class, 'show']);
});

// Protected class routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Instructors/Trainers (admin-only create/update/delete)
    Route::middleware('role:admin')->group(function () {
        Route::post('instructors', [InstructorController::class, 'store']);
        Route::put('instructors/{instructor}', [InstructorController::class, 'update']);
        Route::patch('instructors/{instructor}', [InstructorController::class, 'update']);
        Route::delete('instructors/{instructor}', [InstructorController::class, 'destroy']);
    });

    // Classes (Admin and Trainer only - create/update/delete)
    Route::middleware('role:admin,trainer')->group(function () {
        Route::post('classes', [GymClassController::class, 'store']);
        Route::put('classes/{class}', [GymClassController::class, 'update']);
        Route::delete('classes/{class}', [GymClassController::class, 'destroy']);
    });

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
    });

    // Class Types Management (Admin and Trainer)
    Route::middleware(['role:admin,trainer'])->group(function () {
        Route::get('admin/class-types', [\App\Http\Controllers\Api\ClassTypesController::class, 'index']);
        Route::post('admin/class-types', [\App\Http\Controllers\Api\ClassTypesController::class, 'store']);
        Route::get('admin/class-types/{id}', [\App\Http\Controllers\Api\ClassTypesController::class, 'show']);
        Route::post('admin/class-types/{id}', [\App\Http\Controllers\Api\ClassTypesController::class, 'update']);
        Route::delete('admin/class-types/{id}', [\App\Http\Controllers\Api\ClassTypesController::class, 'destroy']);
        Route::post('admin/class-types/reorder', [\App\Http\Controllers\Api\ClassTypesController::class, 'reorder']);
    });
});
