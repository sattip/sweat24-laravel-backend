<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExerciseEquipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExerciseEquipmentController extends Controller
{
    /**
     * Get all equipment.
     */
    public function index(): JsonResponse
    {
        $equipment = ExerciseEquipment::ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $equipment
        ]);
    }

    /**
     * Get active equipment only.
     */
    public function active(): JsonResponse
    {
        $equipment = ExerciseEquipment::active()
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $equipment
        ]);
    }

    /**
     * Store a new equipment.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:exercise_equipment,name',
            'name_en' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $equipment = ExerciseEquipment::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Ο εξοπλισμός δημιουργήθηκε επιτυχώς',
            'data' => $equipment
        ], 201);
    }

    /**
     * Display a specific equipment.
     */
    public function show(ExerciseEquipment $equipment): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $equipment
        ]);
    }

    /**
     * Update an equipment.
     */
    public function update(Request $request, ExerciseEquipment $equipment): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:exercise_equipment,name,' . $equipment->id,
            'name_en' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $equipment->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Ο εξοπλισμός ενημερώθηκε επιτυχώς',
            'data' => $equipment
        ]);
    }

    /**
     * Delete an equipment.
     */
    public function destroy(ExerciseEquipment $equipment): JsonResponse
    {
        $equipment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ο εξοπλισμός διαγράφηκε επιτυχώς'
        ]);
    }
}
