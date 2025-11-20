<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrainingSession;
use App\Models\TrainingExercise;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TrainingSessionsController extends Controller
{
    /**
     * Get all training sessions for a user.
     */
    public function index(Request $request, $userId = null): JsonResponse
    {
        $query = TrainingSession::with(['user', 'trainer', 'exercises.exercise']);

        if ($userId) {
            $query->forUser($userId);
        }

        // Filter by trainer
        if ($request->has('trainer_id')) {
            $query->forTrainer($request->trainer_id);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->betweenDates($request->start_date, $request->end_date);
        }

        // Filter by session type
        if ($request->has('session_type')) {
            $query->where('session_type', $request->session_type);
        }

        // Filter by intensity
        if ($request->has('min_intensity') && $request->has('max_intensity')) {
            $query->byIntensity($request->min_intensity, $request->max_intensity);
        }

        $sessions = $query->orderBy('session_date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }

    /**
     * Store a new training session.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'trainer_id' => 'nullable|exists:users,id',
            'booking_id' => 'nullable|exists:bookings,id',
            'session_date' => 'required|date',
            'duration_minutes' => 'required|integer|min:1',
            'session_type' => 'required|in:personal,semi_personal,group',
            'intensity' => 'required|integer|min:1|max:10',
            'muscle_groups' => 'nullable|array',
            'muscle_groups.*' => 'string',
            'includes_cardio' => 'nullable|boolean',
            'includes_cognitive' => 'nullable|boolean',
            'includes_mobility' => 'nullable|boolean',
            'includes_balance' => 'nullable|boolean',
            'includes_functional' => 'nullable|boolean',
            'is_total_body' => 'nullable|boolean',
            'notes' => 'nullable|string|max:2000',
            'exercises' => 'nullable|array',
            'exercises.*.exercise_id' => 'nullable|exists:exercises,id',
            'exercises.*.exercise_name' => 'nullable|string',
            'exercises.*.sets' => 'required|integer|min:1',
            'exercises.*.reps' => 'required|integer|min:1',
            'exercises.*.weight_kg' => 'nullable|numeric|min:0',
            'exercises.*.rest_seconds' => 'nullable|integer|min:0',
            'exercises.*.tempo' => 'nullable|string',
            'exercises.*.rir' => 'nullable|integer|min:0',
            'exercises.*.exercise_type' => 'nullable|in:standard,superset,drop,pyramid,emom,amrap,failure,timed',
            'exercises.*.superset_with' => 'nullable|string',
            'exercises.*.notes' => 'nullable|string',
            'exercises.*.order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Create the training session
            $session = TrainingSession::create([
                'user_id' => $request->user_id,
                'trainer_id' => $request->trainer_id ?? auth()->id(),
                'booking_id' => $request->booking_id,
                'session_date' => $request->session_date,
                'duration_minutes' => $request->duration_minutes,
                'session_type' => $request->session_type,
                'intensity' => $request->intensity,
                'muscle_groups' => $request->muscle_groups ?? [],
                'includes_cardio' => $request->includes_cardio ?? false,
                'includes_cognitive' => $request->includes_cognitive ?? false,
                'includes_mobility' => $request->includes_mobility ?? false,
                'includes_balance' => $request->includes_balance ?? false,
                'includes_functional' => $request->includes_functional ?? false,
                'is_total_body' => $request->is_total_body ?? false,
                'notes' => $request->notes,
            ]);

            // Create the exercises if provided
            if ($request->has('exercises') && is_array($request->exercises)) {
                foreach ($request->exercises as $index => $exerciseData) {
                    TrainingExercise::create([
                        'training_session_id' => $session->id,
                        'exercise_id' => $exerciseData['exercise_id'] ?? null,
                        'exercise_name' => $exerciseData['exercise_name'] ?? null,
                        'sets' => $exerciseData['sets'],
                        'reps' => $exerciseData['reps'],
                        'weight_kg' => $exerciseData['weight_kg'] ?? null,
                        'rest_seconds' => $exerciseData['rest_seconds'] ?? null,
                        'tempo' => $exerciseData['tempo'] ?? null,
                        'rir' => $exerciseData['rir'] ?? null,
                        'exercise_type' => $exerciseData['exercise_type'] ?? 'standard',
                        'superset_with' => $exerciseData['superset_with'] ?? null,
                        'notes' => $exerciseData['notes'] ?? null,
                        'order' => $exerciseData['order'] ?? $index,
                    ]);
                }

                // Calculate total volume
                $session->calculateTotalVolume();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Training session created successfully',
                'data' => $session->load(['user', 'trainer', 'exercises.exercise'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create training session',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified training session.
     */
    public function show($id): JsonResponse
    {
        $session = TrainingSession::with(['user', 'trainer', 'exercises.exercise', 'booking'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $session
        ]);
    }

    /**
     * Update the specified training session.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $session = TrainingSession::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'session_date' => 'sometimes|required|date',
            'duration_minutes' => 'sometimes|required|integer|min:1',
            'session_type' => 'sometimes|required|in:personal,semi_personal,group',
            'intensity' => 'sometimes|required|integer|min:1|max:10',
            'muscle_groups' => 'nullable|array',
            'muscle_groups.*' => 'string',
            'includes_cardio' => 'nullable|boolean',
            'includes_cognitive' => 'nullable|boolean',
            'includes_mobility' => 'nullable|boolean',
            'includes_balance' => 'nullable|boolean',
            'includes_functional' => 'nullable|boolean',
            'is_total_body' => 'nullable|boolean',
            'notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $session->update($request->only([
            'session_date', 'duration_minutes', 'session_type', 'intensity',
            'muscle_groups', 'includes_cardio', 'includes_cognitive',
            'includes_mobility', 'includes_balance', 'includes_functional',
            'is_total_body', 'notes'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Training session updated successfully',
            'data' => $session->load(['user', 'trainer', 'exercises.exercise'])
        ]);
    }

    /**
     * Remove the specified training session.
     */
    public function destroy($id): JsonResponse
    {
        $session = TrainingSession::findOrFail($id);
        $session->delete(); // This will cascade delete the exercises

        return response()->json([
            'success' => true,
            'message' => 'Training session deleted successfully'
        ]);
    }

    /**
     * Get training analytics for a user.
     */
    public function analytics(Request $request, $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $sessions = TrainingSession::forUser($userId)
            ->betweenDates($startDate, $endDate)
            ->with('exercises')
            ->get();

        // Calculate analytics
        $totalSessions = $sessions->count();
        $totalVolume = $sessions->sum('total_volume');
        $avgIntensity = $sessions->avg('intensity');

        // Muscle group frequency
        $muscleGroupCounts = [];
        foreach ($sessions as $session) {
            if (is_array($session->muscle_groups)) {
                foreach ($session->muscle_groups as $group) {
                    $muscleGroupCounts[$group] = ($muscleGroupCounts[$group] ?? 0) + 1;
                }
            }
        }

        // Calculate muscle balance percentage
        $muscleBalance = [];
        if ($totalSessions > 0) {
            foreach ($muscleGroupCounts as $group => $count) {
                $muscleBalance[$group] = round(($count / $totalSessions) * 100, 1);
            }
        }

        // Weekly trends
        $weeklyVolume = $sessions->groupBy(function ($session) {
            return $session->session_date->format('Y-W');
        })->map->sum('total_volume');

        $analytics = [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'totals' => [
                'sessions' => $totalSessions,
                'total_volume_kg' => round($totalVolume, 2),
                'avg_intensity' => round($avgIntensity, 1),
                'avg_duration' => round($sessions->avg('duration_minutes'), 0),
            ],
            'muscle_groups' => [
                'frequency' => $muscleGroupCounts,
                'balance_percentage' => $muscleBalance,
            ],
            'weekly_volume' => $weeklyVolume,
            'intensity_distribution' => [
                'low' => $sessions->filter(fn($s) => $s->intensity <= 3)->count(),
                'moderate' => $sessions->filter(fn($s) => $s->intensity > 3 && $s->intensity <= 6)->count(),
                'high' => $sessions->filter(fn($s) => $s->intensity > 6)->count(),
            ],
            'session_types' => $sessions->groupBy('session_type')->map->count(),
            'includes' => [
                'cardio' => $sessions->where('includes_cardio', true)->count(),
                'cognitive' => $sessions->where('includes_cognitive', true)->count(),
                'mobility' => $sessions->where('includes_mobility', true)->count(),
                'balance' => $sessions->where('includes_balance', true)->count(),
                'functional' => $sessions->where('includes_functional', true)->count(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics
        ]);
    }
}
