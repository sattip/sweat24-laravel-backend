<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Questionnaire;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QuestionnaireController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Questionnaire::with('creator:id,name,email');

        // Filter by active status
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        // Filter by trigger type
        if ($request->has('trigger')) {
            $query->whereJsonContains('triggers', $request->trigger);
        }

        $questionnaires = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $questionnaires
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'triggers' => 'required|array',
            'triggers.*' => 'string',
            'frequency_settings' => 'nullable|array',
            'questions' => 'required|array',
            'questions.*.type' => 'required|string|in:text,multiple_choice,rating,rating_10,number,yes_no',
            'questions.*.question' => 'required|string',
            'questions.*.description' => 'nullable|string|max:500',
            'questions.*.required' => 'boolean',
            'questions.*.options' => 'nullable|array', // For multiple choice
            'is_active' => 'boolean',
            'scheduled_send_at' => 'nullable|date|after:now'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $questionnaire = Questionnaire::create([
            'title' => $request->title,
            'description' => $request->description,
            'triggers' => $request->triggers,
            'frequency_settings' => $request->frequency_settings,
            'questions' => $request->questions,
            'is_active' => $request->is_active ?? true,
            'created_by' => auth()->id(),
            'scheduled_send_at' => $request->scheduled_send_at
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Questionnaire created successfully',
            'data' => $questionnaire->load('creator:id,name,email')
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Questionnaire $questionnaire): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $questionnaire->load('creator:id,name,email')
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Questionnaire $questionnaire): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'triggers' => 'sometimes|required|array',
            'triggers.*' => 'string',
            'frequency_settings' => 'nullable|array',
            'questions' => 'sometimes|required|array',
            'questions.*.type' => 'required|string|in:text,multiple_choice,rating,rating_10,number,yes_no',
            'questions.*.question' => 'required|string',
            'questions.*.description' => 'nullable|string|max:500',
            'questions.*.required' => 'boolean',
            'questions.*.options' => 'nullable|array',
            'is_active' => 'boolean',
            'scheduled_send_at' => 'nullable|date|after:now'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $questionnaire->update($request->only([
            'title', 'description', 'triggers', 'frequency_settings',
            'questions', 'is_active', 'scheduled_send_at'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Questionnaire updated successfully',
            'data' => $questionnaire->load('creator:id,name,email')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Questionnaire $questionnaire): JsonResponse
    {
        $questionnaire->delete();

        return response()->json([
            'success' => true,
            'message' => 'Questionnaire deleted successfully'
        ]);
    }

    /**
     * Get active questionnaires for users
     */
    public function active(Request $request): JsonResponse
    {
        $userId = $request->get('user_id');
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User ID is required'
            ], 400);
        }

        $questionnaires = Questionnaire::active()
            ->whereJsonContains('triggers', $request->get('trigger_type', 'daily'))
            ->get();

        return response()->json([
            'success' => true,
            'data' => $questionnaires
        ]);
    }

    /**
     * Toggle questionnaire active status
     */
    public function toggleActive(Questionnaire $questionnaire): JsonResponse
    {
        $questionnaire->update(['is_active' => !$questionnaire->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Questionnaire status updated successfully',
            'data' => $questionnaire
        ]);
    }
}
