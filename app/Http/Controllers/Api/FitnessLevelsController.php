<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FitnessLevel;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FitnessLevelsController extends Controller
{
    /**
     * Get all fitness level records for a user.
     */
    public function index(Request $request, $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $levels = FitnessLevel::where('user_id', $userId)
            ->with(['assessor'])
            ->orderBy('assessment_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $levels
        ]);
    }

    /**
     * Get the current fitness level for a user.
     */
    public function current(Request $request, $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $currentLevel = FitnessLevel::where('user_id', $userId)
            ->orderBy('assessment_date', 'desc')
            ->with(['assessor'])
            ->first();

        return response()->json([
            'success' => true,
            'data' => $currentLevel
        ]);
    }

    /**
     * Store a new fitness level assessment.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'level' => 'required|in:beginner,intermediate,advanced,elite',
            'assessment_date' => 'required|date',
            'assessed_by' => 'nullable|exists:users,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $fitnessLevel = FitnessLevel::create([
            'user_id' => $request->user_id,
            'level' => $request->level,
            'assessment_date' => $request->assessment_date,
            'assessed_by' => $request->assessed_by ?? auth()->id(),
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Fitness level recorded successfully',
            'data' => $fitnessLevel->load('assessor')
        ], 201);
    }

    /**
     * Display the specified fitness level.
     */
    public function show($id): JsonResponse
    {
        $fitnessLevel = FitnessLevel::with(['user', 'assessor'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $fitnessLevel
        ]);
    }

    /**
     * Update the specified fitness level.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $fitnessLevel = FitnessLevel::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'level' => 'sometimes|required|in:beginner,intermediate,advanced,elite',
            'assessment_date' => 'sometimes|required|date',
            'assessed_by' => 'nullable|exists:users,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $fitnessLevel->update($request->only([
            'level', 'assessment_date', 'assessed_by', 'notes'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Fitness level updated successfully',
            'data' => $fitnessLevel->load('assessor')
        ]);
    }

    /**
     * Remove the specified fitness level.
     */
    public function destroy($id): JsonResponse
    {
        $fitnessLevel = FitnessLevel::findOrFail($id);
        $fitnessLevel->delete();

        return response()->json([
            'success' => true,
            'message' => 'Fitness level deleted successfully'
        ]);
    }

    /**
     * Get fitness level history for a user.
     */
    public function history(Request $request, $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $history = FitnessLevel::where('user_id', $userId)
            ->with(['assessor'])
            ->orderBy('assessment_date', 'asc')
            ->get()
            ->map(function ($level) {
                return [
                    'id' => $level->id,
                    'level' => $level->level,
                    'level_label' => $level->level_label,
                    'level_color' => $level->level_color,
                    'assessment_date' => $level->assessment_date->format('Y-m-d'),
                    'assessed_by' => $level->assessor ? $level->assessor->name : null,
                    'notes' => $level->notes,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }
}
