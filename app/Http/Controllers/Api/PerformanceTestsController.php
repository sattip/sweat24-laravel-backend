<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PerformanceTest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PerformanceTestsController extends Controller
{
    /**
     * Get all performance tests for a user.
     */
    public function index(Request $request, $userId = null): JsonResponse
    {
        $query = PerformanceTest::with(['user', 'trainer']);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        // Filter by exercise name
        if ($request->has('exercise_name')) {
            $query->where('exercise_name', $request->exercise_name);
        }

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('test_date', [$request->start_date, $request->end_date]);
        }

        // Get only PRs
        if ($request->has('pr_only') && $request->pr_only) {
            $query->where('is_pr', true);
        }

        $tests = $query->orderBy('test_date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $tests
        ]);
    }

    /**
     * Store a new performance test.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'exercise_name' => 'required|string|max:255',
            'test_date' => 'required|date',
            'weight_kg' => 'nullable|numeric|min:0',
            'reps' => 'nullable|integer|min:0',
            'time_seconds' => 'nullable|integer|min:0',
            'category' => 'required|in:strength,core,endurance,cognitive',
            'notes' => 'nullable|string|max:1000',
            'trainer_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $test = PerformanceTest::create([
            'user_id' => $request->user_id,
            'exercise_name' => $request->exercise_name,
            'test_date' => $request->test_date,
            'weight_kg' => $request->weight_kg,
            'reps' => $request->reps,
            'time_seconds' => $request->time_seconds,
            'category' => $request->category,
            'notes' => $request->notes,
            'trainer_id' => $request->trainer_id ?? auth()->id(),
        ]);

        // Calculate improvement and PR status
        $test->calculateImprovement();
        $test->checkIfPR();
        $test->save();

        return response()->json([
            'success' => true,
            'message' => 'Performance test recorded successfully',
            'data' => $test->load(['user', 'trainer'])
        ], 201);
    }

    /**
     * Display the specified performance test.
     */
    public function show($id): JsonResponse
    {
        $test = PerformanceTest::with(['user', 'trainer'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $test
        ]);
    }

    /**
     * Update the specified performance test.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $test = PerformanceTest::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'exercise_name' => 'sometimes|required|string|max:255',
            'test_date' => 'sometimes|required|date',
            'weight_kg' => 'nullable|numeric|min:0',
            'reps' => 'nullable|integer|min:0',
            'time_seconds' => 'nullable|integer|min:0',
            'category' => 'sometimes|required|in:strength,core,endurance,cognitive',
            'notes' => 'nullable|string|max:1000',
            'trainer_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $test->update($request->only([
            'exercise_name', 'test_date', 'weight_kg', 'reps', 'time_seconds',
            'category', 'notes', 'trainer_id'
        ]));

        // Recalculate improvement and PR status
        $test->calculateImprovement();
        $test->checkIfPR();
        $test->save();

        return response()->json([
            'success' => true,
            'message' => 'Performance test updated successfully',
            'data' => $test->load(['user', 'trainer'])
        ]);
    }

    /**
     * Remove the specified performance test.
     */
    public function destroy($id): JsonResponse
    {
        $test = PerformanceTest::findOrFail($id);
        $userId = $test->user_id;
        $exerciseName = $test->exercise_name;

        $test->delete();

        // Recalculate PRs for this exercise
        $remainingTests = PerformanceTest::where('user_id', $userId)
            ->where('exercise_name', $exerciseName)
            ->get();

        if ($remainingTests->count() > 0) {
            $latestTest = $remainingTests->sortByDesc('test_date')->first();
            $latestTest->checkIfPR();
            $latestTest->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Performance test deleted successfully'
        ]);
    }

    /**
     * Get performance test progress for a specific exercise.
     */
    public function progress(Request $request, $userId, $exerciseName): JsonResponse
    {
        $user = User::findOrFail($userId);

        $tests = PerformanceTest::where('user_id', $userId)
            ->where('exercise_name', $exerciseName)
            ->orderBy('test_date', 'asc')
            ->get()
            ->map(function ($test) {
                return [
                    'id' => $test->id,
                    'test_date' => $test->test_date->format('Y-m-d'),
                    'weight_kg' => $test->weight_kg,
                    'reps' => $test->reps,
                    'time_seconds' => $test->time_seconds,
                    'volume' => ($test->weight_kg ?? 0) * ($test->reps ?? 1),
                    'improvement_percentage' => $test->improvement_percentage,
                    'is_pr' => $test->is_pr,
                    'notes' => $test->notes,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'exercise_name' => $exerciseName,
                'user' => $user->name,
                'tests' => $tests
            ]
        ]);
    }

    /**
     * Get performance analytics for a user.
     */
    public function analytics(Request $request, $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $tests = PerformanceTest::where('user_id', $userId)->get();

        $analytics = [
            'total_tests' => $tests->count(),
            'pr_count' => $tests->where('is_pr', true)->count(),
            'tests_by_category' => $tests->groupBy('category')->map->count(),
            'recent_prs' => $tests->where('is_pr', true)
                ->sortByDesc('test_date')
                ->take(5)
                ->map(function ($test) {
                    return [
                        'exercise_name' => $test->exercise_name,
                        'test_date' => $test->test_date->format('Y-m-d'),
                        'value' => $test->category === 'endurance'
                            ? "{$test->time_seconds}s"
                            : "{$test->weight_kg}kg × {$test->reps}",
                    ];
                })
                ->values(),
            'exercises_tracked' => $tests->pluck('exercise_name')->unique()->values(),
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics
        ]);
    }
}
