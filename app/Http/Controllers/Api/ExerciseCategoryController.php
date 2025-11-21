<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExerciseCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExerciseCategoryController extends Controller
{
    /**
     * Get all categories.
     */
    public function index(): JsonResponse
    {
        $categories = ExerciseCategory::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Get active categories only.
     */
    public function active(): JsonResponse
    {
        $categories = ExerciseCategory::active()
            ->orderBy('name')
            ->pluck('name');

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Store a new category.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:exercise_categories,name',
            'name_en' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $category = ExerciseCategory::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Η κατηγορία δημιουργήθηκε επιτυχώς',
            'data' => $category
        ], 201);
    }

    /**
     * Update a category.
     */
    public function update(Request $request, ExerciseCategory $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:exercise_categories,name,' . $category->id,
            'name_en' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Η κατηγορία ενημερώθηκε επιτυχώς',
            'data' => $category
        ]);
    }

    /**
     * Delete a category.
     */
    public function destroy(ExerciseCategory $category): JsonResponse
    {
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Η κατηγορία διαγράφηκε επιτυχώς'
        ]);
    }
}
