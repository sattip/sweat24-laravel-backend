<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExercisesController extends Controller
{
    /**
     * Get all exercises with optional filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Exercise::query();

        // Filter by active status
        if ($request->has('active_only') && $request->active_only) {
            $query->active();
        }

        // Filter by muscle group
        if ($request->has('muscle_group')) {
            $query->byMuscleGroup($request->muscle_group);
        }

        // Filter by category
        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        // Filter by difficulty
        if ($request->has('difficulty')) {
            $query->byDifficulty($request->difficulty);
        }

        // Filter by equipment
        if ($request->has('equipment')) {
            $query->byEquipment($request->equipment);
        }

        // Search by name
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Order by
        $orderBy = $request->get('order_by', 'name_gr');
        $orderDirection = $request->get('order_direction', 'asc');
        $query->orderBy($orderBy, $orderDirection);

        $exercises = $query->get();

        return response()->json([
            'success' => true,
            'data' => $exercises
        ]);
    }

    /**
     * Store a new exercise.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name_en' => 'required|string|max:255',
            'name_gr' => 'required|string|max:255',
            'muscle_group' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'equipment' => 'nullable|array',
            'equipment.*' => 'string',
            'difficulty_level' => 'required|in:beginner,intermediate,advanced',
            'description' => 'nullable|string|max:2000',
            'video_url' => 'nullable|url',
            'image_url' => 'nullable|url',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $exercise = Exercise::create([
            'name_en' => $request->name_en,
            'name_gr' => $request->name_gr,
            'muscle_group' => $request->muscle_group,
            'category' => $request->category,
            'equipment' => $request->equipment ?? [],
            'difficulty_level' => $request->difficulty_level,
            'description' => $request->description,
            'video_url' => $request->video_url,
            'image_url' => $request->image_url,
            'is_active' => $request->is_active ?? true,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Exercise created successfully',
            'data' => $exercise
        ], 201);
    }

    /**
     * Display the specified exercise.
     */
    public function show($id): JsonResponse
    {
        $exercise = Exercise::with('creator')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $exercise
        ]);
    }

    /**
     * Update the specified exercise.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $exercise = Exercise::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name_en' => 'sometimes|required|string|max:255',
            'name_gr' => 'sometimes|required|string|max:255',
            'muscle_group' => 'sometimes|required|string|max:255',
            'category' => 'sometimes|required|string|max:255',
            'equipment' => 'nullable|array',
            'equipment.*' => 'string',
            'difficulty_level' => 'sometimes|required|in:beginner,intermediate,advanced',
            'description' => 'nullable|string|max:2000',
            'video_url' => 'nullable|url',
            'image_url' => 'nullable|url',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $exercise->update($request->only([
            'name_en', 'name_gr', 'muscle_group', 'category', 'equipment',
            'difficulty_level', 'description', 'video_url', 'image_url', 'is_active'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Exercise updated successfully',
            'data' => $exercise
        ]);
    }

    /**
     * Remove the specified exercise.
     */
    public function destroy($id): JsonResponse
    {
        $exercise = Exercise::findOrFail($id);

        // Soft delete by marking as inactive instead of actually deleting
        // to preserve training history references
        $exercise->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Exercise marked as inactive successfully'
        ]);
    }

    /**
     * Permanently delete an exercise (admin only).
     */
    public function forceDestroy($id): JsonResponse
    {
        $exercise = Exercise::findOrFail($id);
        $exercise->delete();

        return response()->json([
            'success' => true,
            'message' => 'Exercise permanently deleted'
        ]);
    }

    /**
     * Get muscle groups list.
     */
    public function muscleGroups(): JsonResponse
    {
        $muscleGroups = Exercise::distinct()->pluck('muscle_group');

        return response()->json([
            'success' => true,
            'data' => $muscleGroups
        ]);
    }

    /**
     * Get categories list.
     */
    public function categories(): JsonResponse
    {
        $categories = Exercise::distinct()->pluck('category');

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Get equipment list.
     */
    public function equipment(): JsonResponse
    {
        $allEquipment = Exercise::whereNotNull('equipment')
            ->get()
            ->pluck('equipment')
            ->flatten()
            ->unique()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $allEquipment
        ]);
    }
}
