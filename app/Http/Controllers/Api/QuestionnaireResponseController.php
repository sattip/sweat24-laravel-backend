<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Questionnaire;
use App\Models\QuestionnaireResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QuestionnaireResponseController extends Controller
{
    /**
     * Get all questionnaire responses with filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = QuestionnaireResponse::with(['questionnaire:id,title', 'user:id,name,email']);

        // Filter by questionnaire
        if ($request->has('questionnaire_id')) {
            $query->where('questionnaire_id', $request->questionnaire_id);
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by trigger type
        if ($request->has('trigger_type')) {
            $query->where('trigger_type', $request->trigger_type);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('completed_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('completed_at', '<=', $request->to_date);
        }

        // Only completed responses
        if ($request->boolean('completed_only', true)) {
            $query->whereNotNull('completed_at');
        }

        $responses = $query->orderBy('completed_at', 'desc')->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $responses
        ]);
    }

    /**
     * Submit a questionnaire response
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'questionnaire_id' => 'required|exists:questionnaires,id',
            'responses' => 'required|array',
            'trigger_type' => 'required|string',
            'session_id' => 'nullable|string',
            'user_id' => 'required|integer|exists:users,id' // Required for public API calls
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = $request->user_id;

        // Check if user already responded to this questionnaire
        $existingResponse = QuestionnaireResponse::where('questionnaire_id', $request->questionnaire_id)
            ->where('user_id', $userId)
            ->where('trigger_type', $request->trigger_type)
            ->first();

        if ($existingResponse) {
            // Update existing response
            $existingResponse->update([
                'responses' => $request->responses,
                'completed_at' => now(),
                'session_id' => $request->session_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Questionnaire response updated successfully',
                'data' => $existingResponse->load(['questionnaire:id,title', 'user:id,name,email'])
            ]);
        }

        // Create new response
        $response = QuestionnaireResponse::create([
            'questionnaire_id' => $request->questionnaire_id,
            'user_id' => $userId,
            'responses' => $request->responses,
            'trigger_type' => $request->trigger_type,
            'session_id' => $request->session_id,
            'completed_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Questionnaire response submitted successfully',
            'data' => $response->load(['questionnaire:id,title', 'user:id,name,email'])
        ], 201);
    }

    /**
     * Get responses for a specific questionnaire
     */
    public function getByQuestionnaire(Questionnaire $questionnaire, Request $request): JsonResponse
    {
        $query = $questionnaire->responses()->with('user:id,name,email');

        // Filter by trigger type
        if ($request->has('trigger_type')) {
            $query->where('trigger_type', $request->trigger_type);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('completed_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('completed_at', '<=', $request->to_date);
        }

        $responses = $query->orderBy('completed_at', 'desc')->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $responses
        ]);
    }

    /**
     * Get user's responses
     */
    public function getUserResponses(Request $request): JsonResponse
    {
        $userId = $request->get('user_id') ?? auth()->id();

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User ID is required'
            ], 400);
        }

        $query = QuestionnaireResponse::where('user_id', $userId)
            ->with(['questionnaire:id,title,description']);

        // Filter by trigger type
        if ($request->has('trigger_type')) {
            $query->where('trigger_type', $request->trigger_type);
        }

        $responses = $query->orderBy('completed_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $responses
        ]);
    }

    /**
     * Show a specific response
     */
    public function show(QuestionnaireResponse $response): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $response->load(['questionnaire', 'user:id,name,email'])
        ]);
    }

    /**
     * Update a response (for partial saves)
     */
    public function update(Request $request, QuestionnaireResponse $response): JsonResponse
    {
        // Check if user owns this response or is admin
        if ($response->user_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'responses' => 'required|array',
            'session_id' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $response->update([
            'responses' => $request->responses,
            'session_id' => $request->session_id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Response updated successfully',
            'data' => $response
        ]);
    }

    /**
     * Delete a response
     */
    public function destroy(QuestionnaireResponse $response): JsonResponse
    {
        // Only admin can delete responses
        if (!auth()->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $response->delete();

        return response()->json([
            'success' => true,
            'message' => 'Response deleted successfully'
        ]);
    }

    /**
     * Get questionnaire statistics
     */
    public function getStatistics(Questionnaire $questionnaire): JsonResponse
    {
        $stats = [
            'total_responses' => $questionnaire->responses()->count(),
            'responses_by_trigger' => $questionnaire->responses()
                ->selectRaw('trigger_type, COUNT(*) as count')
                ->groupBy('trigger_type')
                ->get()
                ->pluck('count', 'trigger_type'),
            'completion_rate' => $questionnaire->responses()->whereNotNull('completed_at')->count(),
            'avg_completion_time' => null, // Would need to track start time
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
