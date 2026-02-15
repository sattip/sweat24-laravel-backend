<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExerciseMuscleGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExerciseMuscleGroupController extends Controller
{
    /**
     * Get all muscle groups.
     */
    public function index(): JsonResponse
    {
        $muscleGroups = ExerciseMuscleGroup::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $muscleGroups
        ]);
    }

    /**
     * Get active muscle groups only.
     */
    public function active(): JsonResponse
    {
        $muscleGroups = ExerciseMuscleGroup::active()
            ->orderBy('name')
            ->pluck('name');

        return response()->json([
            'success' => true,
            'data' => $muscleGroups
        ]);
    }

    /**
     * Store a new muscle group.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:exercise_muscle_groups,name',
            'name_en' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $muscleGroup = ExerciseMuscleGroup::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Η μυϊκή ομάδα δημιουργήθηκε επιτυχώς',
            'data' => $muscleGroup
        ], 201);
    }

    /**
     * Update a muscle group.
     */
    public function update(Request $request, ExerciseMuscleGroup $muscleGroup): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:exercise_muscle_groups,name,' . $muscleGroup->id,
            'name_en' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $muscleGroup->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Η μυϊκή ομάδα ενημερώθηκε επιτυχώς',
            'data' => $muscleGroup
        ]);
    }

    /**
     * Delete a muscle group.
     */
    public function destroy(ExerciseMuscleGroup $muscleGroup): JsonResponse
    {
        $muscleGroup->delete();

        return response()->json([
            'success' => true,
            'message' => 'Η μυϊκή ομάδα διαγράφηκε επιτυχώς'
        ]);
    }
}
